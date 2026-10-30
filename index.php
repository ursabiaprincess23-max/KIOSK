<?php
include("db.php");

// Skips the stray header row ("Fruit" / "Price") that came from the CSV import.
// Best fix: also delete that row in phpMyAdmin.
$sql = "SELECT * FROM fruitsandveggies WHERE Fruit <> 'Fruit' ORDER BY Productid";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$status = $_GET["status"] ?? "";

// Image folder (URL path and the real folder on disk)
$imgUrlDir  = "images/images/";
$imgDiskDir = __DIR__ . "/images/images/";

// Works whether the Image column has an extension or not (png, jpg, jpeg, webp, gif)
function productImage($name, $urlDir, $diskDir)
{
    $name = trim((string)$name);
    if ($name !== "" && is_file($diskDir . $name)) {
        return $urlDir . rawurlencode($name);
    }
    foreach (["png", "jpg", "jpeg", "webp", "gif", "jfif"] as $ext) {
        if (is_file($diskDir . $name . "." . $ext)) {
            return $urlDir . rawurlencode($name . "." . $ext);
        }
    }
    return $urlDir . "no-image.png";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palengke Kiosk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8d7da; }

        .navbar-brand img { width: 50px; height: 50px; object-fit: contain; margin-right: 10px; }

        .product-card {
            height: 100%;
            transition: transform .2s ease;
        }
        .product-card:hover { transform: translateY(-4px); }

        .product-img {
            width: 100%;
            height: 190px;
            object-fit: contain;
            padding: 15px;
        }

        .modal-product-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            padding: 15px;
        }

        .btn-order { background-color: #dc3545; color: #fff; }
        .btn-order:hover, .btn-order:focus-visible { background-color: #bb2d3b; color: #fff; }

        .total-amount { color: #dc3545; }

        footer {
            background-color: #343a40;
            color: #fff;
            padding: 20px;
            text-align: center;
            margin-top: 50px;
        }
        footer a { color: #fff; text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        @media (prefers-reduced-motion: reduce) {
            .product-card { transition: none; }
            .product-card:hover { transform: none; }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-light border-bottom border-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="<?= $imgUrlDir ?>logo.png" alt="" onerror="this.style.display='none'">
                <span class="h4 mb-0">Palengke Kiosk</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ORDER MESSAGE -->
    <?php if ($status === "success") { ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Order placed. Thank you!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php } ?>

    <!-- PRODUCTS -->
    <div class="container mt-5">
        <h2 class="text-center mb-4">Fresh Fruits and Vegetables</h2>

        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $id       = (int)$row["Productid"];
                $name     = htmlspecialchars($row["Fruit"]);
                $rawPrice = (float)$row["Price"];
                $price    = number_format($rawPrice, 2);
                $imagePath = productImage($row["Image"], $imgUrlDir, $imgDiskDir);
            ?>
                <div class="col-6 col-md-4 col-lg-3 mb-4">

                    <div class="card product-card border-dark rounded-4 shadow-sm">
                        <img src="<?= htmlspecialchars($imagePath) ?>"
                             alt="<?= $name ?>"
                             class="card-img-top product-img"
                             onerror="this.onerror=null;this.src='<?= $imgUrlDir ?>no-image.png';">

                        <div class="card-body d-flex justify-content-between align-items-center">
                            <span class="fs-5 fw-bold">₱<?= $price ?></span>
                            <span class="fs-5"><?= $name ?></span>
                        </div>

                        <div class="container mb-3">
                            <button class="btn btn-order w-100 rounded-pill"
                                    data-bs-toggle="modal"
                                    data-bs-target="#product-id-<?= $id ?>">
                                Add to Cart
                            </button>
                        </div>
                    </div>

                    <!-- ORDER MODAL -->
                    <div class="modal fade" id="product-id-<?= $id ?>" tabindex="-1" aria-labelledby="title-<?= $id ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">

                                <div class="modal-header bg-danger-subtle">
                                    <h5 class="modal-title" id="title-<?= $id ?>">Order <?= $name ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body bg-danger-subtle">
                                    <div class="card border-dark rounded-4">
                                        <img src="<?= htmlspecialchars($imagePath) ?>"
                                             alt="<?= $name ?>"
                                             class="modal-product-img"
                                             onerror="this.onerror=null;this.src='<?= $imgUrlDir ?>no-image.png';">

                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="fs-5 fw-bold">₱<?= $price ?></span>
                                                <span class="fs-5"><?= $name ?></span>
                                            </div>

                                            <form action="insert.php" method="POST" class="order-form" data-price="<?= $rawPrice ?>">
                                                <input type="hidden" name="product_id" value="<?= $id ?>">

                                                <div class="input-group mb-3">
                                                    <span class="input-group-text">Quantity</span>
                                                    <input type="number" class="form-control qty-input" name="Qty" min="1" value="1" required>
                                                </div>

                                                <div class="input-group mb-3">
                                                    <span class="input-group-text">Buyer's Name</span>
                                                    <input type="text" class="form-control" name="User" required>
                                                </div>

                                                <div class="input-group mb-3">
                                                    <span class="input-group-text">Mobile Number</span>
                                                    <input type="tel" class="form-control" name="Contact"
                                                           placeholder="09123456789" pattern="[0-9+\s\-]{10,15}" required>
                                                </div>

                                                <div class="d-flex justify-content-between mb-3 fs-5">
                                                    <span>Total</span>
                                                    <strong class="total-amount">₱<?= $price ?></strong>
                                                </div>

                                                <button type="submit" class="btn btn-order w-100 rounded-pill">Submit Order</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ABOUT US -->
    <section id="about" class="container mt-5">
        <h2 class="text-center mb-3">About Us</h2>
        <p class="text-center">
            Palengke Kiosk is your local marketplace where convenience meets community.
            We bring fresh and affordable goods straight from the market to your fingertips.
        </p>
    </section>

    <!-- CONTACT US -->
    <section id="contact" class="container mt-5 mb-5">
        <h2 class="text-center mb-3">Contact Us</h2>
        <p class="text-center">Have questions or feedback? We'd love to hear from you!</p>
        <div class="text-center">
            <p>Email: <a href="mailto:contact@palengkekiosk.com">contact@palengkekiosk.com</a></p>
            <p>Phone: <a href="tel:+639123456789">+63 912 345 6789</a></p>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p class="mb-1">&copy; <?= date("Y") ?> Palengke Kiosk. All Rights Reserved.</p>
            <p class="mb-0">
                <a href="#">Privacy Policy</a> |
                <a href="#">Terms of Service</a> |
                <a href="mailto:contact@palengkekiosk.com">Contact Us</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live total in each order modal
        document.querySelectorAll(".order-form").forEach(function (form) {
            var price = parseFloat(form.dataset.price) || 0;
            var qty = form.querySelector(".qty-input");
            var total = form.querySelector(".total-amount");
            qty.addEventListener("input", function () {
                var q = Math.max(parseInt(qty.value, 10) || 0, 0);
                total.textContent = "₱" + (price * q).toLocaleString("en-PH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });
        });
    </script>
</body>

</html>