document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Auto-Format Price Inputs to 2 Decimal Places
    const priceInputs = document.querySelectorAll('input[name="price"]');
    priceInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });

    // 2. Inventory Form Safety Confirmation
    const inventoryForm = document.querySelector('.inline-form');
    if (inventoryForm) {
        inventoryForm.addEventListener('submit', function(e) {
            const productName = document.querySelector('input[name="product_name"]').value;
            const qty = document.querySelector('input[name="quantity"]').value;
            const unit = document.querySelector('select[name="quantity_type"]').value;
            
            const confirmMsg = `Are you sure you want to add ${qty} ${unit}(s) of ${productName} to the inventory?`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault(); // Stops the form from submitting if they click Cancel
            }
        });
    }

});