<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ai.php';
if (!isset($_SESSION['uid'])) { header('Location: signin.php'); exit; }
$uid = (int)$_SESSION['uid'];
$conn = Database::conn();
$conn->query("SET SESSION sql_select_limit=18446744073709551615");
if (isset($_GET['ajax']) && $_GET['ajax'] === 'attempts') {
    header('Content-Type: application/json');
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $stmt = $conn->prepare('SELECT id,attempt_number,status,grade,started_at,ended_at FROM attempts WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('iii', $uid, $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($a = $res->fetch_assoc()) { $rows[] = $a; }
    $stmt->close();
    echo json_encode(['items'=>$rows]);
    exit;
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $bio_short = trim($_POST['bio_short'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $profile_picture_path = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $profile_picture_path = store_upload($_FILES['profile_picture']);
        }
        if ($profile_picture_path) {
            $stmt = $conn->prepare('UPDATE users SET bio_short=?, phone=?, first_name=?, last_name=?, profile_picture_path=? WHERE id=?');
            $stmt->bind_param('sssssi', $bio_short,$phone,$first_name,$last_name,$profile_picture_path,$uid);
        } else {
            $stmt = $conn->prepare('UPDATE users SET bio_short=?, phone=?, first_name=?, last_name=? WHERE id=?');
            $stmt->bind_param('ssssi', $bio_short,$phone,$first_name,$last_name,$uid);
        }
        $stmt->execute();
        $stmt->close();
        $summary = generate_profile_summary($uid);
        if ($summary) { save_profile_summary($uid, $summary); }
        $msg = 'Profile updated';
    }
    if ($action === 'change_password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id=?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if ($row && password_verify($old, $row['password_hash']) && strlen($new) >= 8) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            $stmt->close();
            $msg = 'Password changed';
        } else {
            $msg = 'Password change failed';
        }
    }
}
$stmt = $conn->prepare('SELECT id,username,first_name,last_name,degree_field,profile_picture_path,bio_short,phone FROM users WHERE id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
$u = $u ?: ['username'=>'','first_name'=>'','last_name'=>'','degree_field'=>'','profile_picture_path'=>'','bio_short'=>'','phone'=>''];
$stmt = $conn->prepare('SELECT * FROM profiles WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(*) c FROM attempts WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$cRes = $stmt->get_result()->fetch_assoc();
$attemptCount = (int)($cRes['c'] ?? 0);
$stmt->close();
$attempts = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile</title>
  <style>
    :root { --bg:#0f1220; --text:#f5f7ff; --muted:#a9b0c7; --primary:#6c7bff; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: var(--bg); color:var(--text); }
    .wrap { max-width:1100px; margin:0 auto; padding:24px; }
    .grid { display:grid; grid-template-columns: 320px 1fr; gap:16px; }
    .card { background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:18px; }
    .btn { padding:10px 14px; border:1px solid rgba(255,255,255,.08); background:linear-gradient(180deg, rgba(108,123,255,.35), rgba(108,123,255,.15)); color:var(--text); border-radius:12px; text-decoration:none; transition:.25s ease; display:inline-block; }
    input, textarea { width:100%; padding:10px; border-radius:12px; border:1px solid rgba(255,255,255,.12); background:#12152a; color:var(--text); }
    .muted { color:var(--muted); }
    .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    @media (max-width:960px){ .grid{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div id="loader" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,.5);z-index:50;">
      <div style="width:44px;height:44px;border-radius:50%;border:3px solid rgba(255,255,255,.15);border-top-color:#33d1ff;animation:spin 1s linear infinite"></div>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h2 style="margin:0;">Private profile</h2>
      <div style="display:flex; gap:8px;">
        <a class="btn" href="public_profile.php?u=<?= urlencode($u['username']) ?>">View public profile</a>
        <a class="btn" href="#" onclick="window.open('test.php','testwin','width=1200,height=800');return false;">Start test</a>
        <a class="btn" href="index.php">Home</a>
      </div>
    </div>
    <?php if ($msg): ?><div class="card" style="margin:12px 0; color:#dbe2ff;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <div class="grid">
      <div class="card">
        <div style="display:flex; align-items:center; gap:12px;">
          <img loading="lazy" src="<?= htmlspecialchars($u['profile_picture_path'] ?: '') ?>" alt="avatar" style="width:76px;height:76px;border-radius:12px;object-fit:cover;background:#12152a;border:1px solid rgba(255,255,255,.1);" />
          <div>
            <div style="font-weight:700;"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></div>
            <div class="muted">@<?= htmlspecialchars($u['username']) ?> • <?= htmlspecialchars($u['degree_field'] ?: 'N/A') ?></div>
            <div class="muted">Attempts: <?= $attemptCount ?></div>
          </div>
        </div>
        <form method="post" enctype="multipart/form-data" style="margin-top:12px;" onsubmit="document.getElementById('loader').style.display='flex'">
          <input type="hidden" name="action" value="update_profile" />
          <label>Profile picture</label>
          <input type="file" name="profile_picture" accept="image/*" />
          <label>First name</label>
          <input name="first_name" value="<?= htmlspecialchars($u['first_name']) ?>" />
          <label>Last name</label>
          <input name="last_name" value="<?= htmlspecialchars($u['last_name']) ?>" />
          <label>Phone</label>
          <input name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" />
          <label>Bio</label>
          <textarea name="bio_short" rows="3"><?= htmlspecialchars($u['bio_short'] ?? '') ?></textarea>
          <button class="btn" type="submit">Save changes</button>
        </form>
        <form method="post" style="margin-top:14px;" onsubmit="document.getElementById('loader').style.display='flex'">
          <input type="hidden" name="action" value="change_password" />
          <div class="row">
            <div>
              <label>Old password</label>
              <input type="password" name="old_password" />
            </div>
            <div>
              <label>New password</label>
              <input type="password" name="new_password" />
            </div>
          </div>
          <button class="btn" type="submit">Change password</button>
        </form>
      </div>
      <div class="card">
        <h3 style="margin:0 0 8px;">AI summary</h3>
        <p class="muted">Automatically generated based on your profile.</p>
        <div style="margin:12px 0; white-space:pre-wrap;"><?= htmlspecialchars(($p['summary'] ?? '') ?: 'No summary yet.') ?></div>
        <h3 style="margin:18px 0 8px;">Performance report</h3>
        <div style="margin:12px 0; white-space:pre-wrap;"><?= htmlspecialchars(($p['performance_report'] ?? '') ?: 'No report yet.') ?></div>
        <h3 style="margin:18px 0 8px;">Certificate</h3>
        <?php if (!empty($p['certificate_path'])): ?>
          <a class="btn" href="<?= htmlspecialchars($p['certificate_path']) ?>" target="_blank">Open certificate</a>
        <?php else: ?>
          <div class="muted">No certificate available.</div>
        <?php endif; ?>
        <h3 style="margin:18px 0 8px;">Attempts</h3>
        <div id="attempts"></div>
      </div>
    </div>
  </div>
  <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
  <script>
    function renderAttempts(items){
      const root = document.getElementById('attempts');
      root.innerHTML = '';
      items.forEach(a=>{
        const div = document.createElement('div');
        div.style.cssText = 'padding:8px;border:1px solid rgba(255,255,255,.08);border-radius:10px;margin:6px 0;display:flex;justify-content:space-between;';
        div.innerHTML = `<div>#${a.attempt_number} • ${a.status} • ${a.grade||'-'}</div><div class="muted">Started ${a.started_at||''}</div>`;
        root.appendChild(div);
      });
    }
    function loadAttempts(){
      fetch('private_profile.php?ajax=attempts&limit=10').then(r=>r.json()).then(d=>{ renderAttempts(d.items||[]); });
    }
    document.addEventListener('DOMContentLoaded', loadAttempts);
  </script>
</body>
</html>
