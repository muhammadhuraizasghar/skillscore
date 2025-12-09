<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ai.php';
if (!isset($_SESSION['uid'])) { header('Location: signin.php'); exit; }
$uid = (int)$_SESSION['uid'];
$conn = Database::conn();
header('X-Frame-Options: SAMEORIGIN');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'terminate') {
        $aid = (int)($_POST['attempt_id'] ?? 0);
        $stmt = $conn->prepare('UPDATE attempts SET status="blocked", termination_at=NOW(), unblock_after=DATE_ADD(NOW(), INTERVAL 3 DAY) WHERE id=? AND user_id=?');
        $stmt->bind_param('ii', $aid, $uid);
        $stmt->execute();
        $stmt->close();
        json_response(['ok'=>true]);
    }
    if ($action === 'save') {
        $aid = (int)($_POST['attempt_id'] ?? 0);
        $mcq = (int)($_POST['mcq_id'] ?? 0);
        $sel = substr($_POST['selected'] ?? '', 0, 1);
        $stmt = $conn->prepare('UPDATE attempt_answers SET selected=? WHERE attempt_id=? AND mcq_id=? AND locked=0');
        $stmt->bind_param('sii', $sel, $aid, $mcq);
        $stmt->execute();
        $stmt->close();
        json_response(['ok'=>true]);
    }
    if ($action === 'submit') {
        $aid = (int)($_POST['attempt_id'] ?? 0);
        $answers = $conn->query('SELECT a.selected, m.correct FROM attempt_answers a JOIN mcqs m ON a.mcq_id=m.id WHERE a.attempt_id='.$aid);
        $total = 0; $correct = 0;
        while($r = $answers->fetch_assoc()){ $total++; if ($r['selected'] && $r['selected'] === $r['correct']) $correct++; }
        $grade = $total ? (int)round(($correct/$total)*100) . '%' : null;
        $stmt = $conn->prepare('UPDATE attempts SET status="submitted", ended_at=NOW(), grade=? WHERE id=? AND user_id=?');
        $stmt->bind_param('sii', $grade, $aid, $uid);
        $stmt->execute();
        $stmt->close();
        $rep = 'Score: ' . ($grade ?? 'N/A') . ' over ' . $total . ' MCQs.';
        $certDir = 'certificates'; if (!is_dir($certDir)) { mkdir($certDir, 0775, true); }
        $u = $conn->query('SELECT username,first_name,last_name,degree_field FROM users WHERE id='.$uid)->fetch_assoc();
        $certHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Certificate</title></head><body style="font-family:Segoe UI; background:#fff; color:#111; padding:40px;"><div style="border:6px solid #222; border-radius:12px; padding:30px; max-width:800px; margin:0 auto;"><h1 style="margin:0 0 12px;">Board Skills Certificate</h1><div>Name: '.htmlspecialchars(($u['first_name'].' '.$u['last_name'])).'</div><div>Username: '.htmlspecialchars($u['username']).'</div><div>Field: '.htmlspecialchars($u['degree_field']).'</div><div>Date: '.date('Y-m-d').'</div><div style="margin-top:12px;">'.$rep.'</div></div></body></html>';
        $certPath = $certDir . DIRECTORY_SEPARATOR . 'cert_' . $uid . '_' . time() . '.html';
        file_put_contents($certPath, $certHtml);
        $conn->query('INSERT INTO profiles (user_id, performance_report, certificate_path) VALUES ('.$uid.', "'.$conn->real_escape_string($rep).'", "'.$conn->real_escape_string($certPath).'") ON DUPLICATE KEY UPDATE performance_report=VALUES(performance_report), certificate_path=VALUES(certificate_path)');
        json_response(['ok'=>true, 'grade'=>$grade]);
    }
    if ($action === 'ai_policy') {
        $prompt = trim($_POST['prompt'] ?? '');
        $sys = 'You generate fair, secure test policies and MCQ distributions across IQ, EQ, personality, and field-specific topics. Output short actionable guidance.';
        $text = $prompt ? gemini_generate_text($prompt, $sys) : null;
        json_response(['text'=>$text]);
    }
}
function latest_attempt($conn, $uid) {
    return $conn->query('SELECT * FROM attempts WHERE user_id='.$uid.' ORDER BY id DESC LIMIT 1')->fetch_assoc();
}
$latest = latest_attempt($conn, $uid);
if ($latest && $latest['status'] === 'blocked') {
    $unblock = strtotime($latest['unblock_after'] ?? '');
    if ($unblock && $unblock > time()) {
        $blocked = true;
    } else {
        $blocked = false;
    }
} else { $blocked = false; }
if ($blocked) {
    echo '<!DOCTYPE html><html><body style="font-family:system-ui; background:#0f1220; color:#fff; display:grid; place-items:center; min-height:100vh;"><div>Test temporarily terminated. Try again after unblock date: '.htmlspecialchars($latest['unblock_after']).'</div></body></html>'; exit;
}
if (!$latest || $latest['status'] !== 'active') {
    $attempt_no = $latest ? ((int)$latest['attempt_number'] + 1) : 1;
    $added = $latest ? ((int)$latest['added_mcqs'] + 50) : 0;
    $stmt = $conn->prepare('INSERT INTO attempts (user_id, attempt_number, added_mcqs) VALUES (?,?,?)');
    $stmt->bind_param('iii', $uid, $attempt_no, $added);
    $stmt->execute();
    $attempt_id = $stmt->insert_id; $stmt->close();
    if ($latest) {
        $copy = $conn->query('SELECT mcq_id, selected FROM attempt_answers WHERE attempt_id='.(int)$latest['id']);
        while($c = $copy->fetch_assoc()){
            $stmt = $conn->prepare('INSERT INTO attempt_answers (attempt_id, mcq_id, selected, locked) VALUES (?,?,?,1)');
            $sel = $c['selected']; $mcq_id = (int)$c['mcq_id'];
            $stmt->bind_param('iis', $attempt_id, $mcq_id, $sel);
            $stmt->execute();
            $stmt->close();
        }
    }
} else {
    $attempt_id = (int)$latest['id'];
}
$base = 300; $extra = $conn->query('SELECT added_mcqs FROM attempts WHERE id='.$attempt_id)->fetch_assoc()['added_mcqs'];
$total_needed = $base + (int)$extra;
$pool = $conn->query('SELECT id FROM mcqs ORDER BY RAND() LIMIT '.$total_needed);
$existing = $conn->query('SELECT mcq_id FROM attempt_answers WHERE attempt_id='.$attempt_id);
$existing_ids = [];
while($e = $existing->fetch_assoc()){ $existing_ids[(int)$e['mcq_id']] = true; }
while($m = $pool->fetch_assoc()){
    $mid = (int)$m['id'];
    if (!isset($existing_ids[$mid])) {
        $stmt = $conn->prepare('INSERT INTO attempt_answers (attempt_id, mcq_id) VALUES (?,?)');
        $stmt->bind_param('ii', $attempt_id, $mid);
        $stmt->execute();
        $stmt->close();
    }
}
$qa = $conn->query('SELECT a.mcq_id,a.selected,a.locked,m.question,m.option_a,m.option_b,m.option_c,m.option_d FROM attempt_answers a JOIN mcqs m ON a.mcq_id=m.id WHERE a.attempt_id='.$attempt_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Test Window</title>
  <style>
    :root { --bg:#0b0e1a; --text:#f5f7ff; --muted:#a9b0c7; --primary:#ff5f6d; --accent:#33d1ff; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: var(--bg); color:var(--text); }
    header { padding:12px 16px; display:flex; justify-content:space-between; align-items:center; background:#0f1220; position:sticky; top:0; z-index:10; border-bottom:1px solid rgba(255,255,255,.08); }
    .btn { padding:10px 14px; border:1px solid rgba(255,255,255,.12); background:linear-gradient(180deg, rgba(255,95,109,.35), rgba(255,95,109,.15)); color:var(--text); border-radius:10px; text-decoration:none; }
    .grid { display:grid; grid-template-columns: 1fr; gap:10px; padding:16px; }
    .q { background:#12152a; border:1px solid rgba(255,255,255,.08); border-radius:14px; padding:14px; }
    .muted { color:var(--muted); }
    .blocked { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background: rgba(0,0,0,.6); z-index:100; }
    .card { background: #15182b; border:1px solid rgba(255,255,255,.12); border-radius:14px; padding:18px; }
  </style>
</head>
<body>
  <div class="blocked" id="blocked"><div class="card"><div style="font-weight:700;margin-bottom:8px;">Test terminated</div><div class="muted">Violation detected. You can try again after 3 days.</div></div></div>
  <header>
    <div>Secure Test Window</div>
    <div style="display:flex; gap:8px; align-items:center;">
      <div class="muted">Attempt ID: <?= (int)$attempt_id ?></div>
      <button class="btn" onclick="submitTest()">Submit</button>
    </div>
  </header>
  <div class="grid" id="grid">
    <?php while($r = $qa->fetch_assoc()): ?>
      <div class="q">
        <div style="margin-bottom:8px;"><?= htmlspecialchars($r['question']) ?></div>
        <div class="muted" style="margin-bottom:6px;">Choose one option</div>
        <?php $opts = ['A'=>$r['option_a'],'B'=>$r['option_b'],'C'=>$r['option_c'],'D'=>$r['option_d']]; foreach($opts as $key=>$val): $disabled = ((int)$r['locked'])===1 ? 'disabled' : ''; ?>
          <label style="display:block;margin:6px 0;">
            <input type="radio" name="ans_<?= (int)$r['mcq_id'] ?>" value="<?= $key ?>" <?= $disabled ?> <?= ($r['selected']===$key?'checked':'') ?> onchange="saveAns(<?= (int)$attempt_id ?>, <?= (int)$r['mcq_id'] ?>, '<?= $key ?>')" />
            <span><?= htmlspecialchars($val) ?></span>
          </label>
        <?php endforeach; ?>
        <?php if (((int)$r['locked'])===1): ?><div class="muted">Locked from previous attempt</div><?php endif; ?>
      </div>
    <?php endwhile; ?>
  </div>
  <script src="blockchain.js"></script>
  <script>
    let violated = false;
    function terminate(){
      if(violated) return;
      violated = true;
      document.getElementById('blocked').style.display='flex';
      fetch('test.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ action:'terminate', attempt_id:'<?= (int)$attempt_id ?>' }) });
      if (window.Blockchain) { Blockchain.record('violation', { reason:'window_blur_or_copy_paste' }); }
      setTimeout(()=>{ window.close(); }, 1000);
    }
    ['blur','visibilitychange'].forEach(ev=>window.addEventListener(ev,()=>{ if(document.hidden || document.activeElement===document.body){ terminate(); } }));
    window.addEventListener('copy', terminate);
    window.addEventListener('paste', terminate);
    window.addEventListener('keydown', (e)=>{ if((e.ctrlKey && (e.key==='c'||e.key==='v'))){ terminate(); } });
    function saveAns(aid, mcq, val){
      fetch('test.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ action:'save', attempt_id:aid, mcq_id:mcq, selected:val }) });
      if (window.Blockchain) { Blockchain.record('answer', { mcq, val }); }
    }
    function submitTest(){
      const send = (hash,len)=>{
        const params = new URLSearchParams({ action:'submit', attempt_id:'<?= (int)$attempt_id ?>', chain_hash:(hash||''), chain_len:(len||0) });
        fetch('test.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:params })
          .then(r=>r.json()).then(d=>{ alert('Submitted. Grade: '+(d.grade||'N/A')); window.location.href='private_profile.php'; });
      };
      if (window.Blockchain) { Blockchain.finalize().then(h=>send(h, Blockchain.length())); } else { send(null,0); }
    }
    if (window.Blockchain) { Blockchain.init(<?= (int)$uid ?>, <?= (int)$attempt_id ?>); }
  </script>
</body>
</html>
