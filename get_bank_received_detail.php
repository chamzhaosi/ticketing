<?php

if(isset($_POST)){
    include_once "db_connector.php";
    include_once "db_operator.php";

    $data = json_decode(file_get_contents('php://input'), true);

    $bank_holder_name = $data['bank_holder_name'];
    $load_page_time = $data['load_page_time'];

    // check reference no exist or not afthe the time user load the qr page
    $select_result = select_all_query_by_value_time($conn, "bank_received_detail", "payer_name", "s", $bank_holder_name, $load_page_time);
    if($select_result[0]){
        echo json_encode($select_result[1]);
    }else{
        echo json_encode(false);
    }    
}

?>