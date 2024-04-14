<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$_SESSION['bank_holder_name'] = $data['name'];

?>
