<?php

session_start();
include_once "db_connector.php";
include_once "db_operator.php";
include_once "check_data_valid.php";

// has load to the donate page and enter the bank holder name before
if (isset($_SESSION['load_donate_page_time']) && isset($_SESSION['bank_holder_name'])){
  $total_received_amount = 0;

  $getTrasResult = getTransactionValue($conn, $_SESSION['bank_holder_name'], $_SESSION['load_donate_page_time']);  
  $total_received_amount = $getTrasResult[0];

  // user select cancel donate
  if(isset($_POST["user_id"]) && isset($_POST["cancelBtnClicked"]) && $total_received_amount > 0){
    cancelUserDonateWithAmount($conn, $total_received_amount, $getTrasResult[2]);
  }else if(isset($_POST["user_id"]) && isset($_POST["cancelBtnClicked"])){
    cancelUserDonate($conn);
  }else if(isset($_POST["cancelBtnClicked"]) && $total_received_amount > 0){
    cancelWithoutUserDonate($conn, $total_received_amount, $getTrasResult[2]);
  }// user change url
  else if($total_received_amount > 0){
    anonymousDonate($conn, $total_received_amount, $getTrasResult[2]);
  }
}

$donate_id = null;
if(isset($_SESSION['donate_id'])){
    $donate_id = $_SESSION['donate_id'];
}

if(isset($_SESSION['user_id']) && isset($_SESSION['seat_id']) && isset($_SESSION['load_into_qr_page_time'])){
  $load_into_qr_page_time = $_SESSION['load_into_qr_page_time'];
  $load_into_psn_page_time = $_SESSION['load_into_psn_page_time'];
  $total_received_amount = 0;

  $select_result = select_all_query_by_value($conn, "seat_detail", "id", "i", $_SESSION['seat_id']);
    // echo $select_result[0];
    if($select_result[0]){
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
        }
    } 
    
  $getTrasResult = getTransactionValue($conn, $bank_holder_name, $load_into_qr_page_time);
  if ($getTrasResult[0] != 0 ){
      $total_received_amount = $getTrasResult[0];
  }

  // if (time() - $load_into_psn_page_time > 7 * 60 + 45){
  //   timeOverPayment($conn, $donate_id);
  // }else 

  // if time is up, but not transaction record
  if((time() - $load_into_psn_page_time) > 5 * 60 && $total_received_amount === 0){
    $_SESSION['time_up'] = true;
    timeOverPayment($conn, $donate_id);
  }// user press cancel btn, but got transaction record

  else if(isset($_POST['cancelBtnClicked']) && $total_received_amount > 0){
    cancelUserPaymentWithAmount($conn, $donate_id, $total_received_amount, $getTrasResult[2]);
  }// but not transaction record

  else if($total_received_amount === 0){
    cancelUserPayment($conn, $donate_id);
  }// if haven't up, but got transaction record

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
  
}else if(isset($_SESSION['user_id']) && isset($_SESSION['seat_id'])){
  cancelUserPayment($conn, $donate_id);
}

if(isset($_SESSION['locked_id'])) {
  // delete locked seat data from db
  if(!delete_query($conn, "locked_seat", "id", "s", $_SESSION['locked_id'])){
    echo "Locked seat id: ".$_SESSION['locked_id']." unsucessfully delete!";
  }
  unset($_SESSION['locked_id']);
}
function cancelWithoutUserDonate($conn, $total_received_amount, $idFound){
  $insert_result = insertPaymentToDB($conn, null, null, null, 8,  "Duit Now", $total_received_amount, 0, "Unknow user cancel donate, transaction id: " .$idFound);
  if(!$insert_result[0]){
      $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
  }

  $_SESSION["donate_status"] = "Unknow user cancel donate";
  header("Location: ./donate_status.php");
  die("Redirect to donate status page");
}

function anonymousDonate($conn, $total_received_amount, $idFound){
  $insert_result = insertPaymentToDB($conn, null, null, null, 7,  "Duit Now", $total_received_amount, 1, "Anonymously donate, transaction id: " .$idFound);
  if(!$insert_result[0]){
      $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
  }

  $_SESSION["donate_status"] = "Anonymously donate";
  header("Location: ./donate_status.php");
  die("Redirect to donate status page");
}

function cancelUserPaymentWithAmount($conn, $donate_id, $total_received_amount, $idFound){
  $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 6, "Duit Now", $total_received_amount, 0, "User redirect from qr code page / cancel make payment, but found the transaction id: " .$idFound);
  if(!$insert_result[0]){
      $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
  }

  $_SESSION["payment_status"] = "Cancel by user, but got transaction";
  header("Location: ./payment_status.php");
  die("Redirect to payment status page");
}

function cancelUserDonateWithAmount($conn, $total_received_amount, $idFound){
  $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], null, null, 6,  "Duit Now", $total_received_amount, 0, "User redirect from qr code page / cancel make payment, but found the transaction id: " .$idFound);
  if(!$insert_result[0]){
      $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
  }

  $_SESSION["donate_status"] = "Cancel by user, but got transaction";
  header("Location: ./donate_status.php");
  die("Redirect to donate status page");
}

function cancelUserPayment($conn, $donate_id){
  $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], $_SESSION['seat_id'], $donate_id, 6, null, 0.00, 0, "User redirect from qr code page / cancel make payment");
  if(!$insert_result[0]){
      $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
  }

  $_SESSION["payment_status"] = "Cancel by user";
  header("Location: ./payment_status.php");
  die("Redirect to payment status page");
}

function cancelUserDonate($conn){
  $insert_result = insertPaymentToDB($conn, $_SESSION['user_id'], null, null, 6, null, 0.00, 0, "User redirect from qr code page / cancel make payment");
  if(!$insert_result[0]){
      $_SESSION['page_error'] = "Error occurred while inserting the payment detail into the database";
  }

  $_SESSION["donate_status"] = "Cancel by user";
  header("Location: ./donate_status.php");
  die("Redirect to doante status page");
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


if (!empty($_POST) && isset($_POST['seat_number']) && isset($_POST['amount'])) {

  $seat_number = str_replace(" ", "", $_POST['seat_number']);
  // $amount = $_POST['amount'];

  // convert to array first
  $seat_number_array = explode (",", $seat_number);
  // print_r($seat_number); // Array ( [0] => D29 [1] => E29 )

  foreach ($seat_number_array as $seat_id){
    // echo $seat_id;
    // check whehter the seat_id exist or not in the locked_seat table
    if(select_all_query_by_value($conn, 'locked_seat', 'seat_number', 's', $seat_id)[0]){
      $error_message = 'Sorry, the seat <b> '.  htmlspecialchars($seat_id) .' </b> has been buy by someone in few minutes ago!';
      break;
    }// if not problem, then check the selled seat_detail table
    else{
      // echo $seat_id;
      $select_result = select_all_query_by_value($conn, 'seat_detail', 'seat_number', 's', $seat_id);
      if ($select_result[0]){
        $seat_db_id = $select_result[1][0]["id"];

        // after get the id, search with payment detail table to know payment status
        $select_result = select_all_query_by_value($conn, 'payment_detail', 'seat_id', 'i', $seat_db_id);
        if($select_result[0]){
          // if the status is more payment or full payment
          if ($select_result[1][0]["status_id"] === 1 || $select_result[1][0]["status_id"] === 3){
            $error_message = 'Sorry, the seat <b> '.  htmlspecialchars($seat_id) .' </b> has been buy by someone in few minutes ago!';
            break;
          }
        }
      }
    }
  }

  if (empty($error_message)){
    // if both are not problem, then record the data and redirect to personal_payment page
    $_SESSION['seat_number'] = $seat_number_array;
    $_SESSION['amount'] = $_POST['amount'];
    $_SESSION['load_into_psn_page_time'] = time();

    // Redirect
    header('Location: personal_detail_payment.php');
  }

  mysqli_close($conn); // Close the connection before returning
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
<body class="bg-secondary-subtle container-fluid">
    <div id="loadingContainer" class="d-flex justify-content-center align-items-center vh-100 d-block">
      <div class="loader"></div>
    </div>

    <div id="contentContainer" class="d-none">
      <?php

        if (isset($_SESSION['page_error'])){
          echo '<div class="alert alert-danger m-2" role="alert">'
          . $_SESSION['page_error'] .
          '</div>';

            unset($_SESSION['page_error']);
        }

        if (!empty($error_message)){
          echo '<div class="alert alert-danger m-2" role="alert">'
                . $error_message .
                '</div>';
        }

        ?>
      <div class="text-center">
          <div class="row justify-content-center align-items-center">
              <div class="col-auto">
                  <a href="./"><i class="fa-solid fa-arrow-left fs-3"></i></a>
              </div>
              <h2 class="col text-center">Purchase and select your seat!</h2>
          </div>
      </div>


         <!-- Seat Layout -->
      <div class="d-flex justify-content-center row m-0">
          <!-- Stage Layout -->
          <div class="text-center bg-secondary text-light">
              <h3>Stage</h3>
          </div>

          <!-- Stall Layout -->
          <div>
              <h3 class="text-center my-2">Stall</h3>
              <div id="stall" class="d-flex justify-content-xl-center justify-content-start h_bar" >
              </div>
          </div>

          <!-- Circle Layout -->
          <div class="border-top border-dark">
              <h3 class="text-center my-2">Circle</h3>
              <div id="circle" class="d-flex justify-content-xl-center  justify-content-start h_bar">
              </div>
          </div>
      </div>

        <!--Search Bar-->
        <div class="d-flex justify-content-around row m-3">        
          <!--Seat summary-->
          <div id="color_summary" class="text-center col-md-6 col-12">
              <div class="row d-flex justify-content-around">
                  <div class="col-5 w-50">
                      <table class="table">
                          <thead>
                            <tr class="bg-secondary-table">
                              <th scope="col">Price</th>
                              <th scope="col">Balance</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr id="red_row" class="red-table">
                              <td>RM 268</td>
                              <td>30 / 128</td>
                            </tr>
                            <tr id="orange_row" class="orange-table">
                              <td>RM 0</td>
                              <td>30 / 128</td>
                            </tr>
                            <tr id="yellow_row" class="yellow-table">
                              <td>RM 188</td>
                              <td>30 / 128</td>
                            </tr>
                          </tbody>
                      </table>
                  </div>

                  <div class="col-5 w-50">
                      <table class="table">
                          <thead>
                            <tr class="bg-secondary-table">
                              <th scope="col">Price</th>
                              <th scope="col">Balance</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr id="green_row" class="green-table">
                              <td>RM 88</td>
                              <td>30 / 128</td>
                            </tr>
                            <tr id="blue_row" class="blue-table">
                              <td>RM 128</td>
                              <td>30 / 128</td>
                            </tr>
                            <tr id="gray_row" class="gray-table">
                              <td>N/A</td>
                              <td>30 / 128</td>
                            </tr>
                          </tbody>
                        </table>
                  </div>
              </div>
          </div>

          <div id="payment_summary" class="col-md-5 col-12 m-auto text-center">
              <h2 class="border-secondary border-bottom">Purchase Summary</h2>
              <div class="d-flex justify-content-around row">
                  <h5 class="col-5">Seat</h5>
                  <p id="selected_seat_detial" class="col-5">-</p>
              </div>
              <div class="d-flex justify-content-around">
                  <h5 class="col-5">Amount</h5>
                  <p id="selected_amount" class="col-5">RM -</p>
              </div>

              <button id="check_out_btn" class="btn-danger btn" disabled>Check Out</button>
          </div>

          <div id="formContainer" class="d-none"></div>
        </div>
    </div>
    
    

    <script src="./statics/js/concert.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>
