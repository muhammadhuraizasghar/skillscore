<?php
require_once __DIR__ . '/db.php';
$conn = Database::conn();
$username = trim($_GET['u'] ?? '');
if (!$username) { http_response_code(400); echo 'Missing user'; exit; }
$conn->query("SET SESSION sql_select_limit=18446744073709551615");
$ajax = isset($_GET['ajax']) ? $_GET['ajax'] : null;
$uid = null;
$stmt = $conn->prepare('SELECT id,first_name,last_name,degree_field,university,bio_short,profile_picture_path,github_url,linkedin_url,website_url,reference_url FROM users WHERE username=?');
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();
$stmt->close();
if (!$u) { http_response_code(404); echo 'User not found'; exit; }
$uid = (int)$u['id'];
if ($ajax === 'grades') {
    header('Content-Type: application/json');
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $stmt = $conn->prepare('SELECT grade,attempt_number,ended_at FROM attempts WHERE user_id=? AND grade IS NOT NULL ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('iii', $uid, $limit, $offset);
    $stmt->execute();
    $gRes = $stmt->get_result();
    $rows = [];
    while($g = $gRes->fetch_assoc()){ $rows[] = $g; }
    $stmt->close();
    echo json_encode(['items'=>$rows]);
    exit;
}
$stmt = $conn->prepare('SELECT summary,performance_report FROM profiles WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(*) c FROM attempts WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$attempts = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();
$grades = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@<?= htmlspecialchars($username) ?> • Public Profile</title>
  <style>
    :root { --bg:#0f1220; --text:#f5f7ff; --muted:#a9b0c7; --primary:#6c7bff; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: var(--bg); color:var(--text); }
    .wrap { max-width:900px; margin:0 auto; padding:24px; }
    .card { background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:18px; }
    .muted { color:var(--muted); }
    .row { display:flex; gap:12px; align-items:center; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    @media (max-width:800px){ .grid{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="row">
        <img loading="lazy" src="<?= htmlspecialchars($u['profile_picture_path'] ?: '') ?>" alt="avatar" style="width:72px;height:72px;border-radius:12px;object-fit:cover;background:#12152a;border:1px solid rgba(255,255,255,.1);" />
        <div>
          <div style="font-weight:700; font-size:18px;">
            <?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?> <span class="muted">@<?= htmlspecialchars($username) ?></span>
          </div>
          <div class="muted"><?= htmlspecialchars($u['degree_field'] ?: 'N/A') ?> • <?= htmlspecialchars($u['university'] ?: 'N/A') ?></div>
          <div class="muted">Attempts: <?= (int)$attempts ?></div>
        </div>
      </div>
      <div style="margin:12px 0; white-space:pre-wrap;"><?= htmlspecialchars(($p['summary'] ?? '') ?: '') ?></div>
      <div class="grid" style="margin-top:12px;">
        <?php if (!empty($u['github_url'])): ?><a class="card" href="<?= htmlspecialchars($u['github_url']) ?>" target="_blank">GitHub</a><?php endif; ?>
        <?php if (!empty($u['linkedin_url'])): ?><a class="card" href="<?= htmlspecialchars($u['linkedin_url']) ?>" target="_blank">LinkedIn</a><?php endif; ?>
        <?php if (!empty($u['website_url'])): ?><a class="card" href="<?= htmlspecialchars($u['website_url']) ?>" target="_blank">Website</a><?php endif; ?>
        <?php if (!empty($u['reference_url'])): ?><a class="card" href="<?= htmlspecialchars($u['reference_url']) ?>" target="_blank">Reference</a><?php endif; ?>
      </div>
    </div>
    <div class="card" style="margin-top:14px;">
      <h3 style="margin:0 0 8px;">Grades</h3>
      <div id="grades"></div>
    </div>
  </div>
  <script>
    function renderGrades(items){
      const root = document.getElementById('grades');
      root.innerHTML = '';
      items.forEach(g=>{
        const div = document.createElement('div');
        div.style.cssText = 'padding:8px;border:1px solid rgba(255,255,255,.08);border-radius:10px;margin:6px 0;display:flex;justify-content:space-between;';
        div.innerHTML = `<div>Attempt #${g.attempt_number}</div><div>${g.grade}</div><div class="muted">${g.ended_at||''}</div>`;
        root.appendChild(div);
      });
    }
    function loadGrades(){
      fetch('public_profile.php?ajax=grades&u=<?= urlencode($username) ?>&limit=10').then(r=>r.json()).then(d=>{ renderGrades(d.items||[]); });
    }
    document.addEventListener('DOMContentLoaded', loadGrades);
  </script>
</body>
</html>
