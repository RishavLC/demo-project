<?php
session_start();
include "../../common/config.php";

$user_id = $_SESSION['user_id'];
$item_id = intval($_POST['item_id']);

// Get winning info
$stmt = $conn->prepare("
    SELECT 
        ai.title,
        (SELECT bid_amount FROM bids WHERE item_id=ai.id ORDER BY bid_amount DESC LIMIT 1) AS amount,
        (SELECT bidder_id FROM bids WHERE item_id=ai.id ORDER BY bid_amount DESC LIMIT 1) AS winner
    FROM auction_items ai
    WHERE ai.id=?
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$stmt->bind_result($title, $amount, $winner);
$stmt->fetch();
$stmt->close();

if ($winner != $user_id) {
    die("Unauthorized access");
}
?>
<link rel="stylesheet" href="payment.css">

<div class="payment-container">

  <div class="payment-header">
    <h2>Secure Payment</h2>
    <small>Powered by Digital Wallet</small>
  </div>

  <form action="payment_submit.php" method="POST">

    <div class="payment-body">
      <input type="hidden" name="item_id" value="<?= $item_id ?>">

      <label>Item Name</label>
      <input type="text" value="<?= htmlspecialchars($title) ?>" readonly>

      <label>Amount (NPR)</label>
      <input type="text" value="Rs. <?= $amount ?>" readonly>

      <label>Payer Name</label>
      <input type="text" name="payer_name" placeholder="Enter your full name" required>

      <label>Remarks</label>
      <textarea name="remarks" placeholder="Optional remarks"></textarea>
    </div>

    <div class="payment-footer">
      <button class="pay-btn">Confirm & Pay</button>
      <div class="secure-text">
        🔒 Secured by <span>SSL Encryption</span>
      </div>
    </div>

  </form>
</div>
