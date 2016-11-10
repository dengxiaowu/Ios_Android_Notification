<?php
namespace App\Service;

class IOSPushMsg extends DAOBase 
{
	
	public function get_db_manager_key($mode=DB_MODE_WRITE)
	{
		switch($mode)
		{
			case DB_MODE_READ:
				return MYSQL_DB_SNIPPETNEWS_KEY . '_' . DB_MODE_READ;
			default:
				return MYSQL_DB_SNIPPETNEWS_KEY . '_' . DB_MODE_WRITE;
		}
	}

    //内部发送ios推送
    public function push_ios_msg($token_arr, $message){
    	//发送 推送
    	if($token_arr){
    		foreach ($token_arr as $val){
    			$res = $this->push_ios_message($val, $message);
    			if($res){
    				$res_data[] = $res;
    			}else {
    				$res_data == false;
    			}
    		}
    		return $res_data;
    	}
    	\Logger::getRootLogger()->info('ios push msg token is empty');
    	return false;
    }

    //ios
    private function push_ios_message($token, $message){
    	    	
    	$ctx = stream_context_create();
    	stream_context_set_option($ctx, 'ssl', 'local_cert', PEM_PATH );
    	stream_context_set_option($ctx, 'ssl', 'passphrase', PASSPHRASE );
    	
    	// Open a connection to the APNS server
    	//这个为正是的发布地址
    	//$fp = stream_socket_client(“ssl://gateway.push.apple.com:2195“, $err, $errstr, 60, //STREAM_CLIENT_CONNECT, $ctx);
    	//这个是沙盒测试地址，发布到appstore后记得修改哦
    	$fp = stream_socket_client(APNS_URL, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
    	if (!$fp){
    		\Logger::getRootLogger()->info('ios push msg connent fail');
    		return false;
    	}else {
    		// Create the payload body
    		$body['aps'] = array(
    				'alert' => $message['title'],
    				'msg' => $message['content'],
    				'sound' => 'default',
    				//'badge' => 1,//app上面的红点
    		);
    		$payload = json_encode($body);
    		// Build the binary notification
    		$msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $token)) . pack("n",strlen($payload)) . $payload;
    		// Send it to the server
    		$result = fwrite($fp, $msg, strlen($msg));
    		if (!$result){
    			\Logger::getRootLogger()->info('ios push msg fail! token='.$token.'and message='.$payload);
    			return false;
    		}else{
    			// Close the connection to the server
    			\Logger::getRootLogger()->info('ios push msg success! token='.$token.'and message='.$payload);
    			fclose($fp);
    			return true;
    		}
    	}	
    }

}