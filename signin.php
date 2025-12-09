<?php
session_start();
require_once __DIR__ . '/db.php';
$conn = Database::conn();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$identifier || !$password) { $error = 'Missing credentials'; }
    if (!$error) {
        $stmt = $conn->prepare('SELECT id,password_hash FROM users WHERE email=? OR cnic=?');
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['uid'] = (int)$row['id'];
            header('Location: private_profile.php');
            exit;
        } else {
            $error = 'Invalid login';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In</title>
  <style>
    :root { --bg:#0f1220; --text:#f5f7ff; --muted:#a9b0c7; --primary:#6c7bff; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: var(--bg); color:var(--text); display:grid; place-items:center; min-height:100vh; }
    .card { width:420px; max-width:92vw; background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:24px; }
    label { font-size:13px; color:var(--muted); }
    input { width:100%; padding:12px; border-radius:12px; border:1px solid rgba(255,255,255,.12); background:#12152a; color:var(--text); }
    .btn { width:100%; padding:12px 18px; border:1px solid rgba(255,255,255,.08); background:linear-gradient(180deg, rgba(108,123,255,.35), rgba(108,123,255,.15)); color:var(--text); border-radius:12px; text-decoration:none; transition:.25s ease; display:inline-block; margin-top:12px; }
    .error { background:#3b1e25; border:1px solid #a34b5c; color:#ffced6; padding:10px; border-radius:12px; margin-bottom:12px; }
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin:0 0 10px;">Welcome back</h2>
    <p style="color:#dbe2ff;margin:0 0 18px;">Sign in with your email or CNIC.</p>
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <label>Email or CNIC</label>
      <input name="identifier" />
      <label>Password</label>
      <input type="password" name="password" />
      <button class="btn" type="submit">Sign In</button>
      <a class="btn" href="signup.php" style="background:transparent; border-color:rgba(255,255,255,.18);">Create account</a>
    </form>
  </div>
</body>
</html>
