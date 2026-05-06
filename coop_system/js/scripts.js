document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Auto-Format Price Inputs to 2 Decimal Places
    const priceInputs = document.querySelectorAll('input[name="price"], input[name="edit_price"]');
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
                e.preventDefault(); 
            }
        });
    }

    // 3. Edit Modal Logic (This is what was missing!)
    const modal = document.getElementById("editModal");
    const closeBtn = document.querySelector(".close-modal");
    const editButtons = document.querySelectorAll(".edit-btn");

    // Open modal and populate data
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Grab data from the button's hidden data attributes
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const qty = this.getAttribute('data-qty');
            const price = this.getAttribute('data-price');

            // Inject into the modal's input fields
            document.getElementById('modal_edit_id').value = id;
            document.getElementById('modal_edit_name').value = name;
            document.getElementById('modal_edit_qty').value = qty;
            document.getElementById('modal_edit_price').value = parseFloat(price).toFixed(2);

            // Show modal
            modal.style.display = "block";
        });
    });

    // Close modal when clicking 'X'
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = "none";
        });
    }

    // Close modal when clicking anywhere outside the white box
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

});