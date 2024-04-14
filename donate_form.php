<?php

session_start();
include_once "db_connector.php";
include_once "db_operator.php";
include_once "check_data_valid.php";
$error_message = "";
$showSubmitAlertModal = false;


// check the type of donate when submit
// if tick anonymousCheck, just has $_POST['anonymousCheck'])
if(!isset($_POST['donationType']) && $_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST['decisionValue'])){
    
        if (isset($_POST['paymentConfirmRadio'])){
            unset($_POST['paymentConfirmRadio']);
        }
    
        $total_received_amount = 0;
            
        $getTrasResult = getTransactionValue($conn, $_POST['bankAccountNameInput'], $_SESSION['load_donate_page_time']);  
        if ($getTrasResult[0] != 0 ){
            $total_received_amount = $getTrasResult[0];
        }
    
        if ($_POST['decisionValue'] === "not me"){
            notMatchDonate($conn, "Found a transaction ( $getTrasResult[2]) record, but the user stated that it was not initiated by him.");
            
        }else if ($_POST['decisionValue'] === "is me"){
            matchDonate($conn, $getTrasResult[2], $total_received_amount);
        }
    }

    // anonymously donate
    if(isset($_POST['anonymousCheck'])){
        $_SESSION["donate_status"] = "Anonymously donate";
        header("Location: ./donate_status.php");
        die("Redirect to donate status page");
    } // real name donate
    else{
        $error_message = "";

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

        // if($error_message != ""){
        //     $_POST = [];
        // }

        // insert user detail to db
        if ($error_message === "" && !isset($_SESSION['user_id'])){
            $columnNames = array("full_name", "email", "phone_number", "bank_account_name", "attend", 'support_only');
            $values = array($_POST['fullnameInput'], $_POST['emailInput'], ($_POST['phone_prefix']."-".$_POST['phoneInput']), $_POST['bankAccountNameInput'], 0, 1);
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

        // check payment option 
        if($error_message === "" && isset($_POST['paymentConfirmRadio'])){
            $getTrasResult = getTransactionValue($conn, $_POST['bankAccountNameInput'], $_SESSION['load_donate_page_time']);  
            $total_received_amount = $getTrasResult[0];
        
            if ($_POST['paymentConfirmRadio'] == "Default Value"){
                
                if ($getTrasResult[0] != 0 ){
                    $showSubmitAlertModal = true;
                    $showSubmitAlertModalTitle = "Are you sure?";
                    $submitAlertMessage = $getTrasResult[1];
                    
                }else{
                    notMatchDonate($conn, "Not any transaction record found!");
                }
        
            }else if(strpos($_POST['paymentConfirmRadio'], str_replace(" ", "_", $_POST['bankAccountNameInput'])) !== false){
                matchDonate($conn, $getTrasResult[2], $total_received_amount);
            } 
        };
    }
}else if(isset($_POST['donationType'])){
    $_SESSION['donationType'] = $_POST['donationType'];
    $_SESSION['load_donate_page_time'] = time();

    // to prevent the time be refreshed, when user refresh the page
    unset($_POST['donationType']);

    // Redirect to the same script
    //After this redirect, the page will reload as a GET request, and if the user refreshes the page, it won't resubmit the form data.
    header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF']));
    exit();

}else if(!isset($_SESSION['donationType'])){
    header("Location: ./");
    die("Redirect to main page");
}


function notMatchDonate($conn, $remark){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], null, 4, null, 0.00, 0, $remark);
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["donate_status"] = "Not Match";
    header("Location: ./donate_status.php");
    die("Redirect to donate status page");
} 

function matchDonate($conn, $idFound, $total_received_amount){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], null, 1, "Duit Now", $total_received_amount, 0, "Transaction id: ".$idFound);
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["donate_status"] = "Match";
    header("Location: ./donate_status.php");
    die("Redirect to donate status page");
} 

?>

<!DOCTYPE html>
<head>
    <title>Donation form</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./statics/css/concert.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>

<body class="container bg-secondary-subtle vh-100 d-flex justify-content-center align-items-xxl-center align-items-start">

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
                    <div id="formDiv" class=" <?php echo $_SESSION['donationType'] != "anonymous" ? "col-lg-6" : "" ?> col-12">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="anonymousCheck" name="anonymousCheck" <?php 
                                echo $_SESSION['donationType'] === "anonymous" ? "checked" : "";
                                ?>>
                            <label class="form-check-label" for="anonymousCheck">Donate Anonymously</label>
                        </div>

                        <div id="fullNameDiv" class="mb-3">
                            <label for="fullnameInput" class="form-label" >Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fullnameInput" name="fullnameInput" placeholder="Jacky Wong Jun Ann" <?php 
                                echo $_SESSION['donationType'] != "anonymous" ? "required" : "";
                                ?> value="<?php echo isset($_POST['fullnameInput']) ? $_POST['fullnameInput'] : "" ?>" <?php echo isset($_POST['fullnameInput']) ? "readOnly" : "" ?>>
                        </div>

                        <div id='checkboxDiv' class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="checkBankAccountName" name="checkBankAccountName" <?php echo isset($_POST['checkBankAccountName']) ? "checked" : "" ?>>
                            <label class="form-check-label" for="checkBankAccountName">Is the bank account or holder name you will use to pay us the same as your full name?</label>
                        </div>

                        <div id="bankAccountNameDiv" class="mb-3">
                            <label for="bankAccountNameInput" class="form-label">Bank Holder Name <span class="text-danger">*</span> 
                            <!-- Button trigger modal -->
                                <span type="button" class="text-primary" data-bs-toggle="modal" data-bs-target="#ImportantNote">
                                    More info
                                </span>
                            </label>
                            <input type="text" class="form-control" id="bankAccountNameInput" name="bankAccountNameInput" aria-describedby="bankAccountNameHelp" placeholder="Jacky Wong Jun Ann" <?php 
                                echo $_SESSION['donationType'] != "anonymous" ? "required" : "";
                                ?> value="<?php echo isset($_POST['bankAccountNameInput']) ? $_POST['bankAccountNameInput'] : "" ?>" <?php echo isset($_POST['bankAccountNameInput']) ? "readOnly" : "" ?>>
                            <div id="bankAccountNameHelp" class="form-text">Please enter your bank account / holder name for us to confirm your payment! </div>
                        </div>

                        <div id="emailDiv" class="mb-3">
                            <label for="emailInput" class="form-label">Email address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="emailInput" name="emailInput" aria-describedby="emailHelp" <?php 
                                echo $_SESSION['donationType'] != "anonymous" ? "required" : "";
                                ?>  placeholder="jacky@gmail.com" value="<?php echo isset($_POST['emailInput']) ? $_POST['emailInput'] : "" ?>">
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div id="phoneDive" class="mb-3">
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
                                <input type="text" class="form-control col" id="phoneInput" name="phoneInput" <?php 
                                echo $_SESSION['donationType'] != "anonymous" ? "required" : "";
                                ?>  placeholder="23456789" value="<?php echo isset($_POST['phoneInput']) ? $_POST['phoneInput'] : "" ?>"> 
                            </div>
                        </div>
                    </div>

                    <div id="qrDiv" class="<?php echo $_SESSION['donationType'] != "anonymous" ? "col-lg-6" : "" ?> col-12 m-auto text-center">
                        <div id="importantDiv" class="alert alert-warning" role="alert">
                            <h3>Important Note</h3>
                            Please fill in all the information, especially the <b> bank holder name </b>, before you scan the QR code to make the transaction!
                        </div>
                            <!--QR code-->
                            <img src="./statics/assets/qr_code.png" alt="qr_code" width="70%" height="auto">
                        </div>

                        <div id="transactionDiv" class="alert alert-info col-lg-6 col-12 m-auto mt-2" role="alert">
                            Once you have scanned and completed the payment, please wait for a moment to finalize the confirmation.
                        </div>
                        <div class="d-flex justify-content-center">
                            <div id="radioButtonsContainer" >
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentConfirmRadio" id="paymentConfirmRadio" checked value="Default Value">
                                    <label class="form-check-label" for="paymentConfirmRadio">
                                        Select this option if your transaction is not visible in our system.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                    <!-- Cancel Button -->
                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-lg btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
   </div>
   <div id="decisionFromContainer"> </div>
   <script>
    let load_donate_page_time = <?php echo $_SESSION['load_donate_page_time']; ?>;
    var shouldShowModal = <?php echo $showSubmitAlertModal ? 'true' : 'false'; ?>;
   </script>
    
<script src="./statics/js/donate.js"></script>
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
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Confirm Action</h5>
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

<!--Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you certain you wish to proceed?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmBtn">Yes</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="submitAlertModal" tabindex="-1" aria-labelledby="submitAlertModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h1 class="modal-title fs-5 text-light" id="submitAlertModalLabel"><?php echo htmlspecialchars($showSubmitAlertModalTitle) ?></h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php
        echo $submitAlertMessage;              
        ?>
      </div>
      <div class="modal-footer">
        <?php
            if($showSubmitAlertModalTitle === "Are you sure?"){
                echo '<button id="not_me_btn" type="button" class="btn btn-danger" data-bs-dismiss="modal">It\'s not me</button>';
                echo '<button id="is_me_btn" type="button" class="btn btn-success">It\'s me</button>';
            }else{
                echo '<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>';
            }
        ?>
      </div>
    </div>
  </div>
</div>