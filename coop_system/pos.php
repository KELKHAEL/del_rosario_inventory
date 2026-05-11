<?php 
include 'db.php'; 

// Fetch negative stock setting
$setting_res = $conn->query("SELECT setting_value FROM config_inventory_settings WHERE setting_key = 'allow_negative_stock'");
$allow_negative = 0;
if ($setting_res && $setting_res->num_rows > 0) {
    $allow_negative = (int)$setting_res->fetch_assoc()['setting_value'];
}

// PROCESS THE CHECKOUT CART
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $cart = json_decode($_POST['cart_data'], true);
    $payment = $conn->real_escape_string($_POST['payment_method']);
    $receipt = $conn->real_escape_string($_POST['receipt_no']); 
    
    $buyer_name = $conn->real_escape_string($_POST['buyer_name'] ?? ''); 
    $buyer_contact = $conn->real_escape_string($_POST['buyer_contact'] ?? ''); 
    
    $date = date('Y-m-d');

    if (!empty($cart)) {
        foreach ($cart as $item) {
            $id = (int)$item['id'];
            $qty = (int)$item['qty'];
            
            // Deduct from master inventory (negative logic is handled in the frontend UI, DB just executes)
            $conn->query("UPDATE inventory SET current_quantity = current_quantity - $qty WHERE product_id=$id");
            
            $conn->query("INSERT INTO inventory_outsourcing (record_date, product_id, quantity_out, payment_method, receipt_no, buyer_name, buyer_contact) 
                          VALUES ('$date', $id, $qty, '$payment', '$receipt', '$buyer_name', '$buyer_contact')");
        }
        echo "<script>alert('Checkout Successful!'); window.location.href='outsourcing_report.php';</script>";
        exit();
    }
}

// Fetch dynamic unit types from database
$unit_types = [];
$res_units = $conn->query("SELECT name FROM config_unit_types ORDER BY name ASC");
if ($res_units) {
    while($u = $res_units->fetch_assoc()) {
        $unit_types[] = $u['name'];
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
    <style>
        .search-container {
            display: flex; align-items: center; background: #fff; border: 1px solid #ccc;
            border-radius: 6px; padding: 4px; flex: 1; max-width: 300px;
        }
        .search-container input { border: none; outline: none; padding: 8px 10px; width: 100%; font-size: 13px; }
        .search-container span { background: #6a1b9a; color: white; border-radius: 4px; padding: 8px 15px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

    <div class="dashboard-container">
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
                <a href="database_management.php" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-action-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h1 class="page-title">Point of Sale & Outsourcing</h1>
                
                <div class="action-buttons" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    
                    <div class="search-container">
                        <input type="text" id="posSearch" placeholder="Search Products...">
                        <span>SEARCH</span>
                    </div>

                    <div class="input-group" style="flex-direction: row; align-items: center; margin: 0;">
                        <label style="margin-right: 8px; margin-bottom: 0; font-size: 13px;">Unit:</label>
                        <select id="posUnitFilter" onchange="filterProducts()" style="padding: 8px; font-size: 13px; border-radius: 6px;">
                            <option value="all">All Units</option>
                            <?php foreach($unit_types as $u): ?>
                                <option value="<?= strtolower(htmlspecialchars($u)) ?>"><?= htmlspecialchars($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group" style="flex-direction: row; align-items: center; margin: 0;">
                        <label style="margin-right: 8px; margin-bottom: 0; font-size: 13px;">Sort:</label>
                        <select id="posSort" onchange="sortProducts()" style="padding: 8px; font-size: 13px; border-radius: 6px;">
                            <option value="alpha_asc">Alphabetical (A-Z)</option>
                            <option value="alpha_desc">Alphabetical (Z-A)</option>
                            <option value="stock_desc">Stock (High to Low)</option>
                            <option value="stock_asc">Stock (Low to High)</option>
                            <option value="price_desc">Price (High to Low)</option>
                            <option value="price_asc">Price (Low to High)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pos-layout">
                <div class="products-area" id="products-grid">
                    <?php
                    // Show all products now (even if 0) since we might allow negative stock
                    $res = $conn->query("SELECT * FROM inventory ORDER BY product_name ASC");
                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            
                            $stock_color = ($row['current_quantity'] <= 0) ? "red" : "inherit";

                            echo "
                            <div class='product-card' 
                                 data-id='{$row['product_id']}' 
                                 data-name='" . strtolower(htmlspecialchars($row['product_name'])) . "' 
                                 data-price='{$row['price']}' 
                                 data-max-stock='{$row['current_quantity']}' 
                                 data-unit='" . strtolower(htmlspecialchars($row['quantity_type'])) . "'>
                                 
                                <h4 style='text-transform: capitalize;'>" . htmlspecialchars($row['product_name']) . "</h4>
                                <div style='font-size: 12px; color: #888; margin-bottom: 5px;'>" . htmlspecialchars($row['product_type']) . "</div>
                                <p>₱" . number_format($row['price'], 2) . "</p>
                                <div style='font-size: 12px; margin-bottom: 10px; font-weight: bold;'>
                                    Stock: <span id='stock-count-{$row['product_id']}' style='color: {$stock_color};'>{$row['current_quantity']}</span> {$row['quantity_type']}s
                                </div>
                                <button type='button' class='btn btn-secondary' style='width: 100%; border-color: #6a1b9a; color: #6a1b9a;' 
                                    onclick='addToCart({$row['product_id']}, \"" . addslashes($row['product_name']) . "\", {$row['price']}, {$row['current_quantity']})'>
                                    + ADD TO CART
                                </button>
                            </div>";
                        }
                    } else {
                        echo "<p style='grid-column: span 3; color: #888;'>No products available.</p>";
                    }
                    ?>
                </div>

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
                                <option value="Pay Later">Pay Later</option>
                            </select>
                        </div>

                        <div class="input-group" id="receipt_group" style="margin-bottom: 15px;">
                            <label id="receipt_label" style="color: #d32f2f; font-weight: bold;">Reference No. or Invoice *</label>
                            <input type="text" name="receipt_no" id="receipt_no" placeholder="Enter number here..." required style="padding: 10px; border-radius: 6px; width: 100%; border: 1px solid #ccc; font-weight: bold;">
                        </div>

                        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <div class="input-group" style="flex: 1;">
                                <label id="name_label">Buyer Name <span id="name_asterisk" style="display:none; color: #d32f2f;">*</span></label>
                                <input type="text" name="buyer_name" id="buyer_name" placeholder="Optional" style="padding: 10px; border-radius: 6px; width: 100%; border: 1px solid #ccc;">
                            </div>
                            <div class="input-group" style="flex: 1;">
                                <label id="contact_label">Contact <span id="contact_asterisk" style="display:none; color: #d32f2f;">*</span></label>
                                <input type="text" name="buyer_contact" id="buyer_contact" placeholder="Optional" style="padding: 10px; border-radius: 6px; width: 100%; border: 1px solid #ccc;">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" style="width: 100%; background-color: #2e7d32; font-size: 16px; padding: 15px;" onclick="processCheckout()">CONFIRM CHECKOUT</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let cart = {};
        // Inject PHP setting into JS
        const allowNegativeStock = <?= $allow_negative ?> === 1;

        // --- PAYMENT UI LOGIC ---
        document.getElementById('payment_method').addEventListener('change', function() {
            const label = document.getElementById('receipt_label');
            const inputGroup = document.getElementById('receipt_group');
            const inputField = document.getElementById('receipt_no');
            
            const buyerName = document.getElementById('buyer_name');
            const buyerContact = document.getElementById('buyer_contact');
            const nameAsterisk = document.getElementById('name_asterisk');
            const contactAsterisk = document.getElementById('contact_asterisk');

            if (this.value === 'Pay Later') {
                inputGroup.style.display = 'none';
                inputField.removeAttribute('required');
                inputField.value = 'PENDING';
                
                buyerName.setAttribute('required', 'required');
                buyerContact.setAttribute('required', 'required');
                buyerName.placeholder = "Required for Pay Later";
                buyerContact.placeholder = "Required for Pay Later";
                nameAsterisk.style.display = 'inline';
                contactAsterisk.style.display = 'inline';
            } else {
                inputGroup.style.display = 'flex';
                inputField.setAttribute('required', 'required');
                if (inputField.value === 'PENDING') inputField.value = '';
                
                label.innerText = (this.value === 'GCash') ? 'Reference No. *' : 'Receipt No. or Invoice *';

                buyerName.removeAttribute('required');
                buyerContact.removeAttribute('required');
                buyerName.placeholder = "Optional";
                buyerContact.placeholder = "Optional";
                nameAsterisk.style.display = 'none';
                contactAsterisk.style.display = 'none';
            }
        });

        // --- REALTIME SEARCH & FILTERING ---
        function filterProducts() {
            let searchFilter = document.getElementById('posSearch').value.toLowerCase();
            let unitFilter = document.getElementById('posUnitFilter').value;
            let cards = document.querySelectorAll('.product-card');
            
            cards.forEach(card => {
                let textMatch = card.textContent.toLowerCase().includes(searchFilter);
                let unitMatch = (unitFilter === 'all') || (card.dataset.unit === unitFilter);
                card.style.display = (textMatch && unitMatch) ? '' : 'none';
            });
        }
        document.getElementById('posSearch').addEventListener('keyup', filterProducts);

        // --- REALTIME SORTING ---
        function sortProducts() {
            let container = document.getElementById('products-grid');
            let cards = Array.from(container.getElementsByClassName('product-card'));
            let sortType = document.getElementById('posSort').value;

            cards.sort((a, b) => {
                if (sortType === 'alpha_asc') return a.dataset.name.localeCompare(b.dataset.name);
                if (sortType === 'alpha_desc') return b.dataset.name.localeCompare(a.dataset.name);
                if (sortType === 'stock_desc') return parseInt(b.dataset.maxStock) - parseInt(a.dataset.maxStock);
                if (sortType === 'stock_asc') return parseInt(a.dataset.maxStock) - parseInt(b.dataset.maxStock);
                if (sortType === 'price_desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                if (sortType === 'price_asc') return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            });

            container.innerHTML = '';
            cards.forEach(card => container.appendChild(card));
        }

        // --- CART LOGIC ---
        function addToCart(id, name, price, maxQty) {
            if (cart[id]) {
                if (allowNegativeStock || cart[id].qty < maxQty) {
                    cart[id].qty++;
                } else {
                    alert("Cannot exceed current stock limit of " + maxQty + " (Negative Stock is Disabled)!");
                }
            } else {
                if (allowNegativeStock || maxQty > 0) {
                    cart[id] = { name: name, price: price, qty: 1, max: maxQty };
                } else {
                    alert("Item is out of stock! (Negative Stock is Disabled)");
                }
            }
            renderCart();
        }

        function updateQty(id, newQty) {
            let n = parseInt(newQty);
            if (!allowNegativeStock && n > cart[id].max) {
                alert("Cannot exceed current stock (" + cart[id].max + ")!");
                cart[id].qty = cart[id].max;
            } else if (n < 1) {
                delete cart[id];
            } else {
                cart[id].qty = n;
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
                            <input type="number" value="${item.qty}" min="0" onchange="updateQty(${id}, this.value)">
                            <div style="font-weight: bold; width: 60px; text-align: right;">₱${itemTotal.toFixed(2)}</div>
                        </div>
                    </div>
                `;
            }

            if(!hasItems) container.innerHTML = '<p style="color: #888; text-align: center; font-size: 14px;">Cart is empty</p>';
            totalEl.innerText = total.toFixed(2);
            updateStockDisplay();
        }

        function updateStockDisplay() {
            let cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                let id = card.dataset.id;
                let maxStock = parseInt(card.dataset.maxStock);
                let currentStock = maxStock - (cart[id] ? cart[id].qty : 0);
                
                let stockSpan = document.getElementById('stock-count-' + id);
                if (stockSpan) {
                    stockSpan.innerText = currentStock;
                    stockSpan.style.color = (currentStock <= 0) ? "red" : "inherit";
                }
            });
        }

        function processCheckout() {
            if (!document.getElementById('checkoutForm').reportValidity()) return;
            if (Object.keys(cart).length === 0) {
                alert("Your cart is empty! Please add products first.");
                return;
            }

            const cartArray = Object.keys(cart).map(id => ({ id: id, qty: cart[id].qty }));
            document.getElementById('cart_data').value = JSON.stringify(cartArray);
            document.getElementById('checkoutForm').submit();
        }
    </script>
</body>
</html>