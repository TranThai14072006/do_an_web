<?php
session_start();
require __DIR__ . "/../../config/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if ($email === '' || $password === '') {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    }
    elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn_user->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $message = "Email does not exist!";
        } else {

            $stmt = $conn_user->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);

            if ($stmt->execute()) {
                $message = "Reset password successful!";
                header("refresh:2; url=login.php");
            } else {
                $message = "Error updating password!";
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

/* ===== RESET ===== */
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Cormorant Garamond', sans-serif;
}

/* ===== BACKGROUND ===== */
body{
min-height:100vh;
display:flex;
flex-direction:column;
background:
linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.7)),
url("../../images/pfb10.jpg") no-repeat center/cover;
}

/* ===== HEADER ===== */
.header-container{
width:100%;
background:#fff;
box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

.search-bar{
max-width:1400px;
margin:auto;
display:flex;
align-items:center;
justify-content:space-between;
padding:12px 20px;
}

/* HOME BUTTON */
.home-btn{
display:flex;
align-items:center;
gap:6px;
padding:8px 14px;
border-radius:8px;
text-decoration:none;
color:#111;
font-weight:600;
transition:0.3s;
}

.home-btn:hover{
background:#f5f5f5;
color:#b8860b;
}

/* CENTER LOGO */
.search-bar .center{
flex:1;
display:flex;
justify-content:center;
}

.search-bar img{
height:55px;
}

/* ICON */
.icon-link{
width:40px;
height:40px;
display:flex;
align-items:center;
justify-content:center;
color:#000;
}

/* ===== FORM CENTER ===== */
.reset-container{
flex:1;
display:flex;
justify-content:center;
align-items:center;
}

/* ===== GLASS BOX ===== */
.reset-box{
position:relative;
background:rgba(255,255,255,0.08);
backdrop-filter:blur(20px);
width:380px;
padding:60px 30px 35px;
border-radius:20px;
text-align:center;
border:1px solid rgba(255,215,0,0.3);
box-shadow:0 0 40px rgba(255,215,0,0.2);
}

/* BACK BUTTON */
.back-btn{
position:absolute;
top:15px;
left:15px;
width:40px;
height:40px;
border-radius:50%;
border:1px solid rgba(255,215,0,0.6);
background:rgba(0,0,0,0.4);
color:#f8ce86;
font-size:18px;
cursor:pointer;
display:flex;
align-items:center;
justify-content:center;
transition:0.3s;
}

.back-btn:hover{
background:#f8ce86;
color:#000;
box-shadow:0 0 10px rgba(255,215,0,0.6);
transform:scale(1.05);
}

/* ===== TITLE ===== */
.reset-box h2{
color:#d4af37;
font-size:28px;
margin-bottom:10px;
text-shadow:0 0 15px rgba(255,215,0,0.5);
}

.reset-box p{
color:#f0e6b2;
margin-bottom:20px;
}

/* ===== INPUT ===== */
.reset-box input{
width:100%;
padding:12px 15px;
margin-bottom:15px;
border-radius:10px;
border:1px solid rgba(255,215,0,0.6);
background:rgba(255,255,255,0.1);
color:#f0e6b2;
outline:none;
}

.reset-box input::placeholder{
color:#f0e6b2;
}

/* ===== BUTTON ===== */
.reset-box button{
width:100%;
padding:12px;
border-radius:50px;
background:linear-gradient(120deg,#f8ce86,#d4af37,#b8860b);
border:none;
font-weight:bold;
color:#3b2f10;
cursor:pointer;
transition:0.3s;
}

.reset-box button:hover{
transform:scale(1.05);
box-shadow:0 0 25px rgba(255,215,0,0.6);
}

/* MESSAGE */
.message{
color:red;
margin-bottom:10px;
}

/* BACK dưới Continue */
.back-btn-2 {
  display: inline-block;
  margin-top: 10px;
  font-size: 13px;
  color: #ccc;
  text-decoration: none;
  transition: 0.2s;
}

.back-btn-2:hover {
  color: #f8ce86;
}

</style>
</head>

<body>

<header class="header-container">
<div class="search-bar">

<!-- LEFT -->
<div>
<a href="../../index.php" class="home-btn">
<i class="fas fa-home"></i> Home
</a>
</div>

<!-- CENTER -->
<div class="center">
<a href="../../index.php">
<img src="../../images/36-logo.png">
</a>
</div>

<!-- RIGHT -->
<div>
<a href="profile.php" class="icon-link">
<i class="fas fa-user"></i>
</a>
</div>

</div>
</header>

<div class="reset-container">
<div class="reset-box">

<!-- BACK BUTTON -->


<h2>Reset Password</h2>
<p>Reset your password if you forgot them.</p>

<?php if (!empty($message)) { ?>
<p class="message"><?php echo $message; ?></p>
<?php } ?>

<form method="POST">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<input type="password" name="confirmPassword" placeholder="Confirm Password" required>
<button type="submit">Confirm</button>

<a href="javascript:history.back()" class="back-btn-2">
  ← Back
</a>
</form>

</div>
</div>

</body>
</html>