<?php
session_start();
require_once __DIR__ . '/db.php';
$username = null;
if (isset($_SESSION['uid'])) {
  $conn = Database::conn();
  $uid = (int)$_SESSION['uid'];
  $row = $conn->query('SELECT username FROM users WHERE id='.$uid)->fetch_assoc();
  $username = $row ? $row['username'] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Desktop</title>
  <style>
    :root { --bg:#0f1220; --text:#f5f7ff; --muted:#a9b0c7; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: var(--bg); color:var(--text); }
    .wrap { max-width:1000px; margin:0 auto; padding:24px; }
    .card { background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:18px; }
    .grid { display:grid; grid-template-columns: repeat(3,1fr); gap:12px; }
    .btn { display:block; padding:12px; border-radius:12px; background:#12152a; border:1px solid rgba(255,255,255,.12); text-decoration:none; color:var(--text); }
    @media (max-width:900px){ .grid{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin:0 0 12px;">Board Desktop</h2>
    <div class="card">
      <div class="grid">
        <a class="btn" href="index.php">Home</a>
        <a class="btn" href="signup.php">Sign Up</a>
        <a class="btn" href="signin.php">Sign In</a>
        <a class="btn" href="private_profile.php">Private Profile</a>
        <a class="btn" href="<?= $username ? ('public_profile.php?u='.urlencode($username)) : 'public_profile.php' ?>">Public Profile</a>
        <a class="btn" href="test.php">Test Window</a>
      </div>
    </div>
  </div>
</body>
</html>
