<?php
include "../../common/config.php";

$v = $_GET['v'];

$stmt = $conn->prepare("
    SELECT p.*, ai.title
    FROM payments p
    JOIN auction_items ai ON p.item_id = ai.id
    WHERE voucher_no=?
");
$stmt->bind_param("s", $v);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
?>
<!-- <link rel="stylesheet" href="payment.css"> -->
<style>
    /* RESET */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* PAGE BACKGROUND */
body {
  font-family: Arial, Helvetica, sans-serif;
  background: #f4f6f8;
  color: #333;
}

/* MAIN VOUCHER CARD */
.voucher {
  max-width: 420px;
  margin: 40px auto;
  background: #ffffff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  text-align: center;
}

/* TITLE */
.voucher h2 {
  color: #2ecc71;
  margin-bottom: 20px;
  font-size: 22px;
}

/* DETAILS BOX */
.voucher > div {
  border: 1px solid #ddd !important;
  padding: 18px !important;
  width: 100% !important;
  border-radius: 8px;
  text-align: left;
  font-size: 15px;
}

/* EACH ROW */
.voucher p {
  margin: 8px 0;
  line-height: 1.5;
}

/* BOLD LABELS */
.voucher strong {
  display: inline-block;
  min-width: 110px;
}

/* PRINT BUTTON */
button {
  margin-top: 20px;
  padding: 10px 20px;
  background: #3498db;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
}

button:hover {
  background: #2980b9;
}

/* SUCCESS MESSAGE */
.status {
  margin-top: 15px;
  color: #2ecc71;
  font-weight: bold;
  font-size: 15px;
}

/* PRINT MODE */
@media print {
  body {
    background: none;
  }

  button,
  .status {
    display: none;
  }

  .voucher {
    box-shadow: none;
    border: 1px solid #000;
  }
}

</style>
<div class="voucher">
  <h2>Payment Voucher</h2>
<div style="border:1px solid #000;padding:20px;width:400px">
    <p><strong>Voucher No:</strong> <?= $data['voucher_no'] ?></p>
    <p><strong>Item:</strong> <?= htmlspecialchars($data['title']) ?></p>
    <p><strong>Payer:</strong> <?= htmlspecialchars($data['payer_name']) ?></p>
    <p><strong>Amount:</strong> Rs. <?= $data['amount'] ?></p>
    <p><strong>Status:</strong> ✅ Paid</p>
    <p><strong>Date:</strong> <?= $data['created_at'] ?></p>
</div>

<button onclick="window.print()">Print Voucher</button>
  <p class="status">✔ Payment Successful</p>
</div>


