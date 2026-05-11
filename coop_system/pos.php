<?php 
include 'db.php'; 

// PROCESS THE CHECKOUT CART
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $cart = json_decode($_POST['cart_data'], true);
    $payment = $conn->real_escape_string($_POST['payment_method']);
    $receipt = $conn->real_escape_string($_POST['receipt_no']); // NEW: Capture Receipt
    $date = date('Y-m-d');

    if (!empty($cart)) {
        foreach ($cart as $item) {
            $id = (int)$item['id'];
            $qty = (int)$item['qty'];
            
            // 1. Deduct from master inventory
            $conn->query("UPDATE inventory SET current_quantity = current_quantity - $qty WHERE product_id=$id");
            
            // 2. Record the sale with the payment method AND receipt number
            $conn->query("INSERT INTO inventory_outsourcing (record_date, product_id, quantity_out, payment_method, receipt_no) 
                          VALUES ('$date', $id, $qty, '$payment', '$receipt')");
        }
        echo "<script>alert('Checkout Successful!'); window.location.href='outsourcing_report.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell / Outsource - Coop DBMS</title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container">
                <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo">
            </div>
            
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn active">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Point of Sale & Outsourcing</h1>
            </div>

            <div class="pos-layout">
                <!-- LEFT: PRODUCT GRID -->
                <div class="products-area">
                    <?php
                    $res = $conn->query("SELECT * FROM inventory WHERE current_quantity > 0 ORDER BY product_name ASC");
                    
                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            echo "
                            <div class='product-card'>
                                <h4 style='text-transform: capitalize;'>" . htmlspecialchars($row['product_name']) . "</h4>
                                <div style='font-size: 12px; color: #888; margin-bottom: 5px;'>" . htmlspecialchars($row['product_type']) . "</div>
                                <p>₱" . number_format($row['price'], 2) . "</p>
                                <div style='font-size: 12px; margin-bottom: 10px;'>Stock: {$row['current_quantity']} {$row['quantity_type']}s</div>
                                <button type='button' class='btn btn-secondary' style='width: 100%; border-color: #6a1b9a; color: #6a1b9a;' 
                                    onclick='addToCart({$row['product_id']}, \"" . addslashes($row['product_name']) . "\", {$row['price']}, {$row['current_quantity']})'>
                                    + ADD TO CART
                                </button>
                            </div>";
                        }
                    } else {
                        echo "<p style='grid-column: span 3; color: #888;'>No products currently in stock. Please add inventory first.</p>";
                    }
                    ?>
                </div>

                <!-- RIGHT: THE CART -->
                <div class="cart-area">
                    <h3 style="margin-bottom: 20px; border-bottom: 2px solid #6a1b9a; padding-bottom: 10px; color: #6a1b9a;">Current Cart</h3>
                    
                    <div id="cart-container">
                        <p style="color: #888; text-align: center; font-size: 14px;">Cart is empty</p>
                    </div>

                    <div class="cart-total">Total: ₱<span id="cart-total-price">0.00</span></div>

                    <form action="pos.php" method="POST" id="checkoutForm">
                        <input type="hidden" name="checkout" value="1">
                        <input type="hidden" name="cart_data" id="cart_data">
                        
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label>Payment Method</label>
                            <select name="payment_method" id="payment_method" required style="font-weight: bold; border-color: #2e7d32; padding: 12px; border-radius: 6px; width: 100%;">
                                <option value="Cash">Cash Payment</option>
                                <option value="GCash">GCash Transfer</option>
                            </select>
                        </div>

                        <!-- NEW: Dynamic Receipt / Reference Number Field -->
                        <div class="input-group" style="margin-bottom: 20px;">
                            <label id="receipt_label" style="color: #d32f2f; font-weight: bold;">Reference No. or Invoice *</label>
                            <input type="text" name="receipt_no" id="receipt_no" placeholder="Enter number here..." required style="padding: 12px; border-radius: 6px; width: 100%; border: 1px solid #ccc; font-weight: bold;">
                        </div>

                        <button type="button" class="btn btn-primary" style="width: 100%; background-color: #2e7d32; font-size: 16px; padding: 15px;" onclick="processCheckout()">CONFIRM CHECKOUT</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let cart = {};

        // Dynamic Label logic for GCash vs Cash
        document.getElementById('payment_method').addEventListener('change', function() {
            const label = document.getElementById('receipt_label');
            if (this.value === 'GCash') {
                label.innerText = 'Reference No. *';
            } else {
                label.innerText = 'Reference No. or Invoice *';
            }
        });

        function addToCart(id, name, price, maxQty) {
            if (cart[id]) {
                if (cart[id].qty < maxQty) {
                    cart[id].qty++;
                } else {
                    alert("Cannot exceed current stock limit of " + maxQty + "!");
                }
            } else {
                cart[id] = { name: name, price: price, qty: 1, max: maxQty };
            }
            renderCart();
        }

        function updateQty(id, newQty) {
            if (newQty > cart[id].max) {
                alert("Cannot exceed current stock (" + cart[id].max + ")!");
                cart[id].qty = cart[id].max;
            } else if (newQty < 1) {
                delete cart[id];
            } else {
                cart[id].qty = parseInt(newQty);
            }
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cart-container');
            const totalEl = document.getElementById('cart-total-price');
            container.innerHTML = '';
            let total = 0;
            let hasItems = false;

            for (let id in cart) {
                hasItems = true;
                const item = cart[id];
                const itemTotal = item.qty * item.price;
                total += itemTotal;

                container.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
                        </div>
                        <div class="cart-controls">
                            <input type="number" value="${item.qty}" min="0" max="${item.max}" onchange="updateQty(${id}, this.value)">
                            <div style="font-weight: bold; width: 60px; text-align: right;">₱${itemTotal.toFixed(2)}</div>
                        </div>
                    </div>
                `;
            }

            if(!hasItems) container.innerHTML = '<p style="color: #888; text-align: center; font-size: 14px;">Cart is empty</p>';
            totalEl.innerText = total.toFixed(2);
        }

        function processCheckout() {
            // Validate Cart
            if (Object.keys(cart).length === 0) {
                alert("Your cart is empty! Please add products first.");
                return;
            }
            // Validate Receipt Number
            const receiptField = document.getElementById('receipt_no');
            if (receiptField.value.trim() === "") {
                alert("Checkout Failed: You must provide a valid Reference or Invoice Number.");
                receiptField.focus();
                return;
            }

            const cartArray = Object.keys(cart).map(id => ({ id: id, qty: cart[id].qty }));
            document.getElementById('cart_data').value = JSON.stringify(cartArray);
            document.getElementById('checkoutForm').submit();
        }
    </script>
</body>
</html>