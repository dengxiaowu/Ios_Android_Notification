<?php
namespace App\Cli;

use App\Util\ServiceContainer;

class PushMsg extends \CliApplication 
{
	
	public function action_index() 
	{
		$sPushMsg = ServiceContainer::get('PushMsg');
		//获取待发送列表
		$data = $sPushMsg->get_to_send_list();
		if($data){
			foreach ($data as $Message){
				if($Message['sendtime'] <= time()){
					$res = $sPushMsg->send_message($Message);
					if($res){
						\Logger::getRootLogger()->info(' crontab push success'.json_encode($Message));
						//更新发送状态
						$update_mysql = $sPushMsg->update_push_status($Message['id']);
						if($update_mysql){
							\Logger::getRootLogger()->info(' crontab push success and update mysql success. id='.$Message['id']);
							echo '['.date('Y-m-d H:i:s').'] success:'.json_encode($Message,JSON_UNESCAPED_UNICODE).PHP_EOL.PHP_EOL;
						}else {
							echo  '['.date('Y-m-d H:i:s').'] update mysql fail'.PHP_EOL;
						}
					}else {
						echo '['.date('Y-m-d H:i:s').'] send message fail'.PHP_EOL;
					}
				}else {
					echo '['.date('Y-m-d H:i:s').'] nothing to send message'.PHP_EOL;
				}
			}
		}
		echo '['.date('Y-m-d H:i:s').'] list nothing to send message'.PHP_EOL;
	}
}