<?php
// Fetch user's email from the database
$userId = $_settings->userdata('id'); // Assuming user ID is stored in session or elsewhere
$emailQuery = $conn->query("SELECT email FROM client_list WHERE id = '{$userId}'"); // Modify table and column names as needed
$email = $emailQuery->fetch_assoc()['email'];

// Generate a unique reference number
$referenceNumber = 'REF' . strtoupper(bin2hex(random_bytes(8))); // Generate a unique reference
?>

<div class="content py-3">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <div class="h5 card-title">Checkout</div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <form action="" id="checkout-form">
                        <div class="form-group">
                            <label for="delivery_address" class="control-label">Delivery Address</label>
                            <textarea name="delivery_address" id="delivery_address" rows="4" class="form-control rounded-0" required><?= htmlspecialchars($_settings->userdata('address'), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="form-group text-right">
                            
                            <!-- Button for Pay with Paystack -->
                            <button type="button" class="btn btn-flat btn-default btn-sm bg-navy" id="pay-with-paystack-btn">Pay with Paystack</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="row" id="summary">
                        <div class="col-12 border">
                            <h2 class="text-center"><b>Summary</b></h2>
                        </div>
                        <?php 
                        $gtotal = 0;
                        $vendors = $conn->query("SELECT * FROM `vendor_list` WHERE id IN (SELECT vendor_id FROM product_list WHERE id IN (SELECT product_id FROM `cart_list` WHERE client_id ='{$_settings->userdata('id')}')) ORDER BY `shop_name` ASC");
                        while($vrow = $vendors->fetch_assoc()):    
                            $vtotal = $conn->query("SELECT SUM(c.quantity * p.price) FROM `cart_list` c INNER JOIN product_list p ON c.product_id = p.id WHERE c.client_id = '{$_settings->userdata('id')}' AND p.vendor_id = '{$vrow['id']}'")->fetch_array()[0];   
                            $vtotal = $vtotal > 0 ? $vtotal : 0;
                            $gtotal += $vtotal;
                        ?>
                            <div class="col-12 border item">
                                <b class="text-muted"><small><?= htmlspecialchars($vrow['code']." - ".$vrow['shop_name'], ENT_QUOTES, 'UTF-8') ?></small></b>
                                <div class="text-right"><b><?= format_num($vtotal) ?></b></div>
                            </div>
                        <?php endwhile; ?>
                        <div class="col-12 border">
                            <b class="text-muted">Grand Total</b>
                            <div class="text-right h3" id="total"><b><?= format_num($gtotal) ?></b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script> 
<script>
    // Paystack payment
    function payWithPaystack(email, reference) {
        let handler = PaystackPop.setup({
            key: 'pk_test_cba2f103b73d57a1f6044e77351143a5be54b5a4', // Replace with your public key
            email: email, // Use the email fetched from PHP
            amount: <?= $gtotal * 100 ?>,
            currency: 'NGN',
            ref: reference, // Use the reference number from PHP
            onClose: function(){
                alert('Window closed.');
            },
            callback: function(response){
                const form = document.getElementById('checkout-form');
                const formData = new FormData(form);
                fetch(_base_url_ + 'classes/Master.php?f=place_order', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.replace('./?page=orders/my_orders');
                    } else if (data.msg) {
                        alert(data.msg);
                    } else {
                        alert("An error occurred.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred.");
                });
                // You might want to add code to verify the payment on the server-side here.
            }
        });

        handler.openIframe();
    }

    document.getElementById('place-order-btn').addEventListener('click', function() {
        const form = document.getElementById('checkout-form');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (document.querySelectorAll('#summary .item').length <= 0) {
            alert('There is no order listed in the cart yet.');
            return;
        }

        const formData = new FormData(form);
        fetch(_base_url_ + 'classes/Master.php?f=place_order', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.replace('./?page=orders/my_orders');
            } else if (data.msg) {
                alert(data.msg);
            } else {
                alert("An error occurred.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred.");
        });
    });

    document.getElementById('pay-with-paystack-btn').addEventListener('click', function() {
        const form = document.getElementById('checkout-form');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (document.querySelectorAll('#summary .item').length <= 0) {
            alert('There is no order listed in the cart yet.');
            return;
        }

        // Fetch email and reference from PHP and pass them to the payment function
        let email = '<?= $email ?>'; // PHP to JS variable
        let reference = '<?= $referenceNumber ?>'; // PHP to JS variable
        payWithPaystack(email, reference);
    });
</script>
