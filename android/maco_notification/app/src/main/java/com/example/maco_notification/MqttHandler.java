package com.example.maco_notification;

import android.util.Log;

import org.eclipse.paho.client.mqttv3.MqttClient;
import org.eclipse.paho.client.mqttv3.MqttConnectOptions;
import org.eclipse.paho.client.mqttv3.MqttException;
import org.eclipse.paho.client.mqttv3.MqttMessage;
import org.eclipse.paho.client.mqttv3.persist.MemoryPersistence;
import org.eclipse.paho.client.mqttv3.*;

public class MqttHandler {

    private MqttClient client;
    private String brokerUrl;
    private String clientId;
    private String username;
    private String password;

    // ... Rest of your existing methods ...
    private void reconnect() {
        new Thread(() -> {
            int retryInterval = 1000; // Start with 1 second
            while (!this.client.isConnected()) {
                try {
                    Log.d("mqtt error", "reconnect mqtt: "+brokerUrl);
                    Thread.sleep(retryInterval);
                    connect(this.brokerUrl, this.clientId, this.username, this.password);
                    retryInterval = Math.min(retryInterval * 2, 60000); // Max 60 seconds
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt(); // Restore interrupted status
                }
            }
        }).start();
    }

    public void connect(String brokerUrl, String clientId, String username, String password) {
        this.brokerUrl = brokerUrl;
        this.clientId = clientId;
        try {
            // Set up the persistence layer
            MemoryPersistence persistence = new MemoryPersistence();

            // Initialize the MQTT client
            this.client = new MqttClient(brokerUrl, clientId, persistence);

            this.client.setCallback(new MqttCallback() {
                @Override
                public void connectionLost(Throwable cause) {
                    // Handle the connection lost event here
                    reconnect();
                }

                @Override
                public void messageArrived(String topic, MqttMessage message) {
                    // Handle incoming messages
                }

                @Override
                public void deliveryComplete(IMqttDeliveryToken token) {
                    // Handle completed delivery
                }
            });

            // Set up the connection options
            MqttConnectOptions connectOptions = new MqttConnectOptions();
            connectOptions.setCleanSession(true);

            // Set username and password
            connectOptions.setUserName(username);
            connectOptions.setPassword(password.toCharArray());

            // Connect to the broker
            this.client.connect(connectOptions);
        } catch (MqttException e) {
            e.printStackTrace();
            Log.d("mqtt_error", e.toString());
        }
    }


    public void disconnect() {
        try {
            this.client.disconnect();
        } catch (MqttException e) {
            e.printStackTrace();
            Log.d("mqtt_error", e.toString());
        }
    }

    public void publish(String topic, String message) {
        try {
            MqttMessage mqttMessage = new MqttMessage(message.getBytes());
            this.client.publish(topic, mqttMessage);
        } catch (MqttException e) {
            e.printStackTrace();
            Log.d("mqtt_error", e.toString());
        }
    }

    public void subscribe(String topic) {
        try {
            this.client.subscribe(topic);
        } catch (MqttException e) {
            e.printStackTrace();
            Log.d("mqtt_error", e.toString());
        }
    }
}
