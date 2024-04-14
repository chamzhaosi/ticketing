<?php

function has_over_time($create_time){
    // Your given time
    // $timeZone = new DateTimeZone('Asia/Kuala_Lumpur'); // Time zone for Malaysia
    date_default_timezone_set('Asia/Kuala_Lumpur'); // Set the time zone for Malaysia
    $givenTime = new DateTime($create_time);

    // Current time
    $currentTime = new DateTime();

    // Difference between the given time and current time
    $interval = $currentTime->diff($givenTime);

    // Convert the interval to total minutes
    $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    // Check if more than 8 minutes have passed
    if ($totalMinutes > 8) {
        // echo "More than 8 minutes have passed since the given time.";
        return true;
    } else {
        // echo "Less than 8 minutes have passed since the given time.";
        return false;
    }
}

if(isset($_POST)){
    include_once "db_connector.php";
    include_once "db_operator.php";

    $result = [];
    $response = select_all_query($conn, 'locked_seat');

    if (count($response) > 0){
        foreach($response as $data){
            // check time, it is the lock has been over 8 min
            if (has_over_time($data['create_time'])){
                // if over, them delete the record in db
                delete_query($conn, 'locked_seat', 'id', "i", $data['id']);
            }else{
                $result[] = $data['seat_number'];
            }
        }
    }

    mysqli_close($conn); // Close the connection before returning

    header('Content-Type: application/json');
    echo json_encode($result); 
}

?>