<?php 
include 'db.php'; 

// Fetch negative stock setting
$setting_res = $conn->query("SELECT setting_value FROM config_inventory_settings WHERE setting_key = 'allow_negative_stock'");
$allow_negative = 0;
if ($setting_res && $setting_res->num_rows > 0) {
    $allow_negative = (int)$setting_res->fetch_assoc()['setting_value'];
}

// PROCESS THE CHECKOUT CART
$checkout_success = false;
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
            
            $conn->query("UPDATE inventory SET current_quantity = current_quantity - $qty WHERE product_id=$id");
            
            $conn->query("INSERT INTO inventory_outsourcing (record_date, product_id, quantity_out, payment_method, receipt_no, buyer_name, buyer_contact) 
                          VALUES ('$date', $id, $qty, '$payment', '$receipt', '$buyer_name', '$buyer_contact')");
        }
        // Flag for the success alert to trigger on page load
        $checkout_success = true;
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
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], },
                    colors: { primary: '#6a1b9a', primaryDark: '#570591', }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased overflow-hidden">

    <div id="customAlertModal" class="fixed inset-0 z-[1000] hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity"></div>
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all z-10 flex flex-col translate-y-4 opacity-0" id="customAlertBox">
            <div id="customAlertHeader" class="px-6 py-4 flex items-center gap-3 border-b">
                <i id="customAlertIcon" class="fas fa-exclamation-circle text-2xl"></i>
                <h3 id="customAlertTitle" class="text-lg font-bold tracking-tight">Alert</h3>
            </div>
            <div class="p-6 text-gray-600 text-sm leading-relaxed" id="customAlertMessage">
                </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button id="customAlertBtn" class="bg-primary hover:bg-primaryDark text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md">OK</button>
            </div>
        </div>
    </div>

    <div class="flex h-screen w-full">

        <div id="mobile-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden transition-opacity" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="bg-white w-72 border-r border-gray-200 flex flex-col transition-transform transform -translate-x-full md:translate-x-0 fixed md:relative z-50 h-full shadow-lg md:shadow-none">
            <div class="p-6 flex items-center justify-center border-b border-gray-100 relative">
                <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo" class="w-40 md:w-52 h-auto object-contain py-2 drop-shadow-sm transition-transform hover:scale-105">
                <button class="absolute top-4 right-4 md:hidden text-gray-400 hover:text-gray-800" onclick="toggleSidebar()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-4 flex flex-col gap-1">
                <a href="index.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-users w-6"></i> MEMBERSHIP DIRECTORY
                </a>
                <a href="transactions.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-receipt w-6"></i> TRANSACTIONS
                </a>
                <a href="inventory.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-boxes w-6"></i> INVENTORY
                </a>
                <a href="pos.php" class="flex items-center px-6 py-3 bg-primary text-white font-semibold border-l-4 border-primaryDark">
                    <i class="fas fa-shopping-cart w-6"></i> SELL / OUTSOURCE
                </a>
                <a href="outsourcing_report.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-chart-line w-6"></i> OUTSOURCING LOGS
                </a>
                <a href="database_management.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-database w-6"></i> DATABASE SETTINGS
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative w-full">
            
            <header class="bg-white shadow-sm px-4 md:px-8 py-4 flex justify-between items-center z-10">
                <div class="flex items-center gap-4">
                    <button class="text-gray-500 focus:outline-none md:hidden hover:text-primary" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight">Point of Sale</h1>
                </div>
            </header>

            <div class="flex-1 overflow-hidden flex flex-col lg:flex-row">
                
                <div class="flex-1 flex flex-col h-full bg-gray-50">
                    
                    <div class="p-4 md:p-6 bg-white border-b border-gray-200 shadow-sm flex flex-col sm:flex-row gap-4 items-center z-10 relative">
                        <div class="flex w-full sm:w-1/2 bg-gray-100 border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-primary focus-within:border-primary transition-all">
                            <div class="px-3 py-2 text-gray-400 flex items-center justify-center"><i class="fas fa-search"></i></div>
                            <input type="text" id="posSearch" placeholder="Search Products..." class="w-full py-2 pr-4 outline-none text-sm text-gray-700 bg-transparent">
                        </div>
                        
                        <div class="flex gap-2 w-full sm:w-auto">
                            <select id="posUnitFilter" onchange="filterProducts()" class="flex-1 sm:flex-none rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                <option value="all">All Units</option>
                                <?php foreach($unit_types as $u): ?>
                                    <option value="<?= strtolower(htmlspecialchars($u)) ?>"><?= htmlspecialchars($u) ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select id="posSort" onchange="sortProducts()" class="flex-1 sm:flex-none rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                <option value="alpha_asc">A-Z</option>
                                <option value="stock_desc">Highest Stock</option>
                                <option value="price_desc">Highest Price</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 md:p-6 relative">
                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4" id="products-grid">
                            <?php
                            $res = $conn->query("SELECT * FROM inventory ORDER BY product_name ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) {
                                    
                                    $stock_color = ($row['current_quantity'] <= 0) ? "text-red-600 font-bold" : "text-green-600 font-bold";
                                    $bg_shade = ($row['current_quantity'] <= 0) ? "bg-red-50" : "bg-white";

                                    echo "
                                    <div class='product-card {$bg_shade} rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col hover:shadow-md transition-shadow relative overflow-hidden group' 
                                         data-id='{$row['product_id']}' 
                                         data-name='" . strtolower(htmlspecialchars($row['product_name'])) . "' 
                                         data-price='{$row['price']}' 
                                         data-max-stock='{$row['current_quantity']}' 
                                         data-unit='" . strtolower(htmlspecialchars($row['quantity_type'])) . "'>
                                         
                                        <div class='flex-1 mb-3'>
                                            <h4 class='text-sm font-bold text-gray-800 capitalize leading-tight mb-1 group-hover:text-primary transition-colors'>" . htmlspecialchars($row['product_name']) . "</h4>
                                            <div class='text-xs text-gray-500 uppercase tracking-wider'>" . htmlspecialchars($row['product_type']) . "</div>
                                        </div>
                                        
                                        <div class='text-lg font-extrabold text-gray-900 mb-2'>₱" . number_format($row['price'], 2) . "</div>
                                        
                                        <div class='text-xs text-gray-600 mb-4 bg-gray-100 rounded px-2 py-1 inline-block w-max'>
                                            Stock: <span id='stock-count-{$row['product_id']}' class='{$stock_color}'>{$row['current_quantity']}</span> {$row['quantity_type']}s
                                        </div>
                                        
                                        <button type='button' class='mt-auto w-full bg-white border-2 border-primary text-primary hover:bg-primary hover:text-white font-semibold py-2 rounded-lg text-sm transition-colors' 
                                            onclick='addToCart({$row['product_id']}, \"" . addslashes($row['product_name']) . "\", {$row['price']}, {$row['current_quantity']})'>
                                            <i class='fas fa-cart-plus mr-1'></i> ADD
                                        </button>
                                    </div>";
                                }
                            } else {
                                echo "<p class='col-span-full text-center text-gray-400 py-10'>No products available in inventory.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-96 bg-white border-t lg:border-t-0 lg:border-l border-gray-200 flex flex-col shadow-2xl lg:shadow-none z-20 h-auto lg:h-full max-h-[50vh] lg:max-h-full">
                    
                    <div class="p-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="font-bold text-gray-800 text-lg"><i class="fas fa-shopping-basket text-primary mr-2"></i>Checkout Cart</h3>
                    </div>

                    <div id="cart-container" class="flex-1 overflow-y-auto p-4 flex flex-col gap-3 bg-gray-50">
                        <p class="text-gray-400 text-center text-sm py-10 flex flex-col items-center justify-center">
                            <i class="fas fa-shopping-cart text-4xl mb-3 opacity-20"></i>
                            Cart is empty
                        </p>
                    </div>

                    <div class="p-4 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                            <span class="text-gray-600 font-semibold uppercase text-sm tracking-wider">Total</span>
                            <span class="text-3xl font-black text-green-600 tracking-tight">₱<span id="cart-total-price">0.00</span></span>
                        </div>

                        <form action="pos.php" method="POST" id="checkoutForm" class="flex flex-col gap-3">
                            <input type="hidden" name="checkout" value="1">
                            <input type="hidden" name="cart_data" id="cart_data">
                            
                            <div>
                                <select name="payment_method" id="payment_method" required class="w-full font-bold text-gray-800 border-2 border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600 transition-colors bg-white">
                                    <option value="Cash">Cash Payment</option>
                                    <option value="GCash">GCash Transfer</option>
                                    <option value="Pay Later">Pay Later</option>
                                </select>
                            </div>

                            <div id="receipt_group">
                                <input type="text" name="receipt_no" id="receipt_no" placeholder="Reference No. or Invoice *" required class="w-full font-bold text-gray-800 border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:border-primary placeholder-gray-400">
                            </div>

                            <div id="pay_later_group" class="hidden flex gap-2">
                                <input type="text" name="buyer_name" id="buyer_name" placeholder="Buyer Name *" class="w-1/2 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-red-500">
                                <input type="text" name="buyer_contact" id="buyer_contact" placeholder="Contact *" class="w-1/2 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-red-500">
                            </div>

                            <button type="button" class="w-full bg-green-600 hover:bg-green-700 text-white font-black py-4 rounded-lg shadow-lg transition-transform transform hover:-translate-y-0.5 mt-2 flex items-center justify-center gap-2 text-lg" onclick="processCheckout()">
                                <i class="fas fa-check-circle"></i> CONFIRM CHECKOUT
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // --- CUSTOM ALERT LOGIC ---
        let alertRedirectUrl = null;

        function showCustomAlert(title, message, type = 'error', redirectUrl = null) {
            const modal = document.getElementById('customAlertModal');
            const box = document.getElementById('customAlertBox');
            const titleEl = document.getElementById('customAlertTitle');
            const msgEl = document.getElementById('customAlertMessage');
            const iconEl = document.getElementById('customAlertIcon');
            const headerEl = document.getElementById('customAlertHeader');
            const btnEl = document.getElementById('customAlertBtn');

            titleEl.innerText = title;
            msgEl.innerHTML = message;
            alertRedirectUrl = redirectUrl;

            // Style based on type (success or error)
            if (type === 'success') {
                iconEl.className = 'fas fa-check-circle text-2xl text-green-500';
                headerEl.className = 'px-6 py-4 flex items-center gap-3 border-b bg-green-50 border-green-100';
                btnEl.className = 'bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md';
            } else {
                iconEl.className = 'fas fa-exclamation-circle text-2xl text-red-500';
                headerEl.className = 'px-6 py-4 flex items-center gap-3 border-b bg-red-50 border-red-100';
                btnEl.className = 'bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Trigger animation
            setTimeout(() => {
                box.classList.remove('translate-y-4', 'opacity-0');
                box.classList.add('translate-y-0', 'opacity-100');
            }, 10);
        }

        document.getElementById('customAlertBtn').addEventListener('click', function() {
            const modal = document.getElementById('customAlertModal');
            const box = document.getElementById('customAlertBox');
            
            box.classList.remove('translate-y-0', 'opacity-100');
            box.classList.add('translate-y-4', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (alertRedirectUrl) {
                    window.location.href = alertRedirectUrl;
                }
            }, 300);
        });

        // Trigger Success Alert from PHP if checkout was processed
        <?php if ($checkout_success): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showCustomAlert('Transaction Complete', 'The checkout was processed successfully. Inventory levels have been updated.', 'success', 'outsourcing_report.php');
            });
        <?php endif; ?>


        let cart = {};
        const allowNegativeStock = <?= $allow_negative ?> === 1;

        // Payment UI Logic
        document.getElementById('payment_method').addEventListener('change', function() {
            const receiptGroup = document.getElementById('receipt_group');
            const receiptField = document.getElementById('receipt_no');
            
            const payLaterGroup = document.getElementById('pay_later_group');
            const buyerName = document.getElementById('buyer_name');
            const buyerContact = document.getElementById('buyer_contact');

            if (this.value === 'Pay Later') {
                receiptGroup.style.display = 'none';
                receiptField.removeAttribute('required');
                receiptField.value = 'PENDING';
                
                payLaterGroup.classList.remove('hidden');
                buyerName.setAttribute('required', 'required');
                buyerContact.setAttribute('required', 'required');
                
                this.classList.add('border-red-500', 'text-red-700');
                this.classList.remove('border-gray-300', 'text-gray-800');
            } else {
                receiptGroup.style.display = 'block';
                receiptField.setAttribute('required', 'required');
                if (receiptField.value === 'PENDING') receiptField.value = '';
                receiptField.placeholder = (this.value === 'GCash') ? 'GCash Ref No. *' : 'Receipt No. / Invoice *';

                payLaterGroup.classList.add('hidden');
                buyerName.removeAttribute('required');
                buyerContact.removeAttribute('required');
                
                this.classList.remove('border-red-500', 'text-red-700');
                this.classList.add('border-gray-300', 'text-gray-800');
            }
        });

        // Search & Filtering
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

        // Sorting
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

        // Cart Logic (Updated to use custom alerts)
        function addToCart(id, name, price, maxQty) {
            if (cart[id]) {
                if (allowNegativeStock || cart[id].qty < maxQty) {
                    cart[id].qty++;
                } else {
                    showCustomAlert('Stock Limit Reached', `Cannot exceed current stock limit of <strong>${maxQty}</strong>.<br><br><em>(Negative Stock mapping is currently disabled in settings)</em>`, 'error');
                }
            } else {
                if (allowNegativeStock || maxQty > 0) {
                    cart[id] = { name: name, price: price, qty: 1, max: maxQty };
                } else {
                    showCustomAlert('Out of Stock', `This item is completely out of stock.<br><br><em>(Negative Stock mapping is currently disabled in settings)</em>`, 'error');
                }
            }
            renderCart();
        }

        function updateQty(id, newQty) {
            let n = parseInt(newQty);
            if (!allowNegativeStock && n > cart[id].max) {
                showCustomAlert('Stock Limit Reached', `Cannot manually enter a quantity higher than the current stock limit of <strong>${cart[id].max}</strong>.`, 'error');
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
                    <div class="bg-white border border-gray-200 rounded-lg p-3 flex justify-between items-center shadow-sm">
                        <div class="flex-1 pr-2">
                            <div class="font-bold text-sm text-gray-800 leading-tight mb-1">${item.name}</div>
                            <div class="text-xs text-primary font-semibold">₱${item.price.toFixed(2)}</div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="flex items-center border border-gray-300 rounded overflow-hidden h-8">
                                <button onclick="updateQty(${id}, ${item.qty - 1})" class="px-2 bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors h-full"><i class="fas fa-minus text-xs"></i></button>
                                <input type="number" value="${item.qty}" min="0" class="w-10 text-center text-sm font-bold outline-none h-full" onchange="updateQty(${id}, this.value)">
                                <button onclick="updateQty(${id}, ${item.qty + 1})" class="px-2 bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors h-full"><i class="fas fa-plus text-xs"></i></button>
                            </div>
                            <div class="font-black text-gray-800">₱${itemTotal.toFixed(2)}</div>
                        </div>
                    </div>
                `;
            }

            if(!hasItems) container.innerHTML = `
                <p class="text-gray-400 text-center text-sm py-10 flex flex-col items-center justify-center">
                    <i class="fas fa-shopping-cart text-4xl mb-3 opacity-20"></i>
                    Cart is empty
                </p>`;
            
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
                    stockSpan.className = (currentStock <= 0) ? "text-red-600 font-bold" : "text-green-600 font-bold";
                }
            });
        }

        function processCheckout() {
            if (!document.getElementById('checkoutForm').reportValidity()) return;
            if (Object.keys(cart).length === 0) {
                showCustomAlert('Empty Cart', 'Your checkout cart is completely empty. Please add items to the cart before confirming the checkout.', 'error');
                return;
            }

            const cartArray = Object.keys(cart).map(id => ({ id: id, qty: cart[id].qty }));
            document.getElementById('cart_data').value = JSON.stringify(cartArray);
            document.getElementById('checkoutForm').submit();
        }
    </script>
</body>
</html>