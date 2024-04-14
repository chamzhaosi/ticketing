<?php

session_start();
// $_SESSION["payment_status"] = "Cancel by user";
// $_SESSION['seat_id'] = 14;
// $_SESSION['user_id'] = 14;
// $_SESSION['donate_id'] = 14;
// $_SESSION['balance_amount'] = 15;

if(isset($_SESSION['seat_id']) && isset($_SESSION['user_id']) && $_SESSION["payment_status"]){
    include_once "db_connector.php";
    include_once "db_operator.php";
    include_once "check_data_valid.php";
    include_once "php_mail.php";
    $load_into_qr_page_time = $_SESSION['load_into_qr_page_time'];

    $select_result = select_all_query_by_value($conn, "seat_detail", "id", "i", $_SESSION['seat_id']);
    if($select_result[0]){
        $seat_number = $select_result[1][0]["seat_number"];
        $amount = $select_result[1][0]['total_amount'];
        $total = (float)$amount;
    }
    
    $select_result = select_all_query_by_value($conn, "user_detail", "id", "i", $_SESSION['user_id']);
    if($select_result[0]){
        $bank_holder_name = $select_result[1][0]["bank_account_name"];
        $email = $select_result[1][0]["email"];
        $full_name = $select_result[1][0]["full_name"];
    }

    $getTrasResult = getTransactionValue($conn, $bank_holder_name, $load_into_qr_page_time);
    if ($getTrasResult[0] != 0 ){
        $total_received_amount = $getTrasResult[0];
    }
    
    if(isset($_SESSION['donate_id'])){
        $select_result = select_all_query_by_value($conn, "donate_detail", "id", "i", $_SESSION['donate_id']);
        if($select_result[0]){
            $donate_amount = $select_result[1][0]["total_amount"];
            $total += (float)$donate_amount;
        }else{
            $donate_amount = "";
        }
    }else{
        $donate_amount = "";
    } 

    $title = "";
    $bg_color = "";
    $message = "";
    $noted = "";
    $donate_msg = "";
    if($_SESSION["payment_status"] === "Time Up"){
        $bg_color = "warning";
        $title = strtoupper("The allotted time has expired");
        $message = "Due to the <b> expiration of the allocated time </b> and our inability to find and verify the bank holder name <b> ($bank_holder_name) </b> you provided earlier, we kindly request that you <b> attempt the submission again </b>. Should you encounter any difficulties, please do not hesitate to contact us at <b> 012-1234567 and ask for Mr. Jacky </b> for assistance.";
        $noted = "Please be advised that, due to the absence of a transaction record in our system, <b>your order is subject to cancellation</b>. If you are certain that the <b> transaction has been made </b>, we kindly request that you <b>secure a copy of the transaction record </b> and <b>contact us at your earliest convenience.</b>";
    
    }else if($_SESSION["payment_status"] === "Match"){
        $bg_color = "success";
        $title = strtoupper("Your payment has been successfully processed.");
        $message = "Thank you for your payment, which we <b>have successfully received</b>. A confirmation email will be sent to you shortly.";
        $noted = "If you encounter any issues, please do not hesitate to contact us at <b> 012-1234567 and ask for Mr. Jacky </b> for assistance.";
        sendEmail($email, $full_name, $seat_number, $amount, $donate_amount, $total, $total_received_amount, $_SESSION["payment_status"], 0, 0);
            
    }else if($_SESSION["payment_status"] === "Not Match"){
        $bg_color = "danger";
        $title = strtoupper("Not transaction match");
        $message = "Due to the absence of a transaction record in our system, <b>your order is subject to cancellation</b>. If you are certain that the <b> transaction has been made,</b> we kindly request that you <b>secure a copy of the transaction record </b> and <b>contact us at <b> 012-1234567 and ask for Mr. Jacky </b> for assistance.";;
        $noted = "";

    }else if($_SESSION["payment_status"] === "Match, but payment more"){
        $bg_color = "success";
        $title = strtoupper("Your payment has been successfully processed.");
        $message = "Thank you for your payment, which we <b>have successfully received</b>. A confirmation email will be sent to you shortly.";
        $noted =  "We have noticed <b>an overpayment (RM " . $_SESSION['balance_amount'] . ") in your transaction</b>. Please contact us at <b>012-1234567 and ask for Mr. Jacky</b> to arrange for a refund.";
        sendEmail($email, $full_name, $seat_number, $amount, $donate_amount, $total, $total_received_amount, $_SESSION["payment_status"], $_SESSION['balance_amount'], 0);

    }else if($_SESSION["payment_status"] === "Match, but payment less"){
        $bg_color = "danger";
        $title = strtoupper("Unfortunately, your payment was not successfully processed.");
        $message = "Due to your payment (RM " . $_SESSION['balance_amount'] . ") is less than required amount (RM ". $total ."), therefore <b>your order is subject to cancellation</b>. Please make a new transaction with the correct amount.";
        $noted = "Please contact us at <b> 012-1234567 and ask for Mr. Jacky </b> to arrange for a refund.";
        sendEmail($email, $full_name, $seat_number, $amount, $donate_amount, $total, $total_received_amount, $_SESSION["payment_status"], $_SESSION['balance_amount'], 1);

    }else if($_SESSION["payment_status"] === "Cancel by user"){
        $bg_color = "warning";
        $title = strtoupper("Your order has been cancelled.");
        $message = "If you encounter any issues, please do not hesitate to contact us at <b> 012-1234567 and ask for Mr. Jacky </b> for assistance.";
        $noted = "";

    }else if($_SESSION["payment_status"] === "Cancel by user, but got transaction"){
        $bg_color = "warning";
        $title = strtoupper("Your order has been cancelled.");
        $message = "We have detected your transaction <b>(RM " . $total_received_amount. ").</b> Please contact us to initiate the refund process!";
        $noted = "If you encounter any issues, please do not hesitate to contact us at <b> 012-1234567 and ask for Mr. Jacky </b> for assistance.";
        sendEmail($email, $full_name, $seat_number, $amount, $donate_amount, $total, $total_received_amount, $_SESSION["payment_status"], 0, 1);
    }
    
    // First, store the value of the session variable you want to keep
    $keepThisValue = $_SESSION['locked_id'];

    // Unset all session variables
    $_SESSION = array();

    // Restore the value of the session variable you wanted to keep
    $_SESSION['locked_id'] = $keepThisValue;

    mysqli_close($conn); // Close the connection before returning

}else{
    header("Location: ./purchase.php");
    die("Redirect to purchase.php");
}

?>
<!DOCTYPE html>
<head>
    <title>Payment Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./statics/css/concert.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>

<body class="container bg-secondary-subtle">
    <div id="loadingContainer" class="d-flex justify-content-center align-items-center vh-100 d-block">
      <div class="loader"></div>
    </div>

    <div id="contentContainer" class="d-none">
        <div class="vh-100 d-flex justify-content-center align-items-xl-center align-items-start">
            <div class="w-75">
                <div class="alert alert-<?php echo $bg_color;?> fs-4" role="alert">
                <h3 class="text-center bg-<?php echo $bg_color;?> rounded text-light"> <?php echo $title; ?></h3>
                    <br>
                    <?php echo $message; ?>
                    <br>
                    <?php 
                        if ($noted != ""){
                            echo '<br>';
                            echo $noted; 
                            echo '<br>';
                        } 
                    ?>
                    <a href="./purchase.php" class="fs-5"> BACK</a>
                </div>
            </div>
        </div>
    </div>
    <script src="./statics/js/concert.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

<html>