<?php
namespace App\Service;

class ANDROIDPushMsg extends DAOBase 
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
    
    //内部发送Android推送
    public function push_android_msg($token_arr, $message){
    	//发送 推送
    	if($token_arr){
    		$res = $this->push_android_message($message, $token_arr);
    		if($res) $res = json_decode($res, true);
    		if (isset($res['success']) && $res['success'] > 0) {
    			\Logger::getRootLogger()->info('android push msg success'.json_encode($res));
    			return true;
    		}else {
    			\Logger::getRootLogger()->info('android push msg fail'.json_encode($token_arr));
    			return false;
    		}
    	}
    	\Logger::getRootLogger()->info('android push token is empty');
    	return false;
    }
    
    //android
    private function push_android_message($message, array $token_arr)
    {
    	$apiKey = ANDROID_API_KEY;
    
    	$post = array(
    			'registration_ids'  => $token_arr,
    			'notification'      => $message,
    	);
    	$headers = array(
    			'Authorization: key=' . $apiKey,
    			'Content-Type: application/json'
    	);
    	
    	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, ANDROID_API_URL);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    	// Actually send the request
    	$result = curl_exec($ch);
    	// Handle errors
    	if (curl_errno($ch)){
    		\Logger::getRootLogger()->info('android push msg connent fail');
    		return false;
    	}
    	curl_close($ch);
    	return  $result;
    }

}