<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

require_once __DIR__ . '/vendor/autoload.php';

// session_start();
// include_once "db_connector.php";
// include_once "db_operator.php";
// session_start();
require_once "db_connector.php";
require_once "db_operator.php";


// MQTT server configuration
$server   = 'queue.kynoci.com'; // Replace with your MQTT broker address
$port     = 1883; // Common MQTT port
$clientId = 'phpMQTT-publisher-' . uniqid(); // Unique client ID for each connection
$username = 'engineer'; // Your MQTT username, if required
$password = 'NewEra2020'; // Your MQTT password, if required

// Establish a connection to the MQTT broker
$connectionSettings = (new ConnectionSettings())
                        ->setUsername($username)
                        ->setPassword($password);

$mqtt = new MqttClient($server, $port, $clientId);
$mqtt->connect($connectionSettings, true);

// The topic to subscribe to
$topic = 'maco/concert';

// Function to handle received messages
$mqtt->subscribe($topic, function ($topic, $message) {
    echo sprintf("Received message on topic [%s]: %s\n", $topic, $message);
    global $conn;
    insert($message, $conn);
}, 0);

// Keep listening for messages
$mqtt->loop(true);

// Closing the connection (this line will not be reached if loop is true)
$mqtt->disconnect();

function insert($message, $conn){
    // Convert JavaScript regex to PHP regex
    $moneyRegex = '/RM\s([\d.]+)/'; // Matches "RM" followed by a space and then the amount
    $nameRegex = '/from\s(.*?)\./'; // Matches anything after "from " and before the next period
    $refRegex = '/REF:\s(\w+)/'; // Matches "REF: " followed by the reference number

    // Matching and extracting the details
    preg_match($moneyRegex, $message, $moneyMatch);
    preg_match($nameRegex, $message, $nameMatch);
    preg_match($refRegex, $message, $refMatch);

    $money = isset($moneyMatch[1]) ? floatval($moneyMatch[1]) : null;
    $name = isset($nameMatch[1]) ? $nameMatch[1] : null;
    $reference = isset($refMatch[1]) ? $refMatch[1] : null;

    // echo $reference;
    // echo $name;
    // echo $money;
    
    // check reference no exist or not afthe the time user load the qr page
    if(!select_all_query_by_value($conn, "bank_received_detail", "ref_number", "s", $reference)[0]){
        $insert_result = insert_query($conn, "bank_received_detail", ["ref_number","received_amount", "payer_name"], "sss", [$reference, $money, $name]);
        if($insert_result[0]){
            echo json_encode($insert_result[1]);
        }else{
            echo json_encode("Error inserting data");
        }
    }else{
        echo json_encode("Reference Number Duplicant");
    }
}

?>