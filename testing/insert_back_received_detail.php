<?php

if(isset($_POST)){
    include_once "db_connector.php";
    include_once "db_operator.php";

    $data = json_decode(file_get_contents('php://input'), true);

    $money = $data['money'];
    $name = $data['name'];
    $reference = $data['reference'];
    $load_into_qr_page_time = $data['load_into_qr_page_time'];

    // check reference no exist or not afthe the time user load the qr page
    if(!select_all_query_by_value_time($conn, "bank_received_detail", "ref_number", "s", $reference, $load_into_qr_page_time)[0]){
        $insert_result = insert_query($conn, "bank_received_detail", ["ref_number","received_amount", "payer_name"], "sss", [$reference, $money, $name]);
        if($insert_result[0]){
            echo json_encode($insert_result[1]);
        }else{
            echo json_encode("Error inserting data");;
        }
    }else{
        echo json_encode("Reference Number Duplicant");;
    }    
}

?>
