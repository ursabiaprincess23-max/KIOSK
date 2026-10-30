<?php
include("db.php");

$sql = "SELECT * FROM fruitsandveggies WHERE Fruit <> 'Fruit'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$status = $_GET["status"] ?? "";

$colCheck    = mysqli_query($conn, "SHOW COLUMNS FROM fruitsandveggies LIKE 'Category'");
$hasCategory = $colCheck && mysqli_num_rows($colCheck) > 0;

$vegetableWords = [
    "bitter melon", "ampalaya", "broccoli", "cabbage", "repolyo", "carrot", "cauliflower",
    "eggplant", "talong", "tomato", "squash", "kalabasa", "pumpkin", "okra", "string bean",
    "long bean", "sitaw", "bean", "kangkong", "spinach", "pechay", "lettuce", "onion",
    "sibuyas", "garlic", "bawang", "potato", "patatas", "cucumber", "pipino", "corn",
    "radish", "labanos", "ginger", "pepper", "chili", "siling", "sayote", "upo",
    "malunggay", "kamote", "camote", "gabi", "singkamas", "celery", "mushroom"
];
$vegPattern = "/\\b(" . implode("|", array_map(function ($w) { return preg_quote($w, "/"); }, $vegetableWords)) . ")s?\\b/i";

$fruits = [];
$veggies = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($hasCategory) {
        $isVeg = stripos((string)$row["Category"], "veg") !== false;
    } else {
        $isVeg = preg_match($vegPattern, (string)$row["Fruit"]) === 1;
    }
      if ($isVeg) { $veggies[] = $row; } else { $fruits[] = $row; }
}

// Image folder (URL path and the real folder on disk)
$imgUrlDir  = "images/images/";
$imgDiskDir = __DIR__ . "/images/images/";

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
        :root { --green:#2f7d3b; --green-dark:#17382a; --orange:#c2410c; --bg:#f2f4ef; --line:#d9dfd3; }
        html { scroll-behavior:smooth; scroll-padding-top:96px; }
        body { background:var(--bg); color:#1d2a22; }

        .kiosk-header { background:var(--green-dark); }
        .navbar-brand img { width:52px; height:52px; object-fit:contain; margin-right:10px; border-radius:10px; background:#fff; padding:2px; }
        .navbar-toggler { border-color:rgba(255,255,255,.6); }
        .navbar-toggler-icon { filter:invert(1); }

        .btn-nav { color:#fff; border:2px solid rgba(255,255,255,.45); border-radius:12px; padding:.55rem 1.3rem; font-weight:600; background:transparent; }
        .btn-nav:hover, .btn-nav:focus-visible { background:rgba(255,255,255,.15); color:#fff; border-color:#fff; }
        .btn-nav.active { background:#fff; color:var(--green-dark); border-color:#fff; }

        .category-tabs { gap:12px; }
        .category-tabs .nav-link { background:#fff; color:var(--green-dark); border:2px solid var(--line); border-radius:14px; padding:.9rem 2rem; font-size:1.2rem; font-weight:700; min-width:180px; }
        .category-tabs .nav-link .count { background:var(--bg); color:#55635a; border-radius:999px; padding:.1rem .6rem; font-size:.9rem; margin-left:.4rem; }
        .category-tabs .nav-link.active { background:var(--green); border-color:var(--green); color:#fff; }
        .category-tabs .nav-link.active .count { background:rgba(255,255,255,.25); color:#fff; }

        .card { height:100%; border-color:var(--line) !important; transition:box-shadow .2s ease; }
        .card:hover { box-shadow:0 6px 18px rgba(23,56,42,.12) !important; transform:none; }
        .product-img { width:100%; height:190px; object-fit:contain; padding:15px; }
        .modal-product-img { width:100%; height:200px; object-fit:contain; padding:15px; }

        .btn-order { background:var(--green); color:#fff; font-weight:700; }
        .btn-order:hover, .btn-order:focus-visible { background:#256530; color:#fff; }
        .card-body .fw-bold { color:var(--orange); }

        footer { background:var(--green-dark); color:#d6e3da; padding:20px; text-align:center; margin-top:50px; }
        footer a { color:#fff; text-decoration:none; }
        footer a:hover { text-decoration:underline; }
    </style>
</head>

<body>

    <!-- NAVBAR -->
      <nav class="navbar navbar-expand-lg kiosk-header sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="<?= $imgUrlDir ?>logo.png" alt="" onerror="this.style.display='none'">
                 <span class="h4 mb-0 text-white">Palengke Kiosk</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle menu">
                <span class="navbar-toggler-icon"></span>
            </button>

                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
                <a class="btn btn-nav active" href="#">Home</a>
                <a class="btn btn-nav" href="#about">About Us</a>
                <a class="btn btn-nav" href="#contact">Contact Us</a>
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
        <h2 class="text-center mb-4">What would you like to buy today?</h2>

        <ul class="nav nav-pills category-tabs justify-content-center mb-4">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#fruits" type="button">
                    Fruits <span class="count"><?= count($fruits) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#veggies" type="button">
                    Vegetables <span class="count"><?= count($veggies) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
        <?php foreach (["fruits" => $fruits, "veggies" => $veggies] as $paneId => $items) { ?>
        <div class="tab-pane fade <?= $paneId === "fruits" ? "show active" : "" ?>" id="<?= $paneId ?>">
        <div class="row">
            <?php foreach ($items as $row) {

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

                                <div class="modal-header bg-danger-light">
                                    <h5 class="modal-title" id="title-<?= $id ?>">Order <?= $name ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body bg-danger-light">
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