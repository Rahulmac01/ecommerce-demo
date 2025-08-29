<?php
// cart.php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$uid = $_SESSION['user_id'];

// update quantities or remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['qty'] as $cart_id => $q) {
            $q = max(1, (int)$q);
            $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
            $stmt->bind_param('iii', $q, $cart_id, $uid);
            $stmt->execute(); $stmt->close();
        }
    } elseif (isset($_POST['remove'])) {
        $cid = (int)$_POST['remove'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $cid, $uid);
        $stmt->execute(); $stmt->close();
    } elseif (isset($_POST['clear'])) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
        $stmt->bind_param('i', $uid);
        $stmt->execute(); $stmt->close();
    }
    header('Location: cart.php'); exit();
}

// fetch cart rows with product info
$stmt = $conn->prepare("SELECT c.id AS cart_id, c.quantity, p.id AS pid, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0;
foreach ($items as $it) $total += $it['price'] * $it['quantity'];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Cart — MyShop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="nav">
  <div class="container">
    <a class="brand" href="index.php">MyShop</a>
    <div class="nav-right">
      <a href="index.php" class="btn-link">Continue shopping</a>
      <a href="logout.php" class="btn-link">Logout</a>
    </div>
  </div>
</header>

<main class="container">
  <h1>Your Cart</h1>
  <?php if (empty($items)): ?>
    <p>Your cart is empty. <a href="index.php">Shop now</a></p>
  <?php else: ?>
    <form method="post">
      <table class="cart-table">
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td class="prod">
              <img src="<?php echo htmlspecialchars($it['image']); ?>" alt="" style="width:80px">
              <div><?php echo htmlspecialchars($it['name']); ?></div>
            </td>
            <td>₹ <?php echo number_format($it['price'],2); ?></td>
            <td><input type="number" name="qty[<?php echo $it['cart_id']; ?>]" value="<?php echo $it['quantity']; ?>" min="1" style="width:70px"></td>
            <td>₹ <?php echo number_format($it['price'] * $it['quantity'],2); ?></td>
            <td><button name="remove" value="<?php echo $it['cart_id']; ?>" class="btn-link">Remove</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cart-actions">
        <div>Total: <strong>₹ <?php echo number_format($total,2); ?></strong></div>
        <div>
          <button type="submit" name="update" class="btn">Update Cart</button>
          <button type="submit" name="clear" class="btn outline">Clear Cart</button>
          <a href="checkout.php" class="btn">Proceed to Checkout</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
