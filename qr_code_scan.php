<?php
// qr code

// back (still in five min) -> edit the from
// back (but fill in form time up) -> not paid yet, time up -> message:retry again -> redirect to purchase page
// cancel =  -> not paid yet, cancel by client -> redirect to purchase page
// utill time up -> utill time up user not intertup with -> message:retry again (no scan at all) // if scaned, but result, call us-> redirect to purchase page
// confirm = -> match, paid -> seat sold
            //-> match name, but amount less -> not paid enough money, retry scan, please do again all. (notice:) -> redirect to personal_detail_payment page
            //-> match name, but amount more -> paid more money, retry scan, will refund, please do again all. (notice:)-> redirect to personal_detail_payment page
            //-> match name, but amount less -> not paid enough money, retry scan, but time up, please do again all. (notice:) -> redirect to purchase page
            //-> match name, but amount more -> paid more money, retry scan, will refund, but time up, please do again all. (notice:) -> redirect to purchase page


//less payment -> first payment less, second or N time paid the balance, the option will base on the bank holder name to add up
//less payment -> first payment less, not idea to do, time up, our system will check whether the bank holder name exist, if exist automaticall insert to payment and let user know the next step (overall 8 min)
//less payment -> first payment less, 

//don't care match the bank holder name or not, i insert to db,
//but javascript base on the bank holder name update, 

// check db record whether the time after load qr page got the bank holder name bank in.

// (time haven't finish) if got the record, notice customer the record, let them double confirm, if select not, then not match, if yes but less/more, then given then a notice let them scan and pay the balance, if more then we will refund them balance.
// (time up) if got the record, insert the transaction with the order, notice users. (less / more status)

// (time haven't finish) if not record, then message, try again or contact us. (Not match status)
// (time up) if not record, then message, try again or contact us. (time over status)


session_start();
include_once "db_connector.php";
include_once "db_operator.php";
include_once "check_data_valid.php";

if(isset($_SESSION["payment_status"])){
    header("Location: ./payment_status.php");
    die("Time out, redirect to payment_status.php");
}

if(isset($_SESSION['seat_id']) && isset($_SESSION['user_id']) && isset($_SESSION['load_into_qr_page_time'])){
    $showSubmitAlertModal = false;
    $showSubmitAlertModalTitle = "";

    $load_into_psn_page_time = $_SESSION['load_into_psn_page_time'];
    $load_into_qr_page_time = $_SESSION['load_into_qr_page_time'];

    $select_result = select_all_query_by_value($conn, "seat_detail", "id", "i", $_SESSION['seat_id']);
    // echo $select_result[0];
    if($select_result[0]){
        $seat_number = $select_result[1][0]["seat_number"];
        $amount = $select_result[1][0]['total_amount'];
        $total = (float)$amount;
    }

    $select_result = select_all_query_by_value($conn, "user_detail", "id", "i", $_SESSION['user_id']);
    if($select_result[0]){
        $bank_holder_name = $select_result[1][0]["bank_account_name"];
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
    
    if (time() - $load_into_psn_page_time > 7 * 60 + 30){
        $_SESSION['time_up'] = true;
    
        // time has been over
        $total_received_amount = 0;
        
        $getTrasResult = getTransactionValue($conn, $bank_holder_name, $load_into_qr_page_time);
        if ($getTrasResult[0] != 0 ){
            $total_received_amount = $getTrasResult[0];
        }
    
        $donate_id = null;
        if(isset($_SESSION['donate_id'])){
            $donate_id = $_SESSION['donate_id'];
        }
    
        // but not transaction record
        if($total_received_amount === 0){
            timeOverPayment($conn, $donate_id);
        }// if got transaction record
        else if($total_received_amount > 0){
            // if match, full payment
            if ($total === $total_received_amount){
                // 1. Match, Full Payment
                matchFullPayment($conn, $donate_id, $getTrasResult[2], $total_received_amount);
    
            }// if received more than needed value
            else if($total < $total_received_amount){
                // 3. Match, More Payment
                matchMorePayment($conn, $donate_id, $getTrasResult[2], $total_received_amount, $total);
                
            }// if received less than needed value
            else if($total > $total_received_amount){
                // 2. Match, Less Payment
                matchLessPayment($conn, $donate_id, $getTrasResult[2], $total_received_amount, $total);
            }
        }
    }
}else{
    header("Location: ./purchase.php");
    die("Redirect to purchase page");
}


function notMatch($conn, $donate_id, $remark){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 4, null, 0.00, 0, $remark);
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["payment_status"] = "Not Match";
    header("Location: ./payment_status.php");
    die("Redirect to payment status page");
}

function matchFullPayment($conn, $donate_id, $idFound, $total_received_amount){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 1, "Duit Now", $total_received_amount, 0, "Transaction id: ".$idFound);
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["payment_status"] = "Match";
    header("Location: ./payment_status.php");
    die("Redirect to payment status page");
}

function matchMorePayment($conn, $donate_id, $idFound, $total_received_amount, $total){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 3, "Duit Now", $total_received_amount, 0, "Transaction id: ".$idFound);
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["payment_status"] = "Match, but payment more";
    $_SESSION["balance_amount"] = $total_received_amount - $total;
    header("Location: ./payment_status.php");
    die("Redirect to payment status page");
}

function matchLessPayment($conn, $donate_id, $idFound, $total_received_amount, $total){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 2, "Duit Now", $total_received_amount, 0, "Transaction id: ".$idFound);
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["payment_status"] = "Match, but payment less";
    $_SESSION["balance_amount"] = $total - $total_received_amount;
    header("Location: ./payment_status.php");
    die("Redirect to payment status page");
}

function timeOverPayment($conn, $donate_id){
    $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 5, null, 0.00, 0, "Time up without any transaction record!");
    if(!$insert_result[0]){
        $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
    }

    $_SESSION["payment_status"] = "Time Up";
    header("Location: ./payment_status.php");
    die("Redirect to payment status page");
}

if (isset($_POST['decisionValue'])){
    // Status List
    // 1. Match, Full Payment
    // 2. Match, Less Payment
    // 3. Match, More Payment
    // 4. Not Match
    // 5. Time Over
    // 6. User cancel order

    if (isset($_POST['paymentConfirmRadio'])){
        unset($_POST['paymentConfirmRadio']);
    }

    $total_received_amount = 0;
        
    $getTrasResult = getTransactionValue($conn, $bank_holder_name, $load_into_qr_page_time);
    if ($getTrasResult[0] != 0 ){
        $total_received_amount = $getTrasResult[0];
    }

    $donate_id = null;
    if(isset($_SESSION['donate_id'])){
        $donate_id = $_SESSION['donate_id'];
    }

    if ($_POST['decisionValue'] === "not me"){
        // insert to db, redirect to another page and notice them, if already make paid, plesea keep the receipt and contact us or try again.
        // 4. Not Match
        notMatch($conn, $donate_id, "Found a transaction ( $getTrasResult[2]) record, but the user stated that it was not initiated by him.");
        
    }else if ($_POST['decisionValue'] === "is me"){
        // then check the figure first is match or more then insert
       
        // if match, full payment
        if ($total === $total_received_amount){
            // 1. Match, Full Payment
            matchFullPayment($conn, $donate_id, $getTrasResult[2], $total_received_amount);

        }// if received more than needed value
        else if($total < $total_received_amount){
            // 3. Match, More Payment
            matchMorePayment($conn, $donate_id, $getTrasResult[2], $total_received_amount, $total);
            
        }// if received less than needed value
        else if($total > $total_received_amount){
            // 2. Match, Less Payment
            $showSubmitAlertModal = true;
            $showSubmitAlertModalTitle = "Important Notice";
            $balanceAmount = $total - $total_received_amount;
            $submitAlertMessage = "We have noted that the amount remitted for your transaction is <b> less (RM $balanceAmount.00) than the required total (RM $total.00).</b> Please make the payment <b> before the time is up</b>; failure to do so will result in your order being considered <b>unsuccessful!</b>";
        }
    }
}

// if time over, got $_POST['paymentConfirmRadio'] same as below way. (disabled javascript)
// echo isset($_POST['paymentConfirmRadio']);
if(isset($_POST['paymentConfirmRadio'])){
    $getTrasResult = getTransactionValue($conn, $bank_holder_name, $load_into_qr_page_time);  
    $total_received_amount = $getTrasResult[0];

    if ($_POST['paymentConfirmRadio'] == "Default Value"){
        // Status List
        // 1. Match, Full Payment
        // 2. Match, Less Payment
        // 3. Match, More Payment
        // 4. Not Match
        // 5. Time Over
        // 6. User cancel order
 
        if ($getTrasResult[0] != 0 ){
            $showSubmitAlertModal = true;
            $showSubmitAlertModalTitle = "Are you sure?";
            $submitAlertMessage = $getTrasResult[1];
            
        }else{
            // if false; means no record.
            // 4. Not Match
            notMatch($conn, $donate_id, "Not any transaction record found!");

            $_SESSION["payment_status"] = "Not Match";
            header("Location: ./payment_status.php");
            die("Redirect to payment status page");
        }

    }else if(strpos($_POST['paymentConfirmRadio'], str_replace(" ", "_", $bank_holder_name)) !== false){
        // if match, full payment
        if ($total === $total_received_amount){
            // 1. Match, Full Payment
            matchFullPayment($conn, $donate_id, $getTrasResult[2], $total_received_amount);

        }// if received more than needed value
        else if($total < $total_received_amount){
            // 3. Match, More Payment
            matchMorePayment($conn, $donate_id, $getTrasResult[2], $total_received_amount, $total);
            
        }// if received less than needed value
        else if($total > $total_received_amount){
            // 2. Match, Less Payment
            $showSubmitAlertModal = true;
            $showSubmitAlertModalTitle = "Important Notice";
            $balanceAmount = $total - $total_received_amount;
            $submitAlertMessage = "We have noted that the amount remitted for your transaction is <b> less (RM $balanceAmount.00) than the required total (RM $total.00).</b> Please make the payment <b> before the time is up</b>; failure to do so will result in your order being considered <b>unsuccessful!</b>";
        }
    } 
};

mysqli_close($conn); // Close the connection at the bottom, not here (!important)

?>

<!DOCTYPE html>

<head>
    <title> QR Code Scan</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./statics/css/concert.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

</head>
<body class="container bg-secondary-subtle d-flex justify-content-center align-items-xxl-center align-items-start vh-100">
    <div id="loadingContainer" class="d-flex justify-content-center align-items-center vh-100 d-block">
      <div class="loader"></div>
    </div>
        <div id="contentContainer" class="d-none">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">QR code</h2>
                <h6 class="card-subtitle mb-4 text-body-secondary text-center">Please scan the QR Code using your bank's mobile application.</h6>
                <div>
                    <div class="d-flex justify-content-around row">
                        <div class="col-lg-6 col-12 d-flex justify-content-center">
                            <!-- QR Code img -->
                            <img src="./statics/assets/qr_code.png" alt="qr_code" width="70%" height="auto">
                        </div>

                        <div class="col-lg-6 col-12 m-auto text-center">
                            <!-- Payment Summary -->
                                <h3>Time Out</h3>
                                <div id="time_out_message" class="alert alert-warning" role="alert">
                                    Please note, you must complete this transaction within <b> 100 seconds</b>. Failure to do so will be considered as forfeiting the payment.
                                </div>

                                <h3 class="mb-3 pb-2 border-bottom">Payment Summary</h3>
                                <div class="d-flex justify-content-center row">
                                    <h5 class="col-5">Seat number</h5>
                                    <p id="selected_seat_detial" class="col-5 text-end">
                                        <?php
                                            echo htmlspecialchars($seat_number);
                                        ?>
                                    </p>
                                </div>
                                <div class="d-flex justify-content-center row">
                                    <h5 class="col-5">Sub-total</h5>
                                    <p id="sub_amount" class="col-5 text-end">
                                        <?php
                                            echo "RM ".htmlspecialchars($amount) . ".00"
                                        ?>
                                    </p>
                                </div>

                                <div class="d-flex justify-content-center row">
                                    <h5 class="col-5">Donation & Support</h5>
                                    <p id="sub_amount" class="col-5 text-end">
                                        <?php
                                            if($donate_amount != ""){
                                                if (floor($donate_amount) == $donate_amount) {
                                                    echo "RM " . htmlspecialchars($donate_amount) . ".00";
                                                } else {
                                                    echo "RM " . htmlspecialchars($donate_amount);
                                                }
                                                // echo "RM ".htmlspecialchars($donate_amount) . ".00";
                                            }else {
                                                echo "RM 0.00"; 
                                            }
                                        ?>
                                    </p>
                                </div>

                                <div class="border-bottom border-top p-3 mt-3">
                                    <div class="d-flex justify-content-center row">
                                        <h5 class="col">Total amount</h5>
                                        <p id="total_amount" class="col h3 text-end"> 
                                            <?php
                                                echo "RM ".htmlspecialchars($total) . ".00"
                                            ?>  
                                        </p>
                                    </div>
                                </div>
                            </div>
                    </div>
                    <div>
                        <!-- Form for select -->
                        <div class="alert alert-info col-lg-6 col-12 m-auto mt-2" role="alert">
                            Once you have scanned and completed the payment, please wait for a moment to finalize the confirmation.
                        </div>
                        <div class="d-flex justify-content-center my-3">
                            <form id="qr_form" method="POST">
                                <div id="radioButtonsContainer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="paymentConfirmRadio" id="paymentConfirmRadio" checked value="Default Value">
                                        <label class="form-check-label" for="paymentConfirmRadio">
                                            Select this option if your transaction is not visible in our system.
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-lg btn-warning" id="back_btn">Back</button>
                                    <!-- Cancel Button -->
                                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                        Cancel
                                    </button>
                                    <button type="button" class="btn btn-lg btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                        Confirm
                                    </button>
                                    <!-- <button type="submit" class="btn btn-lg btn-primary" id="confirm_btn">Confirm</button> -->
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>  
        </div>
    </div>
    <div id="decisionFromContainer"> </div>
    <script>
        var shouldShowModal = <?php echo $showSubmitAlertModal ? 'true' : 'false'; ?>;
        let load_into_psn_page_time = <?php echo $load_into_psn_page_time; ?>;
        var load_into_qr_page_time = <?php echo $load_into_qr_page_time; ?>;
        var bank_holder_name = "<?php echo $bank_holder_name; ?>";
    </script>
    <script src="./statics/js/concert.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
</body>

</html>

<!--Cancellation Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Delect Action</h5>
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

