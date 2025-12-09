<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Board Skills | Welcome</title>
  <style>
    :root { --bg:#0f1220; --card:#171a2b; --text:#f5f7ff; --muted:#a9b0c7; --primary:#6c7bff; --accent:#33d1ff; --ok:#2ecc71; --warn:#ff9f43; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: radial-gradient(1200px 600px at 20% 0%, #12152a 0%, #0c0f1d 60%), var(--bg); color:var(--text); }
    header { display:flex; align-items:center; justify-content:space-between; padding:24px; }
    .brand { display:flex; align-items:center; gap:12px; font-weight:700; letter-spacing:0.3px; }
    .logo { width:36px; height:36px; background:linear-gradient(135deg, var(--primary), var(--accent)); border-radius:10px; box-shadow: 0 10px 30px rgba(108,123,255,.35); }
    .nav { display:flex; align-items:center; gap:12px; }
    .btn { padding:12px 18px; border:1px solid rgba(255,255,255,.08); background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.01)); color:var(--text); border-radius:12px; text-decoration:none; transition:.25s ease; backdrop-filter: blur(6px); }
    .btn:hover { transform: translateY(-2px); border-color: rgba(255,255,255,.18); }
    .btn.primary { background:linear-gradient(180deg, rgba(108,123,255,.35), rgba(108,123,255,.15)); border-color: rgba(108,123,255,.55); box-shadow: 0 8px 20px rgba(108,123,255,.35); }
    .hero { display:grid; grid-template-columns: 1.2fr 1fr; gap:24px; padding: 40px 24px; max-width:1080px; margin: 0 auto; }
    .card { background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:24px; box-shadow: 0 20px 60px rgba(0,0,0,.45); }
    .title { font-size:42px; line-height:1.16; margin:0 0 14px; }
    .muted { color:var(--muted); }
    .grid { display:grid; grid-template-columns: repeat(2,1fr); gap:12px; margin-top:18px; }
    .pill { padding:10px 12px; border:1px dashed rgba(255,255,255,.12); border-radius:12px; font-size:13px; color:#dbe2ff; background: rgba(108,123,255,.08); }
    .cta { display:flex; gap:12px; margin-top:18px; }
    .footer { text-align:center; padding:22px; color:var(--muted); font-size:13px; }
    .loader { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background: rgba(5,7,14,.55); backdrop-filter: blur(4px); z-index:50; }
    .spin { width:44px; height:44px; border-radius:50%; border:3px solid rgba(255,255,255,.15); border-top-color: var(--accent); animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    @media (max-width:900px){ .hero{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="loader" id="loader"><div class="spin"></div></div>
  <header>
    <div class="brand">
      <div class="logo"></div>
      <div>Board Skills</div>
    </div>
    <nav class="nav">
      <a class="btn" href="signin.php">Sign In</a>
      <a class="btn primary" href="signup.php">Sign Up</a>
    </nav>
  </header>
  <section class="hero">
    <div class="card">
      <h1 class="title">Test degrees, skills, and build a trusted profile</h1>
      <p class="muted">Students, teachers, professors, and specialists can verify credentials, take personalized tests, and publish performance-backed public profiles.</p>
      <div class="grid">
        <div class="pill">300 MCQs with IQ, EQ, personality</div>
        <div class="pill">Anti-cheat test window</div>
        <div class="pill">AI profile summary</div>
        <div class="pill">Certificates and grades</div>
      </div>
      <div class="cta">
        <a class="btn primary" href="signup.php" onclick="showLoader()">Get Started</a>
        <a class="btn" href="signin.php" onclick="showLoader()">I already have an account</a>
      </div>
    </div>
    <div class="card">
      <h3 style="margin:0 0 10px;">Quick Links</h3>
      <div class="grid">
        <a class="btn" href="desktop.php">Desktop</a>
        <a class="btn" href="public_profile.php">Public Profiles</a>
        <a class="btn" href="private_profile.php">My Profile</a>
        <a class="btn" href="test.php">Test Window</a>
      </div>
    </div>
  </section>
  <div class="footer">© Board Skills</div>
  <script>
    function showLoader(){ document.getElementById('loader').style.display='flex'; }
    window.addEventListener('pageshow',()=>{ document.getElementById('loader').style.display='none'; });
  </script>
</body>
</html>
