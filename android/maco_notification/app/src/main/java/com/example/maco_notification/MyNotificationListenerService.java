package com.example.maco_notification;

import android.app.Notification;
import android.content.Intent;
import android.service.notification.NotificationListenerService;
import android.service.notification.StatusBarNotification;
import android.util.Log;

import org.json.JSONException;
import org.json.JSONObject;

public class MyNotificationListenerService extends NotificationListenerService {
    private MqttHandler mqtt;
    private final String BROKER_URL = "ws://queue.kynoci.com:15675/ws";
    private final String CLIENT_ID = ""; // Ensure this is a unique ID
    private final String username = "engineer";
    private final String password = "NewEra2020";

    @Override
    public void onCreate() {
        super.onCreate();
        // Initialize MQTT connection
        mqtt = new MqttHandler();
        mqtt.connect(BROKER_URL, CLIENT_ID, username, password);
        Log.d("mqtt handler", "Mqtt Start");
    }

    @Override
    public void onDestroy() {
        // Disconnect MQTT when service is destroyed
        if (mqtt != null) {
            mqtt.disconnect();
        }
        super.onDestroy();
        Log.d("mqtt handler", "Mqtt Destroy");
    }


    @Override
    public void onNotificationPosted(StatusBarNotification sbn) {
        // Get the package name of the app that posted the notification
        String packageName = sbn.getPackageName();

        // Get the actual notification object
        Notification notification = sbn.getNotification();

        // Extract details from the notification
        CharSequence title = notification.extras.getCharSequence(Notification.EXTRA_TITLE);
        CharSequence text = notification.extras.getCharSequence(Notification.EXTRA_TEXT);
        CharSequence subText = notification.extras.getCharSequence(Notification.EXTRA_SUB_TEXT);
        CharSequence bigText = notification.extras.getCharSequence(Notification.EXTRA_BIG_TEXT);
        CharSequence summaryText = notification.extras.getCharSequence(Notification.EXTRA_SUMMARY_TEXT);

        JSONObject jsonObj = new JSONObject();

////        Intent intent = new Intent("com.example.maco_notification");
//        // Logging the extracted information (for demonstration purposes)
//        Log.d("NotificationListener", "Notification from package: " + packageName);
////        intent.putExtra("packageName", packageName);
//        if (title != null) {
//            Log.d("NotificationListener", "Title: " + title.toString());
////            intent.putExtra("title", title.toString());
//        }
//        if (text != null) {
//            Log.d("NotificationListener", "Text: " + text.toString());
////            intent.putExtra("text", text.toString());
//        }
//        if (subText != null) {
//            Log.d("NotificationListener", "SubText: " + subText.toString());
////            intent.putExtra("subText", subText.toString());
//        }
//        if (bigText != null) {
//            Log.d("NotificationListener", "Big Text: " + bigText.toString());
////            intent.putExtra("bigText", bigText.toString());
//        }
//        if (summaryText != null) {
//            Log.d("NotificationListener", "Summary Text: " + summaryText.toString());
////            intent.putExtra("summaryText", summaryText.toString());
//        }


        try {
            jsonObj.put("packageName", packageName);
            if (title != null) {
                jsonObj.put("title", title.toString());
            }
            if (text != null) {
                jsonObj.put("text", text.toString());
            }
            if (subText != null) {
                jsonObj.put("subText", subText.toString());
            }
            if (bigText != null) {
                jsonObj.put("bigText", bigText.toString());
            }
            if (summaryText != null) {
                jsonObj.put("summaryText", summaryText.toString());
            }

            // Convert JSONObject to String
            String jsonString = jsonObj.toString();
            mqtt.publish("Samsung", jsonString);

            // Now you can use jsonString to publish to MQTT
            // For example: mqttClient.publish(topic, jsonString);

        } catch (JSONException e) {
            e.printStackTrace();
        }

//        mqtt.publish("Samsung", "Miki Mouse");

//        sendBroadcast(intent);
        // Here, you can add more logic to handle the notification,
        // such as sending the information to your app, storing it, etc.
    }

    @Override
    public void onNotificationRemoved(StatusBarNotification sbn) {
        // Code to handle the notification when removed
    }
}

