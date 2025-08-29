<?php
// register.php
session_start();
require_once 'db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        // check existing
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered. Please login.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $ins->bind_param('sss', $name, $email, $hash);
            if ($ins->execute()) {
                $_SESSION['success'] = "Registration successful. Please login.";
                header('Location: login.php');
                exit();
            } else {
                $errors[] = "Registration failed. Try again.";
            }
            $ins->close();
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign Up — MyShop</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="bg-auth">
<div class="auth-card">
  <h2>Create account</h2>

  <?php if (!empty($errors)): ?>
    <div class="msg error">
      <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
    </div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input name="name" placeholder="Full name" value="<?php echo htmlspecialchars($_POST['name'] ?? '') ?>" required>
    <input name="email" type="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>" required>
    <input name="password" type="password" placeholder="Password" required>
    <input name="confirm" type="password" placeholder="Confirm password" required>
    <button class="btn" type="submit">Create account</button>
  </form>

  <p class="muted">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
