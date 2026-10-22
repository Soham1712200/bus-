<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed");
}

$customer = trim($_POST["customer"] ?? "");
$amount = filter_var($_POST["amount"] ?? null, FILTER_VALIDATE_FLOAT);
$method = $_POST["method"] ?? "";
$testStatus = $_POST["test_status"] ?? "";
$allowedMethods = ["UPI", "Card", "Net Banking"];
$allowedStatuses = ["success", "failed"];

if ($customer === "" || strlen($customer) > 100 || $amount === false || $amount < 1 || $amount > 100000 || !in_array($method, $allowedMethods, true) || !in_array($testStatus, $allowedStatuses, true)) {
    http_response_code(400);
    exit("Invalid payment details.");
}

$transactionId = "TEST" . strtoupper(bin2hex(random_bytes(6)));
$_SESSION["payment"] = [
    "customer" => $customer,
    "amount" => number_format($amount, 2, ".", ""),
    "method" => $method,
    "status" => $testStatus,
    "transaction_id" => $transactionId,
    "time" => date("Y-m-d H:i:s")
];

if (isset($_POST['bus_id']) && !empty($_POST['seat_numbers'])) {
    $_SESSION['pending_booking'] = $_POST;
    $_SESSION['pending_booking']['customer'] = $customer;
    $_SESSION['pending_booking']['amount'] = $amount;
    $_SESSION['pending_booking']['transaction_id'] = $transactionId;
    $_SESSION['pending_booking']['method'] = $method;
    $_SESSION['pending_booking']['test_status'] = $testStatus;
}

header("Location: result.php");
exit;
?>
