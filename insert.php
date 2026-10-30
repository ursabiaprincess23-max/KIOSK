<?php
include("db.php");

// The form uses method="POST", so we must read $_POST (the old code only ran for GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$productid = (int)($_POST["product_id"] ?? 0);
$qty       = (int)($_POST["Qty"] ?? 0);
$user      = trim($_POST["User"] ?? "");
$contact   = trim($_POST["Contact"] ?? "");

if ($productid <= 0 || $qty <= 0 || $user === "" || $contact === "") {
    die("Missing or invalid order details. <a href='index.php'>Go back</a>");
}

// Get the real price from the database (table name: fruitsandveggies)
$stmt = $conn->prepare("SELECT Price FROM fruitsandveggies WHERE Productid = ?");
$stmt->bind_param("i", $productid);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found. <a href='index.php'>Go back</a>");
}

$total = $product["Price"] * $qty;

// Save the order (table name: orders)
$stmt = $conn->prepare("INSERT INTO orders (Productid, Qty, Total, `User`, Contact) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("iidss", $productid, $qty, $total, $user, $contact);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: index.php?status=success");
    exit;
}

// If it fails, show the real reason instead of hiding it
die("Could not save order: " . $stmt->error . " <a href='index.php'>Go back</a>");