<?php
session_start();
include("db.php");

// The form uses method="POST"
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

// Get the real product details from the database
$stmt = $conn->prepare("SELECT Fruit, Price, Image FROM fruitsandveggies WHERE Productid = ?");
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
    $orderId = $conn->insert_id;   // 0 if the orders table has no auto-increment id
    $stmt->close();

    // Keep the details so the confirmation page can show them
    $_SESSION["last_order"] = [
        "number"  => $orderId > 0 ? str_pad((string)$orderId, 6, "0", STR_PAD_LEFT) : strtoupper(substr(uniqid(), -6)),
        "product" => $product["Fruit"],
        "image"   => $product["Image"],
        "price"   => (float)$product["Price"],
        "qty"     => $qty,
        "total"   => (float)$total,
        "user"    => $user,
        "contact" => $contact,
        "time"    => date("M d, Y h:i A"),
    ];

    header("Location: order-success.php");
    exit;
}

die("Could not save order: " . $stmt->error . " <a href='index.php'>Go back</a>");