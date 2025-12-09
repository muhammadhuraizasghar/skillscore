<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ai.php';
$conn = Database::conn();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $degree_field = trim($_POST['degree_field'] ?? '');
    $cnic = trim($_POST['cnic'] ?? '');
    $cnic_issue_date = $_POST['cnic_issue_date'] ?? null;
    $address_line = trim($_POST['address_line'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $bio_short = trim($_POST['bio_short'] ?? '');
    $github_url = trim($_POST['github_url'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $website_url = trim($_POST['website_url'] ?? '');
    $reference_url = trim($_POST['reference_url'] ?? '');
    $interests = $_POST['interests'] ?? [];
    $exp = $_POST['exp'] ?? [];
    $degree_file_path = null;
    $passport_photo_path = null;
    $profile_picture_path = null;
    if (!preg_match('/^[a-zA-Z0-9_\.]{3,64}$/', $username)) { $error = 'Invalid username'; }
    if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email'; }
    if (!$error && strlen($password) < 8) { $error = 'Password too short'; }
    if (!$error) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username=? OR email=?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) { $error = 'Username or email already exists'; }
        $stmt->close();
    }
    if (!$error && !empty($_FILES['degree_file']['name'])) {
        $degree_file_path = store_upload($_FILES['degree_file']);
        if (!$degree_file_path) { $error = 'Degree file upload failed'; }
    }
    if (!$error && !empty($_FILES['passport_photo']['name'])) {
        $passport_photo_path = store_upload($_FILES['passport_photo']);
        if (!$passport_photo_path) { $error = 'Passport photo upload failed'; }
    }
    if (!$error && !empty($_FILES['profile_picture']['name'])) {
        $profile_picture_path = store_upload($_FILES['profile_picture']);
        if (!$profile_picture_path) { $error = 'Profile picture upload failed'; }
    }
    if (!$error) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username,email,password_hash,first_name,last_name,company,university,degree_field,degree_file_path,cnic,cnic_issue_date,passport_photo_path,profile_picture_path,address_line,country,city,zipcode,sex,gender,nationality,bio_short,github_url,linkedin_url,website_url,reference_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('sssssssssssssssssssssssss', $username,$email,$hash,$first_name,$last_name,$company,$university,$degree_field,$degree_file_path,$cnic,$cnic_issue_date,$passport_photo_path,$profile_picture_path,$address_line,$country,$city,$zipcode,$sex,$gender,$nationality,$bio_short,$github_url,$linkedin_url,$website_url,$reference_url);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();
            if (is_array($interests)) {
                $istmt = $conn->prepare('INSERT INTO interests (user_id,label) VALUES (?,?)');
                foreach ($interests as $label) {
                    $label = trim($label);
                    if ($label) { $istmt->bind_param('is', $user_id, $label); $istmt->execute(); }
                }
                $istmt->close();
            }
            if (is_array($exp)) {
                $wstmt = $conn->prepare('INSERT INTO work_experience (user_id,title,company,start_date,end_date,description) VALUES (?,?,?,?,?,?)');
                foreach ($exp as $e) {
                    $title = trim($e['title'] ?? '');
                    $comp = trim($e['company'] ?? '');
                    $sd = $e['start_date'] ?? null;
                    $ed = $e['end_date'] ?? null;
                    $desc = trim($e['description'] ?? '');
                    if ($title) { $wstmt->bind_param('isssss', $user_id,$title,$comp,$sd,$ed,$desc); $wstmt->execute(); }
                }
                $wstmt->close();
            }
            $summary = generate_profile_summary($user_id);
            if ($summary) { save_profile_summary($user_id, $summary); }
            $_SESSION['uid'] = $user_id;
            header('Location: private_profile.php');
            exit;
        } else {
            $errno = $conn->errno;
            if ($errno === 1062) { $error = 'Duplicate entry: username, email, or CNIC already exists'; }
            else { $error = 'Registration failed'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up</title>
  <style>
    :root { --bg:#0f1220; --card:#171a2b; --text:#f5f7ff; --muted:#a9b0c7; --primary:#6c7bff; --accent:#33d1ff; }
    body { margin:0; font-family: Inter,system-ui,Segoe UI,Roboto,Arial; background: var(--bg); color:var(--text); }
    .wrap { max-width:1100px; margin:0 auto; padding:24px; }
    .card { background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:24px; }
    .grid { display:grid; grid-template-columns: repeat(3,1fr); gap:16px; }
    .full { grid-column: 1 / -1; }
    label { font-size:13px; color:var(--muted); }
    input, select, textarea { width:100%; padding:12px; border-radius:12px; border:1px solid rgba(255,255,255,.12); background:#12152a; color:var(--text); }
    .btn { padding:12px 18px; border:1px solid rgba(255,255,255,.08); background:linear-gradient(180deg, rgba(108,123,255,.35), rgba(108,123,255,.15)); color:var(--text); border-radius:12px; text-decoration:none; transition:.25s ease; display:inline-block; }
    .error { background:#3b1e25; border:1px solid #a34b5c; color:#ffced6; padding:10px; border-radius:12px; margin-bottom:12px; }
    .row { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
    .chips { display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
    .chip { background:#12152a; border:1px solid rgba(255,255,255,.12); border-radius:999px; padding:6px 10px; font-size:12px; }
    @media (max-width:960px){ .grid{ grid-template-columns:1fr; } .row{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div id="loader" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,.5);z-index:50;">
      <div style="width:44px;height:44px;border-radius:50%;border:3px solid rgba(255,255,255,.15);border-top-color:#33d1ff;animation:spin 1s linear infinite"></div>
    </div>
    <h2 style="margin:0 0 12px;">Create your account</h2>
    <p style="color:#dbe2ff;margin:0 0 20px;">Verify degrees, add interests, and generate your AI summary.</p>
    <div class="card">
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" onsubmit="document.getElementById('loader').style.display='flex'">
        <div class="grid">
          <div>
            <label>Profile picture</label>
            <input type="file" name="profile_picture" accept="image/*" />
          </div>
          <div>
            <label>Passport photo</label>
            <input type="file" name="passport_photo" accept="image/*" />
          </div>
          <div>
            <label>Degree file</label>
            <input type="file" name="degree_file" />
          </div>
          <div>
            <label>First name</label>
            <input required name="first_name" />
          </div>
          <div>
            <label>Last name</label>
            <input required name="last_name" />
          </div>
          <div>
            <label>Username</label>
            <input required name="username" />
          </div>
          <div>
            <label>Email</label>
            <input required type="email" name="email" />
          </div>
          <div>
            <label>Password</label>
            <input required type="password" name="password" />
          </div>
          <div>
            <label>Company</label>
            <input name="company" />
          </div>
          <div>
            <label>University</label>
            <input name="university" />
          </div>
          <div>
            <label>Degree / field</label>
            <input name="degree_field" />
          </div>
          <div>
            <label>CNIC number</label>
            <input name="cnic" />
          </div>
          <div>
            <label>CNIC issue date</label>
            <input type="date" name="cnic_issue_date" />
          </div>
          <div>
            <label>Country</label>
            <input name="country" />
          </div>
          <div>
            <label>City</label>
            <input name="city" />
          </div>
          <div>
            <label>Zip code</label>
            <input name="zipcode" />
          </div>
          <div>
            <label>Sex</label>
            <select name="sex"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select>
          </div>
          <div>
            <label>Gender</label>
            <input name="gender" />
          </div>
          <div>
            <label>Nationality</label>
            <input name="nationality" />
          </div>
          <div class="full">
            <label>Address</label>
            <input name="address_line" />
          </div>
          <div class="full">
            <label>Bio</label>
            <textarea name="bio_short" rows="3" placeholder="Introduce yourself"></textarea>
          </div>
          <div>
            <label>GitHub</label>
            <input type="url" name="github_url" />
          </div>
          <div>
            <label>LinkedIn</label>
            <input type="url" name="linkedin_url" />
          </div>
          <div>
            <label>Website</label>
            <input type="url" name="website_url" />
          </div>
          <div>
            <label>Reference</label>
            <input type="url" name="reference_url" />
          </div>
          <div class="full">
            <label>Interests</label>
            <div class="row">
              <input id="interestInput" placeholder="Add interests like AI, Cardiology, Astronomy" />
              <button type="button" class="btn" onclick="addInterest()">Add</button>
            </div>
            <div class="chips" id="chips"></div>
          </div>
          <div class="full">
            <label>Work experience</label>
            <div id="expList"></div>
            <button type="button" class="btn" onclick="addExp()">Add experience</button>
          </div>
        </div>
        <input type="hidden" name="interests[]" id="interestsHidden" />
        <div id="expHidden"></div>
        <div style="margin-top:18px; display:flex; gap:12px;">
          <button class="btn" type="submit">Create account</button>
          <a class="btn" href="signin.php">I have an account</a>
        </div>
      </form>
    </div>
  </div>
  <script>
    const chips = document.getElementById('chips');
    const interests = [];
    function addInterest(){
      const val = document.getElementById('interestInput').value.trim();
      if(!val) return;
      if(interests.includes(val)) return;
      interests.push(val);
      const c = document.createElement('div');
      c.className='chip'; c.textContent=val; c.onclick=()=>{ interests.splice(interests.indexOf(val),1); c.remove(); updateInterestsHidden(); };
      chips.appendChild(c);
      document.getElementById('interestInput').value='';
      updateInterestsHidden();
    }
    function updateInterestsHidden(){
      const hidden = document.getElementById('interestsHidden');
      hidden.parentNode.removeChild(hidden);
      const form = document.querySelector('form');
      interests.forEach(v=>{
        const h = document.createElement('input'); h.type='hidden'; h.name='interests[]'; h.value=v; form.appendChild(h);
      });
    }
    function addExp(){
      const root = document.getElementById('expList');
      const idx = root.children.length;
      const wrap = document.createElement('div');
      wrap.style.margin='10px 0';
      wrap.innerHTML = `
        <div class="row">
          <input name="exp[${idx}][title]" placeholder="Title" />
          <input name="exp[${idx}][company]" placeholder="Company" />
        </div>
        <div class="row">
          <input type="date" name="exp[${idx}][start_date]" />
          <input type="date" name="exp[${idx}][end_date]" />
        </div>
        <textarea name="exp[${idx}][description]" rows="2" placeholder="Description"></textarea>
      `;
      root.appendChild(wrap);
    }
  </script>
  <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
