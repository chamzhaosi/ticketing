<?php

function create_alert_div($message){
    $alertHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $alertHTML .= $message;
    $alertHTML .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $alertHTML .= '</div>';

    return $alertHTML;
}

function isNonEmptyNoDigits($fullName){
    // Trim the input string
    $trimmedFullName = trim($fullName);

    // Check if the string is not empty
    if ($trimmedFullName === "") {
        return false; // String is empty
    }

    // Check if the string contains digits
    if (preg_match('/\d/', $trimmedFullName)) {
        return false; // String contains digits
    }

    return true; // String is non-empty and contains no digits
}

function isBothValueSame($bankAccountName, $fullName){
    return trim($bankAccountName) === trim($fullName);
}

function isValidEmail($email){
    // return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    $regex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    return preg_match($regex, $email) === 1;
}

function validPhonePrefix($phonePrefix){
    $valid_prefixes = ['011', '012', '013', '014', '015', '016', '017', '018', '019'];
    return in_array($phonePrefix, $valid_prefixes);
}

function isValidPhoneNumberLength($phone){
    return preg_match('/^[0-9]{7,8}$/', $phone) === 1;
}

function isNonEmptyNoLetter($donateAmount){    
        // Check if the string contains letters
        if (preg_match('/[a-zA-Z]/', $donateAmount)) {
            return false; // String contains letters
        }
    
        return true; // String is non-empty and contains no letters
}

function getTransactionValue($conn, $bank_holder_name, $load_into_qr_page_time){
    $select_result = select_all_query_by_value_time($conn, "bank_received_detail", "payer_name", "s", $bank_holder_name, $load_into_qr_page_time);
    $total_received_amount = 0;
    $submitAlertMessage = "";
    $idFound = "";
    if($select_result[0]){
        // if true; means got record.
        // got record but user do not select it, then given a notice to let them know this case;
        $submitAlertMessage = "We have found the transaction record from the account associated with the bank holder's name you provided earlier <b>($bank_holder_name).</b> <br>";
        for ($i = 0; $i<count($select_result[1]); $i++){
            foreach ($select_result[1][$i] as $key => $value) {
                if ($key === "id"){
                    $idFound .= $value ." ";
                }
                if ($key === "received_amount"){
                    $total_received_amount += $value;
                    $submitAlertMessage .= "<p class='text-center mb-0'>Received amount -> RM " . $value . "</p>";
                }
            }
        }
        $submitAlertMessage .= "<h4 class='mt-2'>TOTAL RECEIVED RM $total_received_amount.00 </h4>";
    }
    return [$total_received_amount, $submitAlertMessage, $idFound];
}

function insertPaymentToDB($conn, $user_id, $seat_id, $donate_id, $status_id, $payment_type, $amount, $isAnonymouns, $remark){

    // insert to db, redirect to another page and notice them, if already make paid, plesea keep the receipt and contact us or try again.
    $columnNames = array("user_id", "seat_id", "status_id", "donate_id", "received_total_amount", "payment_type", "anonymous", "remark");
    $values = array($user_id, $seat_id, $status_id, $donate_id, $amount, $payment_type, $isAnonymouns, $remark);
    $insert_result = $insert_result = insert_query($conn, "payment_detail", $columnNames, "iiiissis", $values);
    
    return $insert_result;
}

?>