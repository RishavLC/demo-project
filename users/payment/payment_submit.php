<?php
session_start();
include "../../common/config.php";

$user_id = $_SESSION['user_id'];
$item_id = intval($_POST['item_id']);
$payer   = trim($_POST['payer_name']);
$remarks = trim($_POST['remarks']);

// Get final amount
$stmt = $conn->prepare("
    SELECT bid_amount FROM bids
    WHERE item_id=? ORDER BY bid_amount DESC LIMIT 1
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$stmt->bind_result($amount);
$stmt->fetch();
$stmt->close();

$voucher = "VCH-" . time() . rand(100,999);

$insert = $conn->prepare("
    INSERT INTO payments
    (user_id,item_id,amount,payer_name,remarks,status,voucher_no)
    VALUES (?,?,?,?,?,'success',?)
");
$insert->bind_param(
    "iidsss",
    $user_id,
    $item_id,
    $amount,
    $payer,
    $remarks,
    $voucher
);
$insert->execute();
$insert->close();

header("Location: payment_voucher.php?v=".$voucher);
exit();
