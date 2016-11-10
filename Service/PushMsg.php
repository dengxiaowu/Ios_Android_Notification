<?php
namespace App\Service;

use Lib\Database\ADB;
use App\Util\ServiceContainer;

class PushMsg extends DAOBase 
{
	protected $table_name = 'push_message';
	
	private $redis_w = 'DB_redis_write';
	private $redis_r = 'DB_redis_read';
	
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
	
	/**
	 * 推送信息信息
	 * @param $mPush
	 * @return Int
	 */
	public function add_push_msg($mPush, $data){
		$db_manager = ADB::get_manager($this->get_db_manager_key());
		$ret = $db_manager->save($mPush);
		return is_null($ret) ? false : $ret;
	}

	/**
	 * 推送信息列表
	 */
	public function push_msg_list($keyword='', $page=1, $pagesize=20){
	
		$db_manager = ADB::get_manager($this->get_db_manager_key(DB_MODE_READ));
		
		$condparam = array();
		$where = '';
	
		if($keyword!=''){
			$where .= " and title like ? ";
			$condparam[] = '%'.$keyword.'%';
		}
		
		// 获取总记录条数
		$param = array(
				'where' => 'status=1 ' . $where,
		);
		$total = $db_manager->getCount($this->table_name, $param, $condparam);
		$offset = ($page-1) * $pagesize;
		
		//获取数据
		$param = array(
				'field' => '*',
				'where' => 'status=1 ' . $where,
				'order' => 'id desc, lastmodify desc',
				'limit' => $offset.','.$pagesize
		);
		$data = array();
		$data = $db_manager->query($this->table_name, $param, $condparam, 'hash');
		if($data){
			foreach ($data as $key=>$val){
				$data[$key]['mon_status_name'] = $this->get_mon_status_name($val['mon_status']);
			}
		}
		$oPage = $this->getPageInfo($total, $page, $pagesize);
	
		return array($data, $oPage);
	}
	
	//获取发送状态名称
	private function get_mon_status_name($mon_status){
		$data = array(
				'1' => $this->getLangText('text', 22, $this->getLang()),
				'9' => $this->getLangText('text', 23, $this->getLang()),
		);
		return $data[$mon_status];
	}
	
	/**
	 * 推送信息详情
	 */
	public function push_view($id){
	
		$db_manager = ADB::get_manager($this->get_db_manager_key(DB_MODE_READ));
		
		$param = array(
				'where' =>'id=? and status=1',
		);
		$condparam = array($id);
		$data = array();
		$data = $db_manager->query($this->table_name, $param, $condparam, 'one');
		
		return $data;
		
	}
	
	/**
	 * 推送信息编辑
	 */
	public function push_edit($data){
		$id = $data['id'];
		unset($data['id']);
		$db_manager = ADB::get_manager($this->get_db_manager_key());
		
		$condition = "id=? and status=1";
		$condparam = array($id);
		if($data['sendtime'] > time()){
			$data['mon_status'] = 9;
		}
		$update_data = $data;
		
		$ret = $db_manager->update($this->table_name, $data, $condition, $condparam);
		
		return is_null($ret) ? false : $ret;
	}
	
	/**
	 * 推送信息删除
	 */
	public function push_del($data){
	
		$id = $data['id'];
		unset($data['id']);
	
		$db_manager = ADB::get_manager($this->get_db_manager_key());
	
		$condition = "id=? and status=1";
		$condparam = array($id);
	
		$ret = $db_manager->update($this->table_name, $data, $condition, $condparam);
	
		return is_null($ret) ? false : $ret;
	}
	
	//获取待发送的全部列表
	public function get_to_send_list(){
		
		$db_manager = ADB::get_manager($this->get_db_manager_key(DB_MODE_READ));
		
		$condparam = array();		
		//获取数据
		$param = array(
				'field' => '*',
				'where' => 'status=1 and mon_status=9 and sendtime <='.time(),
				'order' => 'id desc, lastmodify desc',
		);
		$data = array();
		$data = $db_manager->query($this->table_name, $param, $condparam, 'hash');
		
		return $data;
	
	}

    //发送全部类型的推送
    public function send_message( array $message){    
    	if ($message['system_type']){
    		switch ($message['system_type']){
    			case $message['system_type'] == 'ios':
    				$res = $this->push_msg_loopup($message);
    				return $res;
    				break;
    			case $message['system_type'] == 'android':
    				$res = $this->push_msg_loopup($message);
    				return $res;
    				break;
    			case $message['system_type'] == 'all':
    				$res = $this->push_msg_loopup($message);
    				return $res;
    				break;
    			default:
    				return '';	
    		}
    	}
    }
    
    //发送成功-更新发送状态
    public  function update_push_status($id){
    	if(empty($id)) return false;
    	
    	$db_manager = ADB::get_manager($this->get_db_manager_key());
    	
    	$condition = "id=? and status=1 and mon_status=9";
    	$condparam = array($id);
    	$data = array('mon_status'=> 1);
    	$ret = $db_manager->update($this->table_name, $data, $condition, $condparam);
    	
    	return is_null($ret) ? false : $ret;
    }

  
 	//获取用户push token
	public function get_push_tokens($device_type){
		switch ($device_type){
			case 'ios':
				$device_type = 1;
				break;
			case 'android':
				$device_type = 2;
				break;
			default:
				$device_type = '';
		}
		//($type == 'ios') ? 1:2;
 		$sPushToken = ServiceContainer::get('PushToken');
 		$data = $sPushToken->push_token_list($device_type);
 		$tokens = array();
 		if($data){
 			foreach ($data as $val){
 				if($val['device_type'] == 1){
 					$tokens['ios'][] = $val['token'];
 				}elseif ($val['device_type'] == 2){
 					$tokens['android'][] = $val['token'];
 				}
 			}
 		}
 		return $tokens;
 	}
 
 	//获取用户push token----循环发送
 	public function push_msg_loopup($message){
 			
 		switch ($message['system_type']){
 			case 'ios':
 				$device_type = 1;
 				break;
 			case 'android':
 				$device_type = 2;
 				break;
 			default:
 				$device_type = '';
 		}
 		//($type == 'ios') ? 1:2;
 		$sPushToken = ServiceContainer::get('PushToken');
 		$page = 1;
 		$pagesize = 100;
 		
 		do{
 			list($list, $oPage) = $data = $sPushToken->push_token_list($device_type, $page, $pagesize);
 			$tokens = array();
 			if(!empty($list)){
 				foreach ($list as $val){
 					if(intval($val['device_type']) == 1){
 						$tokens['ios'][] = $val['token'];
 					}elseif (intval($val['device_type']) == 2){
 						$tokens['android'][] = $val['token'];
 					}
 				}
 				//转换发送数据格式
 				$message_change = $this->change_data($message);
 				//发送消息
 				switch ($message['system_type']){
 					case 'ios':
 						if($tokens['ios']) {
 							$sIOSPushMsg = ServiceContainer::get('IOSPushMsg');
    						$res = $sIOSPushMsg->push_ios_msg($tokens['ios'], $message_change);
    					}else {
    						\Logger::getRootLogger()->info('ios push msg token is empty');
    					}
 						break;
 					case 'android':
 						if($tokens['android']) {
 							$sAndroidPushMsg = ServiceContainer::get('ANDROIDPushMsg');
    						$res = $sAndroidPushMsg->push_android_msg($tokens['android'], $message_change);    						
    					}else {
    						\Logger::getRootLogger()->info('android push msg token is empty');
    					}
 						break;
 					case 'all':
 						$sIOSPushMsg = ServiceContainer::get('IOSPushMsg');
 						$sAndroidPushMsg = ServiceContainer::get('ANDROIDPushMsg');
 						if($tokens){
 							$res_ios = $sIOSPushMsg->push_ios_msg($tokens['ios'], $message_change);
 							$res_and = $sAndroidPushMsg->push_android_msg($tokens['android'], $message_change);
 							if($res_ios || $res_and) $res = 1;
 						}else {
 							\Logger::getRootLogger()->info('all push msg token is empty');
 						}
 						break;
 					default:
 						$res = '';
 				}
 				$page++;
 			}
 		
 		}while(!empty($list));
 		
 		return $res;
 	}
 	
 	//转换发送数据格式
 	protected function change_data($message){
 		$data = array();
 		if($message){
 			switch ($message['push_type']){
 				case 'url':
 					$data = array(
 						'title' => $message['title'],
 						'content'=>'sn://web/'.$message['url']
 					);
 					break;
 				case 'news':
 					$data = array(
 						'title' => $message['title'],
 						'content'=>'sn://detail/'.$message['nid']
 					);
 					break;
 				case 'feature':
 					$data = array(
 						'title' => $message['title'],
 						'content'=>'sn://featureList/'.$message['feature_id']
 					);
 					break;
 				case 'text':
 					$data = array(
 						'title' => $message['title'],
 						'content'=>'sn://text/'.$message['text']
 					);
 					break;
 				default:
 					return '';
 			}
 		}
 		return $data;
 	}
 
}