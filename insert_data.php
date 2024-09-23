<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received!</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <style>
        body {
            background-color: powderblue !important;
        }
    </style>
<?php
require 'db_connect.php';
include 'configs.php';
session_start();

function generateRandomString($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function generateUniqueVoucher($mysqli, $length = 8) {
    $isUnique = false;
    $voucher = '';

    while (!$isUnique) {
        $voucher = generateRandomString($length);

        // checking if the voucher already exists in the database
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM records WHERE voucher = ?");
        $stmt->bind_param('s', $voucher);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        // If count is 0, the voucher is unique
        if ($count == 0) {
            $isUnique = true;
        }
    }

    return $voucher;
}

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    // Check if the transaction has already been processed
    if (isset($_SESSION['reference']) && $_SESSION['reference'] == $reference) {
        // Transaction already processed, display the same voucher and data
        $voucher = $_SESSION['voucher'];
        $email = $_SESSION['email'];
        $amount = $_SESSION['amount'];
        $phoneNumber = $_SESSION['phoneNumber'];
    } else {
        // Transaction hasn't been processed yet
        $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_GET['amount'], FILTER_VALIDATE_FLOAT);
        $phoneNumber = filter_var($_GET['phoneNumber'], FILTER_SANITIZE_STRING);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $SecretKey"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response) {
            $data = json_decode($response);
            if ($data->status == true && $data->data->status == 'success') {
                $voucher = generateUniqueVoucher($mysqli); // Generate a unique 8-character voucher

                if ($mysqli) {
                    $stmt = $mysqli->prepare("INSERT INTO records (email, amount, phoneNumber, voucher) VALUES (?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param('sdss', $email, $amount, $phoneNumber, $voucher);
                        if ($stmt->execute()) {
                            // Store transaction info in the session
                            $_SESSION['reference'] = $reference;
                            $_SESSION['email'] = $email;
                            $_SESSION['amount'] = $amount;
                            $_SESSION['phoneNumber'] = $phoneNumber;
                            $_SESSION['voucher'] = $voucher;

                            header("Location: " . $_SERVER['REQUEST_URI']);
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
                echo "Payment verification failed: " . $data->message;
            }
        } else {
            echo "cURL Error: Payment verification failed.";
        }
    }
} else {
    echo "No payment reference provided.";
}
?>

<div class="container p-5 mt-5 mb-5">
    <div class="card-container">
        <div class="card shadow-sm p-3">
            <div class="text-center p-2 mt-4">
                <i class="h3 bi bi-check-circle-fill text-success me-2"></i>
                <span class="h3 fw-bold text-success">Payment received successfully!</span>
            </div>
            <div class="text-center p-1">
                <span class="text-muted">Please screenshot or copy your voucher code for activation. Thank you!</span>
            </div>
            <div class="row">
                <div class="col-sm-12 col-lg-12 col-xl-12">
                    <div class="voucher d-flex justify-content-center align-items-center text-center p-3">
                        <span class="bg-warning text-dark p-3 text-center w-25 h2 fw-bold" style="white-space: nowrap !important;"><?php echo $voucher; ?></span>
                    </div>
                </div>
            </div>
            <div class="info text-center p-1">
                <span class="fw-bold">Email: </span>
                <span><?php echo htmlspecialchars($email); ?></span><br>
                <span class="fw-bold">Amount Paid: </span>
                <span><?php echo htmlspecialchars($amount); ?></span><br>
                <span class="fw-bold">Phone Number: </span>
                <span><?php echo htmlspecialchars($phoneNumber); ?></span><br>
            </div>
            <div class="link text-center pt-5 mb-4">
                <a href="index.php" class="text-dark text-decoration-none"><i class="bi bi-arrow-left-short me-2"></i>Back To Home</a>
            </div>
        </div>
    </div>
</div>

<style>
    @media only screen and (max-device-width: 720px) {
        .voucher span {
            white-space: nowrap;
            font-size: 40px;
            color: #000;
            display: inline-block;
            width: 300px !important;
            text-align: center;
        }
    }

</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>