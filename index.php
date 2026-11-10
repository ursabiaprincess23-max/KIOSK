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

// One combined list so every product (and its order modal) is printed only once
$allItems = [];
foreach ($fruits as $r)  { $allItems[] = ["row" => $r, "cat" => "fruit"]; }
foreach ($veggies as $r) { $allItems[] = ["row" => $r, "cat" => "veg"]; }
usort($allItems, function ($a, $b) { return strcasecmp($a["row"]["Fruit"], $b["row"]["Fruit"]); });

// Image folder (URL path and the real folder on disk)
$imgUrlDir  = "images/images/";
$imgDiskDir = __DIR__ . "/images/images/";

// Works whether the Image column has an extension or not
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
        :root {
            --shop: #ee4d2d;
            --shop-dark: #d73211;
            --shop-light: #fff0ec;
            --bg: #f5f5f5;
            --line: #e8e8e8;
            --text: #222;
            --muted: #757575;
        }
        html { scroll-behavior: smooth; scroll-padding-top: 130px; }
        body { background: var(--bg); color: var(--text); font-family: "Helvetica Neue", Helvetica, Arial, "Roboto", sans-serif; }

        /* ---------- HEADER ---------- */
        .shop-header { background: linear-gradient(-180deg, #f53d2d, #f63); color: #fff; position: sticky; top: 0; z-index: 1030; }
        .top-links { font-size: .8rem; padding: 6px 0; }
        .top-links a { color: #fff; text-decoration: none; margin-left: 14px; }
        .top-links a:hover { opacity: .8; }

        .header-main { padding: 10px 0 14px; }
        .brand { display: flex; align-items: center; gap: 8px; color: #fff; text-decoration: none; font-size: 1.5rem; font-weight: 700; white-space: nowrap; }
        .brand img { width: 40px; height: 40px; object-fit: contain; background: #fff; border-radius: 8px; padding: 2px; }
        .brand:hover { color: #fff; }

        .search-box { display: flex; background: #fff; border-radius: 3px; padding: 3px; box-shadow: 0 1px 2px rgba(0,0,0,.12); }
        .search-box input { flex: 1; border: 0; outline: 0; padding: 8px 12px; font-size: .95rem; background: transparent; min-width: 0; }
        .search-box button { background: var(--shop); color: #fff; border: 0; border-radius: 2px; padding: 0 22px; }
        .search-box button:hover { background: var(--shop-dark); }

        /* ---------- BANNER ---------- */
        .banner { background: #fff; padding: 20px 0; }
        .hero { border-radius: 4px; background: linear-gradient(120deg, #ee4d2d 0%, #ff8a3d 100%); color: #fff; padding: 36px 30px; height: 100%; min-height: 190px; display: flex; flex-direction: column; justify-content: center; }
        .hero h1 { font-size: 2rem; font-weight: 800; margin: 0 0 6px; }
        .hero p { margin: 0 0 14px; opacity: .95; }
        .hero .btn-hero { align-self: flex-start; background: #fff; color: var(--shop); font-weight: 700; border-radius: 2px; padding: 8px 22px; }
        .promo { border-radius: 4px; padding: 14px 18px; color: #fff; font-weight: 600; display: flex; align-items: center; min-height: 90px; }
        .promo.p1 { background: #26aa99; }
        .promo.p2 { background: #f5a623; }

        .perks { background: #fff; border-top: 1px solid var(--line); }
        .perks .perk { text-align: center; padding: 12px 6px; font-size: .85rem; color: var(--muted); }
        .perks .perk strong { display: block; color: var(--text); font-size: .9rem; }

        /* ---------- SECTION HEADERS ---------- */
        .section-bar { background: #fff; border-bottom: 1px solid var(--line); }
        .section-title { color: var(--shop); font-size: 1rem; font-weight: 600; padding: 16px 20px; margin: 0; }

        .products-search { margin-bottom: 10px; }
        .products-search .form-control { border-radius: 3px; border: 1px solid var(--line); padding: 12px 16px; }
        .products-search .form-control:focus { border-color: var(--shop); box-shadow: 0 0 0 .2rem rgba(238,77,45,.2); }

        .shop-tabs { display: flex; background: #fff; border-radius: 3px; box-shadow: 0 1px 1px rgba(0,0,0,.05); position: sticky; top: 112px; z-index: 1020; margin-bottom: 14px; }
        .shop-tabs .tab-btn { flex: 1; background: none; border: 0; border-bottom: 3px solid transparent; padding: 16px 8px; font-size: 1rem; text-transform: uppercase; color: var(--text); }
        .shop-tabs .tab-btn:hover { color: var(--shop); }
        .shop-tabs .tab-btn.active { color: var(--shop); border-bottom-color: var(--shop); font-weight: 600; }
        .shop-tabs .count { color: var(--muted); font-size: .8rem; margin-left: 4px; }

        /* ---------- PRODUCT CARD ---------- */
        .product-card { background: #fff; border: 1px solid transparent; border-radius: 2px; height: 100%; display: flex; flex-direction: column; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); transition: transform .1s ease, box-shadow .1s ease; }
        .product-card:hover { border-color: var(--shop); transform: translateY(-1px); box-shadow: 0 1px 20px 0 rgba(0,0,0,.05); }
        .product-img { width: 100%; aspect-ratio: 1 / 1; object-fit: contain; padding: 12px; background: #fafafa; }
        .product-info { padding: 10px 10px 4px; flex: 1; }
        .product-name { font-size: .9rem; line-height: 1.3; height: 2.6em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 8px; }
        .price { color: var(--shop); font-size: 1.1rem; font-weight: 500; }
        .price small { font-size: .75rem; }
        .tag-fresh { display: inline-block; font-size: .65rem; color: var(--shop); border: 1px solid var(--shop); padding: 0 4px; border-radius: 2px; margin-top: 4px; }
        .product-actions { padding: 8px 10px 10px; }

        .btn-shop { background: var(--shop); color: #fff; border: 1px solid var(--shop); border-radius: 2px; font-weight: 500; }
        .btn-shop:hover, .btn-shop:focus-visible { background: var(--shop-dark); border-color: var(--shop-dark); color: #fff; }
        .btn-shop-outline { background: var(--shop-light); color: var(--shop); border: 1px solid var(--shop); border-radius: 2px; font-weight: 500; }
        .btn-shop-outline:hover { background: #ffe5de; color: var(--shop); border-color: var(--shop); }

        .no-results { background: #fff; text-align: center; padding: 60px 20px; color: var(--muted); display: none; }

        /* ---------- MODAL ---------- */
        .modal-content { border-radius: 4px; border: 0; }
        .modal-header { border-bottom: 1px solid var(--line); }
        .modal-product-img { width: 100%; height: 190px; object-fit: contain; background: #fafafa; padding: 12px; border-radius: 3px; }
        .form-control:focus { border-color: var(--shop); box-shadow: 0 0 0 .2rem rgba(238,77,45,.2); }
        .input-group-text { background: #fafafa; font-size: .9rem; }
        .qty-wrap { display: inline-flex; border: 1px solid var(--line); border-radius: 2px; }
        .qty-wrap button { width: 34px; border: 0; background: #fff; font-size: 1.1rem; }
        .qty-wrap input { width: 54px; text-align: center; border: 0; border-left: 1px solid var(--line); border-right: 1px solid var(--line); outline: 0; }

        /* ---------- INFO SECTIONS + FOOTER ---------- */
        .info-box { background: #fff; padding: 28px 20px; border-radius: 3px; }
        footer { background: #fff; border-top: 4px solid var(--shop); margin-top: 50px; padding: 30px 0 20px; color: var(--muted); font-size: .85rem; }
        footer a { color: var(--muted); text-decoration: none; }
        footer a:hover { color: var(--shop); }

        @media (max-width: 767px) {
            .hero h1 { font-size: 1.4rem; }
            .shop-tabs { top: 104px; }
            .brand span { display: none; }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header class="shop-header">
        <div class="container">
            <div class="top-links d-none d-md-flex justify-content-between">
                <span>Fresh from the palengke to your door</span>
                <span>
                    <a href="#about">About Us</a>
                    <a href="#contact">Contact Us</a>
                </span>
            </div>

            <div class="header-main d-flex align-items-center gap-3">
                <a class="brand" href="#">
                    <img src="<?= $imgUrlDir ?>logo.png" alt="" onerror="this.style.display='none'">
                    <span>Palengke Kiosk</span>
                </a>

                <div class="search-box flex-grow-1">
                    <input type="search" id="searchInput" placeholder="Search for fruits and vegetables" autocomplete="off" aria-label="Search products">
                    <button type="button" aria-label="Search">&#128269;</button>
                </div>
            </div>
        </div>
    </header>

    <!-- ORDER MESSAGE -->
    <?php if ($status === "success") { ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                Order placed. Thank you!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php } ?>

    <!-- BANNER -->
    <section class="banner">
        <div class="container">
            <div class="row g-2">
                <div class="col-lg-8">
                    <div class="hero">
                        <h1>Fresh Picks Every Day</h1>
                        <p>Fruits and vegetables straight from the market.</p>
                        <a href="#products" class="btn btn-hero">Shop Now</a>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-flex flex-column gap-2">
                    <div class="promo p1">Fresh from the palengke</div>
                    <div class="promo p2">Affordable local prices</div>
                </div>
            </div>
        </div>
    </section>

    <div class="perks">
        <div class="container">
            <div class="row g-0">
                <div class="col-4 perk"><strong>Fresh Goods</strong>Picked daily</div>
                <div class="col-4 perk"><strong>Low Prices</strong>Palengke rates</div>
                <div class="col-4 perk"><strong>Easy Ordering</strong>Just add your details</div>
            </div>
        </div>
    </div>

    <!-- PRODUCTS -->
    <main class="container mt-4" id="products">

        <div class="products-search">
            <input type="search" id="searchInput2" class="form-control" placeholder="Search fruits and vegetables here" autocomplete="off" aria-label="Search products">
        </div>

        <div class="shop-tabs" role="tablist">
            <button class="tab-btn active" data-filter="all" type="button">
                All <span class="count">(<?= count($allItems) ?>)</span>
            </button>
            <button class="tab-btn" data-filter="fruit" type="button">
                Fruits <span class="count">(<?= count($fruits) ?>)</span>
            </button>
            <button class="tab-btn" data-filter="veg" type="button">
                Vegetables <span class="count">(<?= count($veggies) ?>)</span>
            </button>
        </div>

        <div class="row g-2 product-grid">
        <?php foreach ($allItems as $item) {
            $row       = $item["row"];
            $id        = (int)$row["Productid"];
            $name      = htmlspecialchars($row["Fruit"]);
            $rawPrice  = (float)$row["Price"];
            $price     = number_format($rawPrice, 2);
            $imagePath = productImage($row["Image"], $imgUrlDir, $imgDiskDir);
        ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2 product-col" data-name="<?= strtolower($name) ?>" data-cat="<?= $item["cat"] ?>">
                <div class="product-card">
                    <img src="<?= htmlspecialchars($imagePath) ?>"
                         alt="<?= $name ?>"
                         class="product-img"
                         loading="lazy"
                         onerror="this.onerror=null;this.src='<?= $imgUrlDir ?>no-image.png';">

                    <div class="product-info">
                        <div class="product-name"><?= $name ?></div>
                        <div class="price"><small>₱</small><?= $price ?></div>
                        <span class="tag-fresh">Fresh</span>
                    </div>

                    <div class="product-actions">
                        <button class="btn btn-shop w-100 btn-sm py-2"
                                data-bs-toggle="modal"
                                data-bs-target="#product-id-<?= $id ?>">
                            Buy Now
                        </button>
                    </div>
                </div>

                <!-- ORDER MODAL -->
                <div class="modal fade" id="product-id-<?= $id ?>" tabindex="-1" aria-labelledby="title-<?= $id ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title" id="title-<?= $id ?>">Order <?= $name ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="<?= $name ?>"
                                     class="modal-product-img mb-3"
                                     onerror="this.onerror=null;this.src='<?= $imgUrlDir ?>no-image.png';">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-5"><?= $name ?></span>
                                    <span class="price fs-4">₱<?= $price ?></span>
                                </div>

                                <form action="insert.php" method="POST" class="order-form" data-price="<?= $rawPrice ?>">
                                    <input type="hidden" name="product_id" value="<?= $id ?>">

                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="text-muted">Quantity</span>
                                        <div class="qty-wrap">
                                            <button type="button" class="qty-minus" aria-label="Decrease">&minus;</button>
                                            <input type="number" class="qty-input" name="Qty" min="1" value="1" required>
                                            <button type="button" class="qty-plus" aria-label="Increase">+</button>
                                        </div>
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
                                        <strong class="price total-amount">₱<?= $price ?></strong>
                                    </div>

                                    <button type="submit" class="btn btn-shop w-100 py-2">Place Order</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        </div>

        <div class="no-results" id="noResults">No products found. Try a different keyword.</div>
    </main>

    <!-- ABOUT US -->
    <section id="about" class="container mt-5">
        <div class="info-box text-center">
            <h2 class="h4 mb-3">About Us</h2>
            <p class="mb-0">
                Palengke Kiosk is your local marketplace where convenience meets community.
                We bring fresh and affordable goods straight from the market to your fingertips.
            </p>
        </div>
    </section>

    <!-- CONTACT US -->
    <section id="contact" class="container mt-3">
        <div class="info-box text-center">
            <h2 class="h4 mb-3">Contact Us</h2>
            <p>Have questions or feedback? We'd love to hear from you!</p>
            <p class="mb-1">Email: <a href="mailto:contact@palengkekiosk.com">contact@palengkekiosk.com</a></p>
            <p class="mb-0">Phone: <a href="tel:+639123456789">+63 912 345 6789</a></p>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container text-center">
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
        // Live total + quantity buttons in each order modal
        document.querySelectorAll(".order-form").forEach(function (form) {
            var price = parseFloat(form.dataset.price) || 0;
            var qty = form.querySelector(".qty-input");
            var total = form.querySelector(".total-amount");

            function update() {
                var q = Math.max(parseInt(qty.value, 10) || 0, 0);
                total.textContent = "₱" + (price * q).toLocaleString("en-PH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            qty.addEventListener("input", update);
            form.querySelector(".qty-minus").addEventListener("click", function () {
                qty.value = Math.max((parseInt(qty.value, 10) || 1) - 1, 1);
                update();
            });
            form.querySelector(".qty-plus").addEventListener("click", function () {
                qty.value = (parseInt(qty.value, 10) || 0) + 1;
                update();
            });
        });

        // Category tabs (All / Fruits / Vegetables) + search
        var searchInput = document.getElementById("searchInput");
        var noResults = document.getElementById("noResults");
        var activeFilter = "all";

        function applyFilter() {
            var term = searchInput.value.trim().toLowerCase();
            var visible = 0;
            document.querySelectorAll(".product-col").forEach(function (col) {
                var okCat = activeFilter === "all" || col.dataset.cat === activeFilter;
                var okName = col.dataset.name.indexOf(term) !== -1;
                var show = okCat && okName;
                col.style.display = show ? "" : "none";
                if (show) visible++;
            });
            noResults.style.display = visible === 0 ? "block" : "none";
        }

        var searchInput2 = document.getElementById("searchInput2");
        [searchInput, searchInput2].forEach(function (box) {
            box.addEventListener("input", function () {
                searchInput.value = box.value;
                searchInput2.value = box.value;
                applyFilter();
            });
        });
        document.querySelectorAll(".tab-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                document.querySelectorAll(".tab-btn").forEach(function (b) { b.classList.remove("active"); });
                btn.classList.add("active");
                activeFilter = btn.dataset.filter;
                applyFilter();
            });
        });
    </script>
</body>

</html>