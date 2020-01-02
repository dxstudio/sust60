<?php
namespace app\api\controller;
use app\api\model\User;
use think\Cache;
use extend\encryptedData\WXBizDataCrypt;
class UserController extends BaseController
{
	//default config
    protected $config = [
        'url' => "https://api.weixin.qq.com/sns/jscode2session", 
        'appid' => 'wx56ff4f457b23ebd1', 
        'secret' => 'e1410645f7e2014caf8faaae6971a7d4', 
        'grant_type' => 'authorization_code',
    ];
    /**
     * @param:
     * @return:
     */
	public function saveSession ($code,$signature,$rawData,$iv,$encryptedData){
		/**
		 * 1.get openid and session_key from Wechat Http Api by code
		 * @param:code
		 * result:openid,session_key
		 */
		$final_result=array();
		$https_params = array(
			'appid' => $this->config['appid'],
			'secret'=> $this->config['secret'],
			'js_code'=>$code,
			'grant_type'=>$this->config['grant_type']

		);
		$res=$this->https($this->config['url'], $https_params, 'GET');
		$data=json_decode($res, true);
		if (!isset($data['openid'])||!isset($data['session_key'])) {
            $final_result = ['errcode'=>$data['errcode'], 'errmsg'=>$data['errmsg']];
            return json_encode($final_result);
        }
        else{
        	$openId=$data['openid'];
			$session_key=$data['session_key'];
        }
		/**
		 * 2.check signature
		 * @param:rawData,session_key
		 * result:true
		 */
		$signature2=sha1($rawData.$session_key);
		if($signature!=$signature2){
			$final_result= ['errcode'=>'001', 'errmsg'=>'signature not match'];
			return json_encode($final_result);
		}
		/**
		 * 3.decrypt encryptedData
		 * @param:appid,session_key
		 * result:[json] data
		 *        errCode
		 */
		$pc = new WXBizDataCrypt($this->config['appid'], $session_key);
		$errCode = $pc->decryptData($encryptedData, $iv, $decryptedData );
		if($errCode != 0){
			$final_result= ['errcode'=>$errCode, 'errmsg'=>'decrypt error'];
			return json_encode($final_result);
		}
		else{
			$decryptedData=json_decode($decryptedData,true);
		}
		/**
		 * 4.check decryptedData 
		 * @param:openid,appid,decryptedData
		 * result:default
		 */
		if($openId!=$decryptedData['openId']){
			$final_result= ['errcode'=>'004', 'errmsg'=>'openid not match'];
			return json_encode($final_result);
		}
		else if($this->config['appid']!=$decryptedData['watermark']['appid']){
			$final_result= ['errcode'=>'004', 'errmsg'=>'appid not match'];
			return json_encode($final_result);
		}	
		/**
		 * 5.make 3rd_session
		 * @param:openid,session_key
		 * result:[SESSION] token(openid+rand):session_key+openid
		 */
		$token=$this->randomFromDev(16);
		Cache::set($token,$decryptedData['openId']."|".$session_key);
		
		/**
		 *6.add or update user 
		 *
		 */
		date_default_timezone_set("Asia/Shanghai");
    	$update_time=date("Y-m-d H:i:s");
    	$user_params = array('nickName'=> $decryptedData['nickName'],
    						'gender'=> $decryptedData['gender'],
    						'city'=> $decryptedData['city'],
    						'province'=> $decryptedData['province'],
    						'country'=> $decryptedData['country'],
    						'avatarUrl'=> $decryptedData['avatarUrl'],
    						'session_key' => $session_key,
    						'update_time' => $update_time);
		if($this->isUser($decryptedData['openId'])){
    		$update_result=$this->updateUser($decryptedData['openId'],$user_params);
			if(!$update_result)
			{
				$final_result= ['errcode'=>'006', 'errmsg'=>'update failed'];
				return json_encode($final_result);
			}
		}
		else{
			$add_result=$this->addUser($decryptedData['openId'],$user_params);
			if(!$add_result)
			{
				$final_result= ['errcode'=>'006', 'errmsg'=>'add failed'];
				return json_encode($final_result);
			}
		}
		$final_result= ['errcode'=>'000', 'errmsg'=>$token];
		return json_encode($final_result);
	}
	public function identify(){
		$token=$_POST['token'];
		$addinfo=json_decode($_POST['addinfo'],true);

		if(Cache::get($token)){
			$cache=Cache::get($token);
			$openid = substr($cache,0,strrpos($cache,'|')); 
			$update_result=$this->updateUser($openid,$addinfo);
			if(!$update_result)
			{
				$final_result= ['errcode'=>'006', 'errmsg'=>'update failed','userinfo'=>$addinfo];
			}
			else{
				$final_result= ['errcode'=>'007', 'errmsg'=>'update success:','userinfo'=>''];
			}
			
		}else{
			$final_result= ['errcode'=>'008', 'errmsg'=>'token error','userinfo'=>''];
		}
		return json_encode($final_result);
		
	}
	/**
	 * @param:token:
	 *		  iv:
	 *		  encryptedData:
	 * @return:errcode:0(success)
	 *                 111(token failed)
	 */
	public function getWeRunData($token,$iv,$encryptedData){
		if(!(Cache::get($token))){
			$final_result['errcode']='111';
			$final_result['errmsg']="token failed";
		}
		else{
			$cache=Cache::get($token);
			$session_key = substr(strstr($cache, "|"), 1);
			$pc = new WXBizDataCrypt($this->config['appid'], $session_key);
			$errCode = $pc->decryptData($encryptedData, $iv, $decryptedData );
			if($errCode != 0){
				$final_result['errcode']=$errCode;
				$final_result['errmsg']="decrypt error";
			}
			else{
				$decryptedData=json_decode($decryptedData,true);
				if($decryptedData['watermark']['appid']==$this->config['appid']){
					$final_result['errcode']=$errCode;
					$final_result['errmsg']=$decryptedData;
				}
				else{
					$final_result['errcode']='111';
					$final_result['errmsg']="not this app";
				}
			}
			
		}

		return json_encode($final_result);

	}
	/**
	 * @param:result:1=>openid default
	 *				 2=>session_key
	 */
	public function detoken($token,$result=''){

		if(!(Cache::get($token))){
			$final_result='111';
		}
		else{
			$cache=Cache::get($token);
			if($result=='2'){
				$final_result = substr(strstr($cache, "|"), 1);
			}
			else{
				$final_result = substr($cache,0,strrpos($cache,'|')); 
			}
			
		}
		return $final_result;
	}
	public function isUser($openId){
		return USER::where('openId',$openId)->count();
	}
	public function userInfo($openId){
		$user=new User;
        $result=$user->where('openId',$openId)->find();
	}
	public function addUser($openId,$params){
		$params['openId']=$openId;
    	$user = new User($params);
		$result=$user->save();
        return $result;
	}

	public function updateUser($openId,$params){

        $user = new User;
        $result=$user->save($params,['openId'=>$openId]);
        return $result;
	}
	public function clearUser($token){
		$openId=$this->detoken($token,'1');
		$clearParams=array('score'=>'');
		$update_result=$this->updateUser($openId,$clearParams);
		Cache::rm($token);
		if(!$update_result||(Cache::get($token)))
		{
			$final_result= ['errcode'=>'006', 'errmsg'=>'clear failed'];
			return json_encode($final_result);
		}
		$final_result= ['errcode'=>'000', 'errmsg'=>'clear success:'];
		return json_encode($final_result);

	}
	public function clearAll(){
		Cache::clear();
	}
}