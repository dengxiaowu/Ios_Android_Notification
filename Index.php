<?php
class Index {
	
	//Android 推送
	public function android_push()
	{	
		// Payload data you want to send to Android device(s)
		// (it will be accessible via intent extras)    
		$data = array('message' => 'Hello World  2 !');

		// The recipient registration tokens for this notification
		// https://developer.android.com/google/gcm/    
		$ids = array('eBiKt1Gj65s:APA91bEU2nBFJjaqMe1CmJnYb_GCF8YdbARxC2WR3ejb80Y9vFfZ41YeXW5CfG_VB068USYLxhvJ-ZYXGW-wk9-2yRYA6FaqOkijF2SWe5-TqUteFN_SlySNn4qpG91JJjW7WPU_ymJm',
				
		);
		// Send push notification via Google Cloud Messaging
		$this->sendPushNotification($data, $ids);

		
	}
	
	function sendPushNotification($data, $ids)
	{
		// Insert real GCM API key from the Google APIs Console
		// https://code.google.com/apis/console/
		$apiKey = 'AIzaSyCoALQrA4v_YSy_HY2D0NDqX649UmpqCNk';
	
		// Set POST request body
		$post = array(
				'registration_ids'  => $ids,
				'data'              => $data,
		);
	
		// Set CURL request headers
		$headers = array(
				'Authorization: key=' . $apiKey,
				'Content-Type: application/json'
		);
		
		// Initialize curl handle
		$ch = curl_init();
	
		// Set URL to GCM push endpoint
		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
	
		// Set request method to POST
		curl_setopt($ch, CURLOPT_POST, true);
	
		// Set custom request headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
		// Get the response back as string instead of printing it
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		// Set JSON post data
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
	
		// Actually send the request
		$result = curl_exec($ch);
	
		// Handle errors
		if (curl_errno($ch)){
			echo 'GCM error: ' . curl_error($ch);
		}
	
		// Close curl handle
		curl_close($ch);
	
		// Debug GCM response
		echo $result;
	}
	
	//ios 
	public function ios_push(){
		// 这里是我们上面得到的deviceToken，直接复制过来（记得去掉空格）
		$deviceToken = '97961a5b9a0109c7a9064f5e3af612000bbc91c97ab48e6f845b7c3baa74c8a3';
		
		// Put your private key's passphrase here:
		$passphrase = 'abc123';
		
		// Put your alert message here:
		// 		$message = 'My first push test!';
		$message = array('title'=>'小小说ffffffsdfjsd','content'=>'sn://detail/sjfsdfjsfs');
		
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', '/Users/phpdeveloper/Downloads/ck.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		
		// Open a connection to the APNS server
		//这个为正是的发布地址
		$fp = stream_socket_client("ssl://gateway.push.apple.com:2195", $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
		//这个是沙盒测试地址，发布到appstore后记得修改哦
// 		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
		
// 			echo 'Connected to APNS' . PHP_EOL;
		
			// Create the payload body
			$body['aps'] = array(
					'alert' => $message['title'],
					'msg' => $message['content'],
					'sound' => 'default',
					'badge' => 1,
			);
		
			// Encode the payload as JSON
			$payload = json_encode($body);
		
			// Build the binary notification
			$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		
			// Send it to the server
			$result = fwrite($fp, $msg, strlen($msg));
			
			if (!$result)
				echo 'Message not delivered' . PHP_EOL;
				else	
// 					$rtn_str = fread($fp,6);
// 					echo $rtn_str;
					echo $result. PHP_EOL;exit();
					echo 'Message successfully delivered' . PHP_EOL;
					// Close the connection to the server
					fclose($fp);
	}
}
