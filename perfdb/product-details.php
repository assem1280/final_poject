<?php
require_once 'connect.php';

// Get product id from query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("HTTP/1.1 400 Bad Request");
    echo "<h2>Invalid product ID</h2><p><a href=\"../selected/selection.html\">Back to selection</a></p>";
    exit;
}

try {
    $query = "SELECT p.p_id, p.p_name, p.price, p.description, p.stock, p.image_url, b.brand_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.p_id = :id LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("HTTP/1.1 404 Not Found");
        echo "<h2>Product not found</h2><p>The requested product does not exist.</p><p><a href=\"../selected/selection.html\">Back to selection</a></p>";
        exit;
    }

} catch (PDOException $e) {
    echo "<h2>Database error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    exit;
}

// image path
$image = isset($product['image_url']) && $product['image_url'] !== '' ? "../images/" . $product['image_url'] : "../images/perfumes/default-perfume.jpg";
$brandName = isset($product['brand_name']) ? $product['brand_name'] : 'Unknown Brand';

// default size handling
$size = isset($_GET['size']) ? intval($_GET['size']) : 50;
if ($size !== 50 && $size !== 100) { $size = 50; }

$basePrice = (float)$product['price'];
// Pricing rule: 100ml adds $50 to base price (base is 50ml)
$displayPrice = $basePrice + ($size === 100 ? 50.00 : 0.00);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($product['p_name']); ?> — Details</title>
<link rel="stylesheet" href="/pefumeppp/assets/cart-widget.css">
<style>
    body {
    font-family: "Poppins", Arial, sans-serif;
    background: #ffffff;
    color: #333333;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

body::before,
body::after {
    content: '';
    position: absolute;
    width: 2px;
    height: 2px;
    background: #000000;
    border-radius: 50%;
    box-shadow: 
        100px 200px #000000, 300px 100px #000000, 500px 300px #000000,
        700px 150px #000000, 200px 400px #000000, 600px 250px #000000,
        150px 50px #000000, 450px 450px #000000, 800px 400px #000000,
        250px 150px #000000, 550px 50px #000000, 350px 350px #000000,
        50px 300px #000000, 650px 100px #000000, 400px 200px #000000,
        750px 350px #000000, 900px 200px #000000, 180px 280px #000000,
        420px 380px #000000, 580px 180px #000000, 720px 480px #000000,
        140px 420px #000000, 480px 120px #000000, 820px 280px #000000,
        280px 80px #000000, 520px 320px #000000, 680px 420px #000000,
        80px 180px #000000, 380px 280px #000000, 680px 80px #000000,
        220px 320px #000000, 560px 420px #000000, 320px 220px #000000,
        760px 120px #000000, 120px 360px #000000, 460px 60px #000000,
        620px 360px #000000, 860px 160px #000000, 190px 490px #000000,
        530px 190px #000000, 690px 290px #000000, 850px 490px #000000,
        90px 90px #000000, 430px 390px #000000, 590px 90px #000000,
        730px 390px #000000, 870px 290px #000000, 210px 110px #000000;
    animation: twinkle 3s infinite alternate;
}

body::after {
    animation-delay: 1.5s;
    box-shadow: 
        120px 220px #000000, 320px 120px #000000, 520px 320px #000000,
        720px 170px #000000, 220px 420px #000000, 620px 270px #000000,
        170px 70px #000000, 470px 470px #000000, 820px 420px #000000,
        270px 170px #000000, 570px 70px #000000, 370px 370px #000000,
        70px 320px #000000, 670px 120px #000000, 420px 220px #000000,
        770px 370px #000000, 920px 220px #000000, 200px 300px #000000,
        440px 400px #000000, 600px 200px #000000, 740px 500px #000000;
}

@keyframes twinkle {
    0% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.2); }
    100% { opacity: 0.3; transform: scale(1); }
}

    .container{
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        gap: 24px;
        align-items: flex-start;
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
    }
    .image{flex:1}
    .image img{
        width:100%;
        height:auto;
        border-radius:12px;
        box-shadow:0 8px 20px rgba(0, 0, 0, 0.15);
    }
    .details{flex:1.1}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:8px}
    .brand img{width:60px;height:60px;object-fit:contain}
    h1{margin:0 0 8px 0;font-size:26px;color:#000000}
    .price{font-size:22px;color:#000000;margin:8px 0}
    .desc{
        margin:14px 0;
        color:#555555;
        line-height:1.7;
        font-family: 'Playfair Display', serif;
        font-size:17px;
        letter-spacing: 0.3px;
    }
    label{display:block;margin-top:16px;font-weight:600;color:#333333}
    
    .size-options{
        display: flex;
        gap: 10px;
        margin-top: 8px;
    }
    .size-btn{
        background: #ffffff;
        border: 2px solid #000000;
        color: #000000;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .size-btn.active,
    .size-btn:hover{
        background: #000000;
        color: #ffffff;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.3);
    }

    input[type=number]{
        padding:8px;
        border-radius:6px;
        border:1px solid #cccccc;
        background:#ffffff;
        color:#000000;
        margin-top:6px;
    }

    .actions{margin-top:22px;display:flex;gap:10px;flex-wrap:wrap}
    button.add {
    background: #000000;
    color: #ffffff;
    padding: 12px 22px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}
button.add:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
    background: #222222;
}

    .back {
    display: inline-block;
    margin-bottom: 22px;
    text-decoration: none;
    font-weight: 600;
    padding: 12px 22px;
    border-radius: 10px;
    background: #ffffff;
    color: #000000;
    border: 1px solid #000000;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    transition: all 0.35s ease;
    position: relative;
    overflow: hidden;
}

.back:hover {
    color: #ffffff;
    border-color: #000000;
    background: #000000;
    box-shadow: 0 0 25px rgba(0, 0, 0, 0.3);
    transform: translateY(-2px) scale(1.03);
}

.back::after {
    content: "";
    position: absolute;
    top: 0;
    left: -75%;
    width: 50%;
    height: 100%;
    background: linear-gradient(120deg, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.0));
    transform: skewX(-25deg);
    transition: all 0.6s ease;
}

.back:hover::after {
    left: 130%;
}

</style>
</head>
<body>
<a class="back" href="javascript:history.back()">← Back to selection</a>

<div class="container">
    <div class="image">
        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['p_name']); ?>">
    </div>
    <div class="details">
        <div class="brand">
            <img src="../images/Untitled_design-removebg-preview.png" alt="brand logo">
            <div>
                <div style="font-size:13px;color:#aaa">Brand</div>
                <div style="font-weight:700;color:#fff"><?php echo htmlspecialchars($brandName); ?></div>
            </div>
        </div>

        <h1><?php echo htmlspecialchars($product['p_name']); ?></h1>
        <div class="price" id="price">$<?php echo number_format($displayPrice,2); ?></div>
        <div class="desc"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></div>

        <label>Size</label>
        <div class="size-options">
            <button class="size-btn <?php echo $size===50 ? 'active':''; ?>" data-size="50">50 ml</button>
            <button class="size-btn <?php echo $size===100 ? 'active':''; ?>" data-size="100">100 ml</button>
        </div>

        <label for="quantity">Quantity</label>
        <input id="quantity" type="number" min="1" value="1" style="width:96px">

        <div class="actions">
    <button id="addToCart" class="add">
        🛒 Add to cart
    </button>
</div>

    </div>
</div>

<link rel="stylesheet" href="/pefumeppp/assets/cart-widget.css">
<script src="/pefumeppp/assets/cart-widget.js" defer></script>

<script>
   const productId = <?php echo intval($product['p_id']); ?>;
const basePrice = <?php echo json_encode($basePrice); ?>;
const priceEl = document.getElementById('price');
const sizeBtns = document.querySelectorAll('.size-btn');
const quantityInput = document.getElementById('quantity');

let selectedSize = <?php echo $size; ?>;

// دالة لحساب السعر الكلي
function updatePrice() {
    const quantity = parseInt(quantityInput.value, 10) || 1; // قراءة الكمية
    const sizePrice = selectedSize === 100 ? 50.00 : 0.00; // زيادة السعر للـ100ml
    const totalPrice = (basePrice + sizePrice) * quantity;
    priceEl.textContent = '$' + totalPrice.toFixed(2); // تحديث السعر في الصفحة
}

// عند الضغط على أزرار الحجم
sizeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        sizeBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedSize = parseInt(btn.dataset.size, 10);
        updatePrice(); // تحديث السعر بعد تغيير الحجم
    });
});

// عند تغيير الكمية
quantityInput.addEventListener('input', updatePrice);

// إضافة للعربة
async function addToCart() {
    const quantity = parseInt(quantityInput.value, 10) || 1;
    try {
        const resp = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: quantity, size: selectedSize })
        });
        const data = await resp.json();
        if (data.success) {
            alert('تم الإضافة للعربة: ' + <?php echo json_encode($product['p_name']); ?> + '\nالحجم: ' + selectedSize + ' ml');
            // Refresh cart badge immediately
            if (window.refreshCartBadges) try { window.refreshCartBadges(); } catch(e) { console.warn('refreshCartBadges failed', e); }
            if (window.dispatchEvent) try { window.dispatchEvent(new Event('cart:updated')); } catch(e) { /* ignore */ }
        } else {
            alert('فشل الإضافة للعربة: ' + (data.error || 'غير معروف'));
        }
    } catch (err) {
        console.error(err);
        alert('خطأ بالشبكة أثناء الإضافة للعربة');
    }
}

document.getElementById('addToCart').addEventListener('click', addToCart);

// تهيئة السعر عند تحميل الصفحة
updatePrice();


</script>
</body>
</html>
