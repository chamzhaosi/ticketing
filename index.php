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
  if(time() - $load_into_psn_page_time > 5 * 60 && $total_received_amount === 0){
    $_SESSION['time_up'] = true;
    timeOverPayment($conn, $donate_id);
  }// but got transaction record

  else if(isset($_POST['cancelBtnClicked']) && $total_received_amount > 0){
    cancelUserPaymentWithAmount($conn, $donate_id, $total_received_amount, $getTrasResult[2]);
  }// user press cancel btn, but not transaction record

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


// If you want to destroy the session completely, use session_destroy()
session_destroy();

mysqli_close($conn); // Close the connection before returning

?>

<!DOCTYPE html>
<head>
    <title>MACO concert</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./statics/css/concert.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>

<body class="bg-secondary-subtle">
    <div id="loadingContainer" class="d-flex justify-content-center align-items-center vh-100 d-block">
      <div class="loader"></div>
    </div>

    <div id="contentContainer" class="container-fluid d-none">
        <div><h4>DAMA</h4></div>

        <!--Banner-->
        <div class="d-flex justify-content-center bg-black row">
            <div class="col d-flex justify-content-center">
              <img id="poster" src="./statics/assets/poster.jpg" alt="concert_poster.jpg" >
            </div>
            
            <div class="col d-flex justify-content-center mt-2">
              <video id="concert_video" width="400" controls>
                  <source src="./statics/assets/video.mp4#t=8" type="video/mp4">
              </video>
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

            <form id="search_seat_form" class="col-md-5 col-12 m-auto">
                <div id="search_seat_div" class="mb-3">
                    <label for="searchSeat" class="form-label">Seach Your Seat (By Email or Phone Number)</label>
                    <input type="text" class="form-control" id="searchSeat" name="searchSeat" placeholder="abc@gmail.com / 012-34567890">
                </div>
                <button type="submit" class="btn btn-primary">Seach</button>
            </form>
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

        <div class="my-4">
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                      Purchase & Select Seat
                    </button>
                  </h2>
                  <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                      <a href="./purchase.php"> <button class="btn btn-primary">Purchase Tickets</button></a>
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                      Donate & Support
                    </button>
                  </h2>
                  <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#donateModal">
                      Donate
                    </button>
                    </div>
                  </div>
                </div>
              </div>
        </div>
    </div>
    <div id="form-container"></div>
    <script src="./statics/js/concert.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

</html>

<!-- Modal -->
<div class="modal fade" id="donateModal" tabindex="-1" aria-labelledby="donateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h1 class="modal-title fs-5" id="donateModalLabel">Donation Identity Preference</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        How would you like to proceed with your donation?
      </div>
      <div class="modal-footer">
        <button id="btn_anonymous" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Donate Anonymously</button>
        <button id="btn_real" type="button" class="btn btn-primary" data-bs-dismiss="modal">Donate with Real Name</button>
      </div>
    </div>
  </div>
</div>
