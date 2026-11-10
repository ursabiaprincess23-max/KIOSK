<?php
session_start();

$order = $_SESSION["last_order"] ?? null;
if (!$order) {
    header("Location: index.php");
    exit;
}

// Cancel Order (only changes what this page shows; see note below)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel"])) {
    $_SESSION["last_order"]["cancelled"] = true;
    header("Location: order-success.php");
    exit;
}
$cancelled = !empty($order["cancelled"]);

$imgUrlDir  = "images/images/";
$imgDiskDir = __DIR__ . "/images/images/";

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

$img = productImage($order["image"], $imgUrlDir, $imgDiskDir);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | Palengke Kiosk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root { --shop:#ee4d2d; --teal:#26aa99; --bg:#f5f5f5; --line:#e8e8e8; --muted:#757575; }
        body { background:var(--bg); font-family:"Helvetica Neue",Helvetica,Arial,sans-serif; color:#222; padding-bottom:130px; }

        .topbar { background:#fff; border-bottom:1px solid var(--line); padding:14px 0; position:sticky; top:0; z-index:10; }
        .topbar a.back { color:var(--shop); text-decoration:none; font-size:1.6rem; line-height:1; margin-right:14px; }
        .topbar .title { font-size:1.15rem; }

        .status { background:var(--teal); color:#fff; padding:20px 18px; font-size:1.2rem; border-radius:4px; }
        .status.cancelled { background:#9e9e9e; }

        .card-box { background:#fff; border-radius:4px; padding:14px 18px; margin-top:10px; }
        .pay-row { display:flex; align-items:center; gap:10px; }
        .pay-icon { color:var(--muted); font-size:1.4rem; }

        .shop-name { font-weight:500; margin-bottom:12px; }
        .item { display:flex; gap:14px; }
        .item-img { width:80px; height:80px; object-fit:contain; background:#fafafa; border:1px solid var(--line); border-radius:3px; }
        .item-name { font-size:.95rem; }
        .item-qty { color:var(--muted); font-size:.85rem; }
        .item-price { text-align:right; align-self:flex-end; }

        .total-line { display:flex; justify-content:flex-end; align-items:center; gap:8px; border-top:1px solid var(--line); margin-top:14px; padding-top:12px; cursor:pointer; background:none; border-left:0; border-right:0; border-bottom:0; width:100%; }
        .total-detail { font-size:.88rem; color:var(--muted); }
        .total-detail div { display:flex; justify-content:space-between; padding:3px 0; }

        .support-title, .section-title { font-size:1.05rem; margin-bottom:6px; }
        .support-link { display:flex; justify-content:space-between; align-items:center; padding:12px 0; color:#222; text-decoration:none; }
        .support-link + .support-link { border-top:1px solid var(--line); }
        .chev { color:var(--muted); }

        .order-id-row { display:flex; justify-content:space-between; align-items:center; }
        .btn-copy { border:1px solid #bbb; background:#fff; border-radius:4px; padding:3px 14px; }
        .btn-copy:hover { border-color:var(--shop); color:var(--shop); }
        .more-row { display:flex; justify-content:space-between; font-size:.9rem; padding:5px 0; color:var(--muted); }
        .more-row span:last-child { color:#222; }

        .bottom-bar { position:fixed; left:0; right:0; bottom:0; background:#fff; border-top:1px solid var(--line); padding:10px 0 14px; z-index:20; }
        .bottom-bar .total { text-align:right; margin-bottom:8px; }
        .price { color:var(--shop); }
        .btn-cancel { width:100%; border:1px solid #bbb; background:#fff; border-radius:6px; padding:12px; font-size:1.05rem; }
        .btn-cancel:hover { border-color:var(--shop); color:var(--shop); }
        .btn-shop { width:100%; background:var(--shop); color:#fff; border:0; border-radius:6px; padding:12px; font-size:1.05rem; display:block; text-align:center; text-decoration:none; }
        .btn-shop:hover { background:#d73211; color:#fff; }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="container d-flex align-items-center" style="max-width:720px;">
            <a href="index.php" class="back" aria-label="Back to shop">&larr;</a>
            <span class="title">Order Details</span>
        </div>
    </div>

    <!-- Popup notification -->
    <?php if (!$cancelled) { ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2000;">
        <div id="orderToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"><strong>Order placed!</strong> Order <?= htmlspecialchars($order["number"]) ?> has been received.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php } ?>

    <main class="container mt-3" style="max-width:720px;">

        <!-- STATUS -->
        <div class="status <?= $cancelled ? "cancelled" : "" ?>">
            <?= $cancelled ? "Order cancelled" : "Waiting for seller to ship" ?>
        </div>

        <!-- PAYMENT -->
        <div class="card-box">
            <div class="pay-row"><span class="pay-icon">&#8369;</span> Pay by Cash on Delivery</div>
        </div>

        <!-- ITEM -->
        <div class="card-box">
            <div class="shop-name">Palengke Kiosk</div>
            <div class="item">
                <img src="<?= htmlspecialchars($img) ?>" class="item-img" alt=""
                     onerror="this.onerror=null;this.src='<?= $imgUrlDir ?>no-image.png';">
                <div class="flex-grow-1">
                    <div class="item-name"><?= htmlspecialchars($order["product"]) ?></div>
                    <div class="item-qty">x<?= (int)$order["qty"] ?></div>
                </div>
                <div class="item-price">₱<?= number_format($order["price"], 2) ?></div>
            </div>

            <button class="total-line" type="button" data-bs-toggle="collapse" data-bs-target="#totalDetail" aria-expanded="false">
                <span>Order Total: <strong>₱<?= number_format($order["total"], 2) ?></strong></span>
                <span class="chev">&#8964;</span>
            </button>
            <div class="collapse total-detail mt-2" id="totalDetail">
                <div><span>Merchandise Subtotal</span><span>₱<?= number_format($order["total"], 2) ?></span></div>
                <div><span>Order Total</span><span>₱<?= number_format($order["total"], 2) ?></span></div>
            </div>
        </div>

        <!-- SUPPORT CENTER -->
        <div class="card-box">
            <div class="support-title">Support Center</div>
            <a class="support-link" href="tel:+639123456789"><span>Contact Seller</span><span class="chev">&rsaquo;</span></a>
            <a class="support-link" href="index.php#contact"><span>Help Center</span><span class="chev">&rsaquo;</span></a>
        </div>

        <!-- ORDER ID -->
        <div class="card-box">
            <div class="order-id-row">
                <span class="fs-6">Order ID</span>
                <span>
                    <span id="orderId"><?= htmlspecialchars($order["number"]) ?></span>
                    <button type="button" class="btn-copy ms-2" id="copyBtn">Copy</button>
                </span>
            </div>

            <div class="collapse mt-3" id="moreInfo">
                <div class="more-row"><span>Order Time</span><span><?= htmlspecialchars($order["time"]) ?></span></div>
                <div class="more-row"><span>Buyer</span><span><?= htmlspecialchars($order["user"]) ?></span></div>
                <div class="more-row"><span>Mobile Number</span><span><?= htmlspecialchars($order["contact"]) ?></span></div>
            </div>

            <hr class="my-3">
            <button class="btn w-100 text-muted p-0" type="button" data-bs-toggle="collapse" data-bs-target="#moreInfo" id="moreBtn">View More &#8964;</button>
        </div>
    </main>

    <!-- BOTTOM BAR -->
    <div class="bottom-bar">
        <div class="container" style="max-width:720px;">
            <div class="total">Total Payment: <strong class="price fs-5">₱<?= number_format($order["total"], 2) ?></strong></div>
            <?php if ($cancelled) { ?>
                <a href="index.php#products" class="btn-shop">Buy Again</a>
            <?php } else { ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    <input type="hidden" name="cancel" value="1">
                    <button type="submit" class="btn-cancel">Cancel Order</button>
                </form>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var toastEl = document.getElementById("orderToast");
        if (toastEl) { new bootstrap.Toast(toastEl, { delay: 5000 }).show(); }

        // Copy order ID
        var copyBtn = document.getElementById("copyBtn");
        copyBtn.addEventListener("click", function () {
            var id = document.getElementById("orderId").textContent.trim();
            var done = function () {
                copyBtn.textContent = "Copied";
                setTimeout(function () { copyBtn.textContent = "Copy"; }, 1500);
            };
            if (navigator.clipboard) { navigator.clipboard.writeText(id).then(done); }
            else { var t = document.createElement("textarea"); t.value = id; document.body.appendChild(t); t.select(); document.execCommand("copy"); t.remove(); done(); }
        });

        // View More / View Less label
        var moreInfo = document.getElementById("moreInfo");
        moreInfo.addEventListener("shown.bs.collapse", function () { document.getElementById("moreBtn").innerHTML = "View Less &#8963;"; });
        moreInfo.addEventListener("hidden.bs.collapse", function () { document.getElementById("moreBtn").innerHTML = "View More &#8964;"; });
    </script>
</body>

</html>