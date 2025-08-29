<?php
// checkout.php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$uid = $_SESSION['user_id'];

// fetch cart
$stmt = $conn->prepare("SELECT c.id AS cart_id, c.quantity, p.id AS pid, p.name, p.price FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$cartRes = $stmt->get_result();
$cartItems = $cartRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}

$total = 0;
foreach ($cartItems as $it) $total += $it['price'] * $it['quantity'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    if ($address === '') $errors[] = "Enter shipping address.";

    if (empty($errors)) {
        // create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, address) VALUES (?, ?, ?)");
        $stmt->bind_param('ids', $uid, $total, $address);
        if ($stmt->execute()) {
            $order_id = $stmt->insert_id;
            $stmt->close();
            // insert order items
            $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $it) {
                $ins->bind_param('iiid', $order_id, $it['pid'], $it['quantity'], $it['price']);
                $ins->execute();
            }
            $ins->close();
            // clear cart
            $del = $conn->prepare("DELETE FROM cart WHERE user_id=?");
            $del->bind_param('i', $uid);
            $del->execute();
            $del->close();

            // success
            header("Location: orders.php?success=1&order_id=".$order_id);
            exit();
        } else {
            $errors[] = "Could not place order. Try again.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout — MyShop</title>
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
  <h1>Checkout</h1>
  <?php if (!empty($errors)): ?>
    <div class="msg error"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
  <?php endif; ?>

  <div class="checkout-grid">
    <div>
      <h3>Order summary</h3>
      <?php foreach ($cartItems as $it): ?>
        <div class="checkout-item">
          <?php echo htmlspecialchars($it['name']); ?> × <?php echo $it['quantity']; ?>
          <span>₹ <?php echo number_format($it['price'] * $it['quantity'],2); ?></span>
        </div>
      <?php endforeach; ?>
      <div class="checkout-total">Total: ₹ <?php echo number_format($total,2); ?></div>
    </div>

    <div>
      <h3>Shipping details</h3>
      <form method="post">
        <textarea name="address" placeholder="Full shipping address" rows="5" style="width:100%"></textarea>
        <button class="btn" type="submit">Place Order</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>
