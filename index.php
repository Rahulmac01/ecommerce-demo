<?php
// index.php
session_start();
require_once 'db.php';

// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// handle add-to-cart (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_id'])) {
    $user_id = $_SESSION['user_id'];
    $pid = (int)$_POST['add_product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    // insert or update quantity
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
    $stmt->bind_param('iii', $user_id, $pid, $qty);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php');
    exit();
}

// fetch products
$products = [];
$res = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) $products[] = $row;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MyShop — Home</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="nav">
  <div class="container">
    <a class="brand" href="index.php">MyShop</a>
    <div class="nav-right">
      <span class="muted">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
      <a href="cart.php" class="btn-link">Cart</a>
      <a href="orders.php" class="btn-link">Orders</a>
      <a href="logout.php" class="btn-link">Logout</a>
    </div>
  </div>
</header>

<main class="container">
  <h1>Featured Products</h1>
  <div class="grid">
    <?php foreach ($products as $p): ?>
      <div class="card">
        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
        <h3><?php echo htmlspecialchars($p['name']); ?></h3>
        <p class="muted"><?php echo htmlspecialchars($p['description']); ?></p>
        <div class="price">₹ <?php echo number_format($p['price'],2); ?></div>

        <form method="post">
          <input type="hidden" name="add_product_id" value="<?php echo $p['id']; ?>">
          <input class="qty" type="number" name="qty" value="1" min="1" style="width:60px">
          <button class="btn" type="submit">Add to Cart</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>
