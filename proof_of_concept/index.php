<?php

include 'db_connector.php';

$stmt = $conn->prepare("INSERT INTO donate_detial (total_amount) VALUES (?)");
$stmt->bind_param("d", $total_amount);

$total_amount = 125.98;

if ($stmt->execute()) {
    echo "New record created successfully";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();

echo "hello world"

?>
