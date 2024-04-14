<?php

function identifyAndFormatInput($input) {
    // Regular expression for validating an email
    $emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    
    // Regular expression for validating a phone number (7 or 8 digits, with or without hyphen)
    $phonePattern = '/^(\d{3}-\d{7}|\d{3}-\d{8}|\d{10}|\d{11})$/';

    if (preg_match($emailPattern, $input)) {
        return ['type' => 'email', 'formatted' => $input];
    } elseif (preg_match($phonePattern, $input)) {
        // Format the phone number by inserting a hyphen after the first three digits if not already present
        if (!preg_match('/^\d{3}-/', $input)) {
            $formattedPhone = preg_replace('/^(\d{3})(\d{7,8})$/', '$1-$2', $input);
        } else {
            $formattedPhone = $input; // If hyphen is already present, use the original input
        }
        return ['type' => 'phone_number', 'formatted' => $formattedPhone];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include_once "db_connector.php";
    include_once "db_operator.php";
    // Assuming you're sending a JSON POST request
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if a specific key exists in the POST data
    if (isset($data["inputValue"])) {
        $seat_number_array = [];

        // Now you can access your data
        $inputValue = $data["inputValue"];

        $search_column = identifyAndFormatInput($inputValue)['type'];
        $search_value = identifyAndFormatInput($inputValue)['formatted'];
        // echo $search_column;?

        $select_value_result_1 = select_all_query_by_value($conn, "user_detail", $search_column, "s", $search_value);
        if($select_value_result_1[0]){

            foreach($select_value_result_1[1] as $result_row){
                $select_result = select_all_query_by_two_column_three_values($conn, "payment_detail", "status_id", "user_id", "iii", 1, 3, $result_row['id']);
                if ($select_result[0]){

                    foreach ($select_result[1] as $select_row) {
                        $seat_id = $select_row['seat_id'];
                        // echo $seat_id;
                        $select_value_result_2 = select_all_query_by_value($conn, 'seat_detail', 'id', 'i', $seat_id);
                        // echo  $select_value_result[0];
                        if ($select_value_result_2[0]){
                            $seat_number_string = $select_value_result_2[1][0]["seat_number"];
                            // echo  $select_value_result[1][0]["seat_number"];
                    
                            foreach (explode(",", $seat_number_string) as $seat_number) {
                                // echo $seat_number;
                                $seat_number_array[] = $seat_number; // Append the seat number directly
                                // echo $seat_number_array;
                            }
                        }
                    }
                }
            }
        }

        mysqli_close($conn); // Close the connection before returning

        header('Content-Type: application/json');
        // echo $seat_number_array;
        echo json_encode($seat_number_array);
    }
}


?>