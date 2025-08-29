<?php
// orders.php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$uid = $_SESSION['user_id'];

if (isset($_GET['success']) && isset($_GET['order_id'])) {
    $successMsg = "Order placed successfully! Order ID: " . (int)$_GET['order_id'];
}

// fetch orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$ordersRes = $stmt->get_result();
$orders = $ordersRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Orders — MyShop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="nav">
  <div class="container">
    <a class="brand" href="index.php">MyShop</a>
    <div class="nav-right">
      <a href="cart.php" class="btn-link">Cart</a>
      <a href="logout.php" class="btn-link">Logout</a>
    </div>
  </div>
</header>

<main class="container">
  <h1>My Orders</h1>
  <?php if (!empty($successMsg)): ?>
    <div class="msg success"><?php echo htmlspecialchars($successMsg); ?></div>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
    <p>No orders yet. <a href="index.php">Shop now</a></p>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
      <div class="order-card">
        <div>Order #<?php echo $o['id']; ?> — ₹<?php echo number_format($o['total'],2); ?></div>
        <div><?php echo $o['created_at']; ?></div>
        <div>Address: <?php echo htmlspecialchars($o['address']); ?></div>
        <div><a href="order_view.php?id=<?php echo $o['id']; ?>">View items</a></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</main>
</body>
</html>
