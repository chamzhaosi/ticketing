<?php

session_start();

include_once "db_connector.php";
include_once "db_operator.php";
include_once "check_data_valid.php";
include_once "php_mail.php";
$load_donate_page_time = $_SESSION['load_donate_page_time'];

$title = "";
$bg_color = "";
$message = "";
$noted = "";
$donate_msg = "";

if(isset($_SESSION['user_id']) && isset($_SESSION["donate_status"])){
    $select_result = select_all_query_by_value($conn, "user_detail", "id", "i", $_SESSION['user_id']);
    if($select_result[0]){
        $bank_holder_name = $select_result[1][0]["bank_account_name"];
        $email = $select_result[1][0]["email"];
        $full_name = $select_result[1][0]["full_name"];
    }

    $getTrasResult = getTransactionValue($conn, $bank_holder_name, $load_donate_page_time);
    if ($getTrasResult[0] != 0 ){
        $total_received_amount = $getTrasResult[0];
    }

    if($_SESSION["donate_status"] === "Match"){
        $bg_color = "success";
        $title = strtoupper("Thank You for Your Donation!");
        $message = "Your donation has been <b> successfully received. </b> We sincerely appreciate your support and generosity. Your contribution is making a difference at MACO.";
        $noted = "For any inquiries or further details about your donation, please feel free to contact us at  <b> 012-1234567 and ask for Mr. Jacky </b>. We're here to help!";
        // sendEmail($email, $full_name, $seat_number, $amount, $donate_amount, $total, $total_received_amount, $_SESSION["donate_status"], 0, 0);
            
    }else if($_SESSION["donate_status"] === "Not Match"){
        $bg_color = "danger";
        $title = strtoupper("Donate Unsuccessful");
        $message = "We're sorry to inform you that your recent donate attempt was unsuccessful. No transaction has been recorded on your bank account.";
        $noted = "For any inquiries or further details about your donation, please feel free to contact us at  <b> 012-1234567 and ask for Mr. Jacky </b>. We're here to help!";

    }else if($_SESSION["donate_status"] === "Cancel by user"){
        $bg_color = "warning";
        $title = strtoupper("Donation Cancellation Confirmed");
        $message = "Your request to <b> cancel </b> the donation has been processed successfully. No transaction has been recorded on your bank account.";
        $noted = "For any inquiries or further details about your donation, please feel free to contact us at  <b> 012-1234567 and ask for Mr. Jacky </b>. We're here to help!";

    }else if($_SESSION["donate_status"] === "Cancel by user, but got transaction"){
        $bg_color = "danger";
        $title = strtoupper("Donation Cancellation Notice");
        $message = "We've noted your request to <b>cancel</b> the donation. However, a transaction <b>(RM  $total_received_amount )</b> has been detected in our records.";
        $noted = "Please contact us at <b> 012-1234567 and ask for Mr. Jacky </b> to initiate the refund process. We will guide you through the necessary steps and provide any assistance you may need.";
        // sendEmail($email, $full_name, $seat_number, $amount, $donate_amount, $total, $total_received_amount, $_SESSION["donate_status"], 0, 1);
    }
    
    // Unset all session variables
    $_SESSION = array();

    mysqli_close($conn); // Close the connection before returning

}else if(isset($_SESSION["donate_status"]) && isset($_SESSION['bank_holder_name'])){
    
    $getTrasResult = getTransactionValue($conn, $_SESSION['bank_holder_name'], $load_donate_page_time);
    if ($getTrasResult[0] != 0 ){
        $total_received_amount = $getTrasResult[0];
    }

    if($_SESSION["donate_status"] === "Anonymously donate"){
        $bg_color = "success";
        $title = strtoupper("Thank You for Your Donation!");
        $message = "Your donation has been <b> successfully received. </b> We sincerely appreciate your support and generosity. Your contribution is making a difference at MACO.";
        $noted = "For any inquiries or further details about your donation, please feel free to contact us at  <b> 012-1234567 and ask for Mr. Jacky </b>. We're here to help!";
            
    }else if($_SESSION["donate_status"] === "Unknow user cancel donate"){
        $bg_color = "danger";
        $title = strtoupper("Donation Cancellation Notice");
        $message = "We've noted your request to <b> cancel</b> the donation. However, a transaction <b>(RM $total_received_amount )</b> has been detected in our records.";
        $noted = "Please contact us at <b> 012-1234567 and ask for Mr. Jacky </b> to initiate the refund process. We will guide you through the necessary steps and provide any assistance you may need.";
    }

    // Unset all session variables
    $_SESSION = array();

    mysqli_close($conn); // Close the connection before returning

}else if(isset($_SESSION["donate_status"])){

    if($_SESSION["donate_status"] === "Anonymously donate"){
        $bg_color = "success";
        $title = strtoupper("Thank You for Your Donation!");
        $message = "Your donation should be <b> successfully received. </b> We sincerely appreciate your support and generosity. Your contribution is making a difference at MACO.";
        $noted = "For any inquiries or further details about your donation, please feel free to contact us at  <b> 012-1234567 and ask for Mr. Jacky </b>. We're here to help!";
    }

    // Unset all session variables
    $_SESSION = array();
}
else{
    header("Location: ./");
    die("Redirect to main.php");
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
                <a href="./" class="fs-5"> BACK</a>
            </div>
        </div>
    </div>
    <script src="./statics/js/concert.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

<html>