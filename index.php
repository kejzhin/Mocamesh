<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
  
    echo "Received POST data:<br>";
    echo "Email: " . htmlspecialchars($_POST['email']) . "<br>";
    echo "Amount: " . htmlspecialchars($_POST['amount']) . "<br>";
    echo "Phone Number: " . htmlspecialchars($_POST['phoneNumber']) . "<br>";
    echo "Voucher: " . (isset($_POST['voucher']) ? htmlspecialchars($_POST['voucher']) : 'Not provided') . "<br>";

    $email = $_POST['email']; 
    $amount = $_POST['amount'];
    $phoneNumber = $_POST['phoneNumber'];
    $voucher = isset($_POST['voucher']) ? $_POST['voucher'] : '';

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
    $phoneNumber = filter_var($phoneNumber, FILTER_SANITIZE_STRING);
    $voucher = filter_var($voucher, FILTER_SANITIZE_STRING);

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && $amount !== false && preg_match('/^\d{11}$/', $phoneNumber)) {
        if ($mysqli) {
            $stmt = $mysqli->prepare("INSERT INTO records (email, amount, phoneNumber, voucher) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sdss', $email, $amount, $phoneNumber, $voucher);
                if ($stmt->execute()) {
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    echo "Error executing statement: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $mysqli->error;
            }
        } else {
            echo "Database connection not established.";
        }
    } else {
        echo "Invalid input data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAYSTACK</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <style>
        body {
            background-color: powderblue !important;
        }
    </style>
        <div class="">
            <div class="container text-center">
                <div class="row">
                    <div class="d-sm-12">
                        <img class="logo-img" src="img/logo.png" alt="" width="400">
                    </div>
                </div>
                <div class="row">
                    <div class="form-container p-2 mb-5">
                        <form id="paymentForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phoneNumber">Phone Number:</label>
                                <input type="tel" name="phoneNumber" class="form-control" id="phoneNumber" pattern="\d{11}" title="Please enter 11 digits" required placeholder="09123456789">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="amount">Amount</label><br>
                                <select name="amount" id="amount" class="form-control" style="font-size:16px; font-weight:500; color:black;">
                                    <option value="100">100 for 10GB Data Access</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="form-submit">
                                <button type="submit"class="text-uppercase" name="submit">Get Voucher</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <style>
                @media only screen and (max-device-width: 720px) {
                    img.logo-img {
                        width: 300px !important;
                    }
                }
            </style>
        </div>

    <script src="https://js.paystack.co/v1/inline.js"></script>

    <?php
    include 'configs.php';
    ?>

<script type="text/javascript">
    const paymentForm = document.getElementById('paymentForm');
    paymentForm.addEventListener("submit", payWithPaystack, false);

    function payWithPaystack(e) {
        e.preventDefault(); // Prevent the form from submitting immediately

        const email = document.getElementById('email').value;
        const amount = document.getElementById('amount').value;

        let handler = PaystackPop.setup({
            key: '<?php echo $PublicKey; ?>', // Replace with your Paystack public key
            email: email,
            amount: amount * 100, // Amount is in kobo, so multiply by 100
            currency: 'NGN',
            ref: '' + Math.floor((Math.random() * 1000000000) + 1), // Generates a pseudo-unique reference

            onClose: function() {
                alert('Transaction was not completed, window closed.');
            },

            callback: function(response) {
              let message = 'Payment complete! Reference: ' + response.reference;
              alert(message);

              // Redirect to the new PHP page with the reference ID
              window.location.href = 'insert_data.php?reference=' + response.reference + 
                                    '&email=' + encodeURIComponent(document.getElementById('email').value) + 
                                    '&amount=' + encodeURIComponent(document.getElementById('amount').value) + 
                                    '&phoneNumber=' + encodeURIComponent(document.getElementById('phoneNumber').value);
          }

        });

        handler.openIframe();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>