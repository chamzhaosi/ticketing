<?php

session_start();

if (isset($_SESSION['seat_number']) && isset($_SESSION['amount'])) {
    
    $amount = $_SESSION['amount'];
    $load_into_psn_page_time = $_SESSION['load_into_psn_page_time'];
    $error_message = "";
    $show_qr_code_modal = false;
    
    if (time() - $load_into_psn_page_time > 5 * 60){
        header('Location: ./purchase.php');
        die("Redirecting to purchase page!");
    }


    include_once "db_connector.php";
    include_once "db_operator.php";
    include_once "check_data_valid.php";

    // $seat_number = $_SESSION['seat_number'];
    $seat_number = implode(",", array_map(function($val) {
        return "$val";
    },$_SESSION['seat_number']));

    // echo $seat_number;

    // check whether over the time, when submit the form
    // if (time() - $load_into_psn_page_time > 5 * 60){
    //     // Status List
    //     // 1. Match, Full Payment
    //     // 2. Match, Less Payment
    //     // 3. Match, More Payment
    //     // 4. Not Match
    //     // 5. Time Over
    //     // 6. User cancel order

    //     // $_SESSION['page_error'] = "";
    //     // // time up, but have user id, seat id, donate id, insert to payment table with status
    //     // if(isset($_SESSION['user_id']) && isset($_SESSION['seat_id'])){
    //     //     $donate_id = null;
    //     //     if(isset($_SESSION['donate_id'])){
    //     //         $donate_id = $_SESSION['donate_id'];
    //     //     }
    //     //     $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 5, null, 0.00, 0, "Time is up!");
    //     // } @@@@@@@@@@@@@@

    //     // // Redirect to a different page and then stop script execution
    //     // $_SESSION['page_error'] .= "Time has elapsed! Please try again!";
    //     header('Location: ./purchase.php');
    //     die("Redirecting to purchase page!");
    // }

    // locked the seat
    if (!select_all_query_by_value($conn, "locked_seat",  "seat_number", "s", $seat_number)[0]){
        $insert_result = insert_query($conn, "locked_seat", "seat_number", "s", $seat_number);
        if($insert_result [0] != true){
            // $_SESSION['page_error'] = $insert_result[1];
            // $_SESSION['page_error'] = "Error occurred while inserting the locked seat number into the database, Please try again!";
            // header('Location: ./purchase.php');
            // die("Redirecting to purchase page!");
            $error_message .= create_alert_div("Error occurred while inserting the locked seat number into the database, Please try again!");
        }else{
            $_SESSION['locked_id'] = $insert_result [1];
        }
    }

    // Don't care above the email or phone is exist or not
    if ($_SERVER["REQUEST_METHOD"] == "POST"){
        include_once "check_data_valid.php";

        // check everythin whether valid or not
        if(isset($_POST['fullnameInput'])){
            if(!isNonEmptyNoDigits($_POST['fullnameInput'])){
                $error_message .= create_alert_div("Full name cannot be <b> empty and contain digit </b>!");
            }
        }

        // check if check the box, then the fullname and bank account name must same
        if(isset($_POST['checkBankAccountName'])){
            if(!isBothValueSame($_POST['bankAccountNameInput'], $_POST['fullnameInput'])){
                $error_message .= create_alert_div("Full name and bank account name <b> must be same </b>, if the checkbox is <b> checked </b>!");
            }
        }else{
            if(!isNonEmptyNoDigits($_POST['bankAccountNameInput'])){
                $error_message .= create_alert_div("Bank holder name cannot be <b> empty and contain digit </b>!");
            }
        }

        // check the email form validation
        if(isset($_POST['emailInput'])){
            $email = trim($_POST['emailInput']);
            if(!isValidEmail($email)){
                $error_message .= create_alert_div("Email format <b>invalid</b>!");
            }
        }

        // check the email form validation
        if(isset($_POST['phone_prefix'])){
            if(!validPhonePrefix($_POST['phone_prefix'])){
                $error_message .= create_alert_div("Please choose a valid <b>phone prefix</b>!");
            }
        }

        // check the email form validation
        if(isset($_POST['phoneInput'])){
            $phone = trim($_POST['phoneInput']);
            if(!isValidPhoneNumberLength($phone)){
                $error_message .= create_alert_div("Phone number must not <b>less than 7 digits and more than 8 digits</b>! (Not include prefixe)");
            }
        }

        // check the donate function not digit
        if(isset($_POST['donation_support'])){
            if(!isNonEmptyNoLetter($_POST['donation_support'])){
                $error_message .= create_alert_div("Donate amount cannot be <b> contain letter </b>!");
            }
        }

        // insert user detail to db
        if ($error_message === "" && !isset($_SESSION['user_id'])){
            $columnNames = array("full_name", "email", "phone_number", "bank_account_name", "attend", 'support_only');
            $values = array($_POST['fullnameInput'], $_POST['emailInput'], ($_POST['phone_prefix']."-".$_POST['phoneInput']), $_POST['bankAccountNameInput'], 1, 0);
            $insert_result = insert_query($conn, "user_detail", $columnNames, "ssssdd", $values);
            if($insert_result[0]!= true){
                // $_SESSION['page_error'] = $insert_result[1];
                // $_SESSION['page_error'] = "Error occurred while inserting the user detail into the database, Please try again!";;
                // header('Location: ./purchase.php');
                // die("Redirecting to purchase page!");
                $error_message .= create_alert_div("Error occurred while inserting the user detail into the database, Please try again!");
            }else{
                $_SESSION['user_id'] = $insert_result[1];
            }
        }else if($error_message === ""){
            // update user detial to db
            $datas = ["full_name"=>$_POST['fullnameInput'], "email"=>$_POST['emailInput'], "phone_number"=>($_POST['phone_prefix']."-".$_POST['phoneInput']), "bank_account_name"=>$_POST['bankAccountNameInput']];
            $update_result = update_query($conn, "user_detail",  $datas, $_SESSION['user_id']);
            if($update_result[0] != true){
                // $_SESSION['page_error'] = "Error occurred while updating the user detail into the database, Please try again!";;
                // header('Location: ./purchase.php');
                // die("Redirecting to purchase page!");
                $error_message .= create_alert_div("Error occurred while updating the user detail into the database, Please try again!");
            }
        }
        
        // insert donate detail to db
        if ($error_message === "" && isset($_SESSION['user_id']) && $_POST['donation_support']!= "" && !isset($_SESSION['donate_id'])){
            $insert_result = insert_query($conn, "donate_detail", "total_amount", "s", $_POST['donation_support']);
            if($insert_result[0]!= true){
                // $_SESSION['page_error'] = $insert_result[1];
                // $_SESSION['page_error'] = "Error occurred while inserting the donate amount into the database, Please try again!";;
                // header('Location: ./purchase.php');
                // die("Redirecting to purchase page!");
                $error_message .= create_alert_div("Error occurred while inserting the donate amount into the database, Please try again!");
            }else{
                $_SESSION['donate_id'] = $insert_result[1];
            }
        }else if($error_message === "" && isset($_SESSION['user_id']) && $_POST['donation_support']!= ""){
            // update donate amount to db
            $datas = ["total_amount"=>$_POST['donation_support']];
            $update_result = update_query($conn, "donate_detail",  $datas, $_SESSION['donate_id']);
            if($update_result[0] != true){
                // $_SESSION['page_error'] = "Error occurred while updating the donate amount into the database, Please try again!";
                // header('Location: ./purchase.php');
                // die("Redirecting to purchase page!");
                $error_message .= create_alert_div("Error occurred while updating the donate amount into the database, Please try again!");
            }
        }

        // insert locked_seat to seat_detial to db
        if($error_message === "" && isset($_SESSION['seat_number']) && isset($_SESSION['amount']) && !isset($_SESSION['seat_id'])){
            $insert_result = insert_query($conn, "seat_detail", ["seat_number","total_amount"], "ss", [$seat_number, $amount]);
            if($insert_result[0] != true){
                $error_message .= create_alert_div("Error occurred while inserting the seat data into the database, Please try again!");
            }else{
                $_SESSION['seat_id'] = $insert_result[1];
            }
        }else if($error_message === "" && isset($_SESSION['seat_number']) && isset($_SESSION['amount'])){
            // update locked_seat to seat_detial to db
            $datas = ["seat_number"=>$seat_number ,"total_amount"=>$amount];
            $update_result = update_query($conn, "seat_detail",  $datas, $_SESSION['seat_id']);
        }

        // pop up / navigation to QR page (allowing 2 min to scan and wait until 3 min)
        if($error_message === ""){
            if (!isset($_SESSION['load_into_qr_page_time'])){
                $_SESSION['load_into_qr_page_time'] = time();
            }
            header('Location: qr_code_scan.php');
        }

    }else if(isset($_SESSION['user_id']) && isset($_SESSION['seat_id'])){
        $select_result = select_all_query_by_value($conn, "user_detail", "id", "i", $_SESSION['user_id']);
        if($select_result[0]){
            $_POST['fullnameInput'] = $select_result[1][0]["full_name"];
            $_POST['emailInput'] = $select_result[1][0]["email"];
            $_POST['bankAccountNameInput'] = $select_result[1][0]["bank_account_name"];
            $phone_number = explode("-", $select_result[1][0]["phone_number"]);

            $_POST['phone_prefix'] = $phone_number[0];
            $_POST['phoneInput'] = $phone_number[1];
            $_POST['checkBankAccountName'] = $_POST['fullnameInput'] === $_POST['bankAccountNameInput'] ? "no" : "";
        }
        
        if(isset($_SESSION['donate_id'])){
            $select_result = select_all_query_by_value($conn, "donate_detail", "id", "i", $_SESSION['donate_id']);
            if($select_result[0]){
                $_POST['donation_support'] = $select_result[1][0]["total_amount"];
            }
        }
    }
    // $conn->close();
    mysqli_close($conn); // Close the connection before returning

}else{
    // Redirect to a different page and then stop script execution
    header('Location: ./purchase.php');
    die("Redirecting to purchase page!");
}

?>

<!DOCTYPE html>
<head>
    <title>MACO concert</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./statics/css/concert.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>

<body class="container bg-secondary-subtle vh-100 d-flex justify-content-center align-items-xl-center align-items-start">
    <div id="loadingContainer" class="d-flex justify-content-center align-items-center vh-100 d-block">
      <div class="loader"></div>
    </div>

<div id="contentContainer" class="d-none">
   <div class="card">
    <div class="card-body">
        <h2 class="card-title">Personal Information</h2>
        <h6 class="card-subtitle mb-4 text-body-secondary">Please fill in your information</h6>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="mt-2" id="personal_detial_payment_form">
                <?php
                    if($error_message != ""){
                        echo $error_message;
                    }
                ?>
                <div class="row d-flex justify-content-around">
                    <div class="col-lg-6 col-12">
                        <div id="fullNameDiv" class="mb-3">
                            <label for="fullnameInput" class="form-label" >Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fullnameInput" name="fullnameInput" placeholder="Jacky Wong Jun Ann" required value="<?php echo isset($_POST['fullnameInput']) ? $_POST['fullnameInput'] : "" ?>" <?php echo isset($_POST['fullnameInput']) ? "readOnly" : "" ?>>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="checkBankAccountName" name="checkBankAccountName" <?php echo isset($_POST['checkBankAccountName']) ? "checked" : "" ?>>
                            <label class="form-check-label" for="checkBankAccountName">Is the bank account or holder name you will use to pay us the same as your full name?</label>
                        </div>

                        <div class="mb-3">
                            <label for="bankAccountNameInput" class="form-label">Bank Holder Name <span class="text-danger">*</span> 
                            <!-- Button trigger modal -->
                                <span type="button" class="text-primary" data-bs-toggle="modal" data-bs-target="#ImportantNote">
                                    More info
                                </span>
                            </label>
                            <input type="text" class="form-control" id="bankAccountNameInput" name="bankAccountNameInput" aria-describedby="bankAccountNameHelp" placeholder="Jacky Wong Jun Ann" required value="<?php echo isset($_POST['bankAccountNameInput']) ? $_POST['bankAccountNameInput'] : "" ?>" <?php echo isset($_POST['bankAccountNameInput']) ? "readOnly" : "" ?>>
                            <div id="bankAccountNameHelp" class="form-text">Please enter your bank account / holder name for us to confirm your payment! </div>
                        </div>

                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="emailInput" name="emailInput" aria-describedby="emailHelp" required placeholder="jacky@gmail.com" value="<?php echo isset($_POST['emailInput']) ? $_POST['emailInput'] : "" ?>">
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="phoneInput" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <div class="d-flex justify-content-start row">
                                <div class="col-3">
                                    <select class="form-select" aria-label="Default select example" id="phone_prefix" name="phone_prefix">
                                    <?php
                                        for ($i = 11; $i <= 19; $i++) {
                                            if(isset($_POST['phone_prefix'])){
                                                $flag = $_POST['phone_prefix'] === "0".$i ? "selected" : "";
                                            }
                                            $value = str_pad($i, 3, "0", STR_PAD_LEFT); // Pad the number with zeros to make it three digits
                                            echo "<option value='$value' $flag>$value</option>";
                                        }
                                    ?>
                                    </select>
                                </div>
                                <input type="text" class="form-control col" id="phoneInput" name="phoneInput" required placeholder="23456789" value="<?php echo isset($_POST['phoneInput']) ? $_POST['phoneInput'] : "" ?>"> 
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 m-auto text-center">
                        <h3>Time Out</h3>
                        <div id="time_out_message" class="alert alert-warning" role="alert">
                            <p id="load_into_psn_page_time" class="d-none">
                                <?php
                                    echo htmlspecialchars($load_into_psn_page_time);
                                ?>
                            </p>
                            Please note, you must complete this form within <b> 300 seconds</b>. Failure to do so will be considered as forfeiting the payment.
                        </div>

                        <h3 class="mb-3 pb-2 border-bottom">Payment Summary</h3>
                        <div class="d-flex justify-content-center row">
                            <h5 class="col-5">Seat number</h5>
                            <p id="selected_seat_detial" class="col-5">
                                <?php
                                    echo htmlspecialchars($seat_number);
                                ?>
                            </p>
                        </div>
                        <div class="d-flex justify-content-center row">
                            <h5 class="col-5">Sub-total</h5>
                            <p id="sub_amount" class="col-5">
                                <?php
                                    echo "RM ".htmlspecialchars($amount) . ".00"
                                ?>
                            </p>
                        </div>

                        <div class="d-flex justify-content-center row">
                            <h5 class="col">Donation & Support (RM)</h5>
                            <input id="donation_support" name="donation_support" class="form-control form-control-sm col me-5 text-center" type="text" placeholder="" aria-label=".form-control-sm example" value="<?php echo isset($_POST['donation_support']) ? $_POST['donation_support'] : "" ?>">
                        </div>

                        <div class="border-bottom border-top p-3 mt-5">
                            <div class="d-flex justify-content-center row ">
                                <h5 class="col">Total amount</h5>
                                <p id="total_amount" class="col h3"> 
                                    <?php
                                        echo "RM ".htmlspecialchars($amount) . ".00"
                                    ?>  
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <!-- Cancel Button -->
                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary">Payment</button>
                </div>
            </form>
        </div>
    </div>
   </div>
    <script src="./statics/js/concert.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
</body>

</html>

<!-- Important Note -->
<div class="modal fade" id="ImportantNote" tabindex="-1" aria-labelledby="ImportantNoteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="ImportantNoteLabel">More Information</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul>
            <li>
                As our payment process exclusively accommodates <b> DuitNow payments via QR code </b>, we kindly request the name of the corresponding <b>bank account holder</b>.
            </li>
            <li>
                For example:<br>
                If you wish to proceed with the payment using a bank account registered under
                <ul>
                    <li>
                        <b>your name </b>, please simply <b>mark</b> the checkbox provided above;
                    </li>
                    <li>
                        <b>another individual</b>, enter <b>that person's name</b> in the input field provided below;
                    </li>
                    <li>
                        <b>a company name</b>, please enter <b>that company's name</b> in the input field below.
                    </li>
                </ul>
            </li>
            <li>
                This information will enable us to <b> verify your payment transaction at a later stage </b>.
            </li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Closed</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                The data will be irrevocably deleted. Do you wish to proceed?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Confrim</button>
            </div>
        </div>
    </div>
</div>

