<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MQTTSubscriber
{
    private $mqttClient;
    private $server;
    private $port;
    private $username;
    private $password;
    private $clientId;

    public function __construct($server, $port, $username, $password, $clientId)
    {
        $this->server = $server;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->clientId = $clientId;

        $this->initializeClient();
    }

    private function initializeClient()
    {
        $connectionSettings = (new ConnectionSettings())
                                ->setUsername($this->username)
                                ->setPassword($this->password);

        $this->mqttClient = new MqttClient($this->server, $this->port, $this->clientId);
        $this->mqttClient->connect($connectionSettings, true);
    }

    public function subscribeToTopic($topic, $callback)
    {
        $this->mqttClient->subscribe($topic, $callback, 0);
        $this->mqttClient->loop(true);
    }

    public function __destruct()
    {
        $this->mqttClient->disconnect();
    }
}
?>