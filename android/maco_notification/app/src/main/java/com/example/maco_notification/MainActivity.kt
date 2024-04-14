package com.example.maco_notification

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.Bundle
import android.util.Log
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.viewModels
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.MutableState
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import com.example.maco_notification.ui.theme.Maco_notificationTheme

//class MainViewModel : ViewModel() {
//    val notificationText = mutableStateOf("Initial Text")
////    fun updateNotificationText(newText: String) {
////        notificationText.value = newText
////    }
//}

class MainActivity : ComponentActivity() {
    // This state holds the text to be displayed
//    private val viewModel by viewModels<MainViewModel>()
//    val BROKER_URL = "tcp://broker.hivemq.com:1883";
//    val CLIENT_ID = "";
//    val mqtt = MqttHandler();

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            Maco_notificationTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
//                    Greeting("Android", viewModel.notificationText, onSettingsClick = {
//                        openNotificationListenerSettings()
//                    })
                    Greeting("Android", onSettingsClick = {
                        openNotificationListenerSettings()
                    })
                }
            }
        }
//        mqtt.connect(BROKER_URL, CLIENT_ID, );
    }

//    override fun onStart() {
//        super.onStart()
//        val filter = IntentFilter("com.example.maco_notification")
//        registerReceiver(notificationReceiver, filter)
//    }
//
//    override fun onStop() {
//        super.onStop()
//        unregisterReceiver(notificationReceiver)
//    }
//
//    override fun onDestroy() {
//        super.onDestroy()
//        mqtt.disconnect()
//    }


    private fun openNotificationListenerSettings() {
        val intent = Intent("android.settings.ACTION_NOTIFICATION_LISTENER_SETTINGS")
        startActivity(intent)
    }

//    private val notificationReceiver = object : BroadcastReceiver() {
//        override fun onReceive(context: Context, intent: Intent) {
//            val packageName = intent.getStringExtra("packageName") ?: "Unknown"
//            val title = intent.getStringExtra("title") ?: "No Title"
//            val text = intent.getStringExtra("text") ?: "No Text"
//            val subText = intent.getStringExtra("subText") ?: "No Sub Text"
//            val bigText = intent.getStringExtra("bigText") ?: "No Big Text"
//            val summaryText = intent.getStringExtra("summaryText") ?: "No Summary Text"
//
//            val content = "Package: $packageName\nTitle: $title\nText: $text\n" +
//                    "Sub Text: $subText\n" +
//                    "Big Text: $bigText\n" +
//                    "Summary Text: $summaryText"
//            Log.d("NotificationListener", "BroadcastReceiver: $content")
//            viewModel.updateNotificationText(content)
//            Log.d("NotificationListener", "mqtt_publish: Start")
//            mqtt.publish("ChamChamChamCham", "TigerTiger")
//            Log.d("NotificationListener", "mqtt_publish: End")
//            // Process the information as needed
//        }
//
//    }
}


@Composable
//fun Greeting(name: String, notificationText: MutableState<String> = mutableStateOf("Default Text"),
//             modifier: Modifier = Modifier, onSettingsClick: () -> Unit) {
fun Greeting(name: String, modifier: Modifier = Modifier, onSettingsClick: () -> Unit) {
    Column(modifier = modifier.padding(16.dp)) {
        Text(text = "Hello $name!")
        Spacer(modifier = Modifier.height(8.dp))
        Button(onClick = onSettingsClick) {
            Text(text = "Open Notification Settings")
        }
//        Text(text = notificationText.value)
    }
}


@Preview(showBackground = true)
@Composable
fun GreetingPreview() {
    Maco_notificationTheme {
//        Greeting("Android", mutableStateOf("Preview Text"), onSettingsClick = {})
        Greeting("Android", onSettingsClick = {})
    }
}