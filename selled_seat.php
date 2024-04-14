<?php

if(isset($_POST)){
    include_once "db_connector.php";
    include_once "db_operator.php";

    $seat_number_array = [];

    // get all payment detial whit status id 1 and 3
    $select_result = select_all_query_by_one_column_two_values($conn, "payment_detail", "status_id", "ii", "1", "3");
    if ($select_result[0]){
        
        foreach ($select_result[1] as $select_row) {
            $seat_id = $select_row['seat_id'];
            // echo $seat_id;
            // echo "fadfasdf";
            $select_value_result = select_all_query_by_value($conn, 'seat_detail', 'id', 'i', $seat_id);
            // echo  $select_value_result[0];
            if ($select_value_result[0]){
                $seat_number_string = $select_value_result[1][0]["seat_number"];
                // echo  $select_value_result[1][0]["seat_number"];
        
                foreach (explode(",", $seat_number_string) as $seat_number) {
                    // echo $seat_number;
                    $seat_number_array[] = $seat_number; // Append the seat number directly
                    // echo $seat_number_array;
                }
            }
        }
    }

    mysqli_close($conn); // Close the connection before returning

    header('Content-Type: application/json');
    // echo $seat_number_array;
    echo json_encode($seat_number_array); 
}

?>