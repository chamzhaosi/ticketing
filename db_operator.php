<?php

function select_all_query($conn, $tableName){
    $query = "SELECT * FROM `$tableName`";
    
    $result = mysqli_query($conn, $query);

    $data = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
            // echo $data;
        }
        return $data;
    } else {
        return $data;
    }
}

function select_all_query_by_value($conn, $tableName, $columnName, $datatype, $value){
    $query = "SELECT * FROM `$tableName` WHERE `$columnName` LIKE ?";

    $stmt = mysqli_prepare($conn, $query);
 
    if($columnName != "id" && $columnName != "seat_id"){
        $searchValue = "%$value%";
    }else{
        $searchValue = $value;
    }
    // echo $searchValue;
    mysqli_stmt_bind_param($stmt, $datatype, $searchValue);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Array to hold all rows
    $rows = [];
    // Fetch all rows and add to the array
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
        // echo count($rows);
    }

    // echo $rows[0]["id"];
    // echo "   ";
    // echo $rows[0]["received_amount"];

    // echo count($rows);
    // Check if any rows were found
    if (count($rows) > 0) {
        return array(true, $rows);
    } else {
        return array(false, []);
    }
}

function select_all_query_by_value_time($conn, $tableName, $columnName, $datatype, $value, $createTime) {
    date_default_timezone_set('Asia/Kuala_Lumpur');
    // Convert Unix timestamp to MySQL datetime format
    $formattedCreateTime = date('Y-m-d H:i:s', $createTime);
    // echo  $formattedCreateTime;

    // Updated query with additional condition for create_time
    $query = "SELECT * FROM `$tableName` WHERE `$columnName` LIKE ? AND `create_time` > ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($columnName != "id" && $columnName != "seat_id") {
        $searchValue = "%$value%";
    } else {
        $searchValue = $value;
    }

    // Bind parameters for both the search value and create_time
    mysqli_stmt_bind_param($stmt, $datatype . 's', $searchValue, $formattedCreateTime);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Array to hold all rows
    $rows = [];
    // Fetch all rows and add to the array
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    // Check if any rows were found
    if (count($rows) > 0) {
        return array(true, $rows);
    } else {
        return array(false, []);
    }
}

function select_all_query_by_one_column_two_values($conn, $tableName, $columnName, $datatype, $value1, $value2) {
    // Updated query to search for two values in the same column
    $query = "SELECT * FROM `$tableName` WHERE `$columnName` LIKE ? OR `$columnName` LIKE ?";
    $stmt = mysqli_prepare($conn, $query);

    // Handle the values based on column exceptions
    if ($columnName != "id" && $columnName != "seat_id" && $columnName != "status_id") {
        $searchValue1 = "%$value1%";
        $searchValue2 = "%$value2%";
    } else {
        $searchValue1 = $value1;
        $searchValue2 = $value2;
    }

    // Bind parameters for the search values
    mysqli_stmt_bind_param($stmt, $datatype, $searchValue1, $searchValue2);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Array to hold all rows
    $rows = [];
    // Fetch all rows and add to the array
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    // Check if any rows were found
    if (count($rows) > 0) {
        return array(true, $rows);
    } else {
        return array(false, []);
    }
}

function select_all_query_by_two_column_three_values($conn, $tableName, $columnName, $columnNameSecond, $datatype, $value1, $value2, $value3) {
    // Updated query to search for two values in the first column and one value in the second column
    $query = "SELECT * FROM `$tableName` WHERE (`$columnName` LIKE ? OR `$columnName` LIKE ? ) AND `$columnNameSecond` LIKE ?";
    $stmt = mysqli_prepare($conn, $query);

    // Handle the values based on column exceptions
    if ($columnName != "id" && $columnName != "seat_id" && $columnName != "status_id") {
        $searchValue1 = "%$value1%";
        $searchValue2 = "%$value2%";
        $searchValue3 = "%$value3%";
    } else {
        $searchValue1 = $value1;
        $searchValue2 = $value2;
        $searchValue3 = $value3;
    }

    // Bind parameters for the search values
    mysqli_stmt_bind_param($stmt, $datatype, $searchValue1, $searchValue2, $searchValue3);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    // echo $result;

    // Array to hold all rows
    $rows = [];
    // Fetch all rows and add to the array
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    // Check if any rows were found
    if (count($rows) > 0) {
        return array(true, $rows);
    } else {
        return array(false, []);
    }
}


function insert_query($conn, $tableName, $columnNames, $datatypes, $values){
    // Ensure columnNames and values are arrays
    if (!is_array($columnNames)) {
        $columnNames = [$columnNames];
    }

    // Create column list
    $columnList = implode(", ", array_map(function($col) use ($conn) {
        return "`" . mysqli_real_escape_string($conn, $col) . "`";
    }, $columnNames));

    // echo $columnList;

    // Placeholder for values (? marks)
    $valuePlaceholders = implode(", ", array_fill(0, count($columnNames), '?'));
    // echo $valuePlaceholders;

    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO `$tableName` ($columnList) VALUES ($valuePlaceholders)");

    if($tableName === "locked_seat"){
        // Bind parameters. "ss" means both parameters are strings.
        // echo $value_append;
        $value_append = $values;
        $stmt->bind_param($datatypes, $value_append);
    }else{
        if (!is_array($values)) {
            $values = [$values];
        }
        
         // Dynamically bind parameters
        $refValues = array_merge([$datatypes], $values);
        $refArray = array();
        foreach ($refValues as $key => $value) {
            $refArray[$key] = &$refValues[$key];
        }

        // echo $refArray[0];
        // echo $refArray[1];
        call_user_func_array([$stmt, 'bind_param'], $refArray);
    }

    // Execute the statement
    $stmt->execute();

    // Check for successful insertion
    if ($stmt->affected_rows > 0) {
        // echo "Record inserted successfully";
        return array(true, $stmt->insert_id);
    } else {
        // echo "Error: " . $stmt->error;
        // Insertion failed
        // echo "false";
        return array(false, $stmt->error);
    }
}


function delete_query($conn, $tableName, $columnName, $datatype, $value){
    $sql = "DELETE FROM `$tableName` WHERE `$columnName` = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param($datatype, $value); // "i" denotes the type (integer)

    if ($stmt->execute()) {
        // echo "Record deleted successfully";
        return true;
    } else {
        // echo "Error deleting record: " . $conn->error;
        return false;
    }
}

function update_query($conn, $tableName, $data, $whereClause) {
    // Sanitize table name
    $tableName = mysqli_real_escape_string($conn, $tableName);

    // Start building the SQL string
    $sql = "UPDATE `$tableName` SET ";

    // Placeholder for parameters
    $params = [];
    $types = '';

    // Build the SET part of the SQL dynamically
    foreach ($data as $column => $value) {
        $sql .= "`" . mysqli_real_escape_string($conn, $column) . "` = ?, ";
        $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        $params[] = &$data[$column];
    }

    // Remove the trailing comma and space
    $sql = rtrim($sql, ", ");

    // Add the WHERE clause
    $sql .= " WHERE id =" . $whereClause;

    // echo $sql;

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Dynamically bind parameters
    if (!empty($params)) {
        array_unshift($params, $types);
        call_user_func_array([$stmt, 'bind_param'], $params);
    }

    // Execute the statement
    if ($stmt->execute()) {
        // Check for successful update
        return array(true, $stmt->affected_rows);
    } else {
        // Handle errors
        return array(false, $stmt->error);
    }
}


// function insert_query($conn, $tableName, $columnName, $datatype, $value){
//     if (count($columnName) > 1){
//         // Create column list
//         $column_append = implode(", ", array_map(function($col) {
//             return "`$col`";
//         }, $columnName));
//     }else{
//         $column_append = $columnName[0];
//     }

//     // Create placeholders for values
//     $placeholders = implode(", ", array_fill(0, count($value), '?'));

//     // Prepare the statement
//     $stmt = $conn->prepare("INSERT INTO `$tableName` ($column_append) VALUES ($placeholders)");

//    // Refactor $value to be an array of references
//    $refValues = [];
//    foreach ($value as $key => $val) {
//        $refValues[$key] = &$value[$key];
//    }

//    print_r($refValues);

//    // Merge $datatype with $refValues
//    array_unshift($refValues, $datatype);

//    print_r($refValues);

//    // Use call_user_func_array to bind parameters dynamically
//    call_user_func_array([$stmt, 'bind_param'], $refValues);

//     // Check for successful insertion
//     if ($stmt->affected_rows > 0) {
//         echo "Record inserted successfully";
//     } else {
//         echo "Error: " . $stmt->error;
//     }

//     // Close statement
//     $stmt->close();

//     // Close connection
//     $conn->close();


// }

?>