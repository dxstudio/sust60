<?php

namespace app\api\controller;
use extend\encryptedData\WXBizDataCrypt;
header( "Access-Control-Allow-Origin:*" );
header( "Access-Control-Allow-Methods:POST,GET" );
class IndexController{
	protected $openid='';
	protected $session_key='';
	public function index(){
		$final_result='test sftp';
        return json_encode($final_result);
	}
	public function get($index=''){
		//根据$kwd执行搜索函数，结果统一为json格式，假设以下是搜索到的两条符合结果
		$arr=array();
		$arr1 = array('title' => '严书记”被查，该“归功”严夫人吗', 'type' => '时政', 'update_time' => '2018-05-19 09:16:59', 'writor' => '新京报', 'url' => 'http://news.ifeng.com/a/20180519/58364878_0.shtml?_zbs_baidu_news');
		array_push($arr,$arr1);
		$arr2 = array('title' => '年入106亿！服务很“逆天”的海底捞要上市了', 'tag' => '财经', 'update_time' => '2018-05-18 14:09', 'writor' => '光明网', 'url' => 'http://xinwen.eastday.com/a/180518140901127.html?qid=news.baidu.com');
		array_push($arr,$arr2);
		return json_encode($arr,JSON_UNESCAPED_UNICODE);
	}
	public function post(){
		
		$kwd=$_POST['index'];
		$arr=array();
		//根据$kwd执行搜索函数，结果统一为json格式，假设以下是搜索到的两条符合结果
		$arr1 = array('title' => '严书记”被查，该“归功”严夫人吗', 'type' => '时政', 'update_time' => '2018-05-19 09:16:59', 'writor' => '新京报', 'url' => 'http://news.ifeng.com/a/20180519/58364878_0.shtml?_zbs_baidu_news');
		array_push($arr,$arr1);
		$arr2 = array('title' => '年入106亿！服务很“逆天”的海底捞要上市了', 'tag' => '财经', 'update_time' => '2018-05-18 14:09', 'writor' => '光明网', 'url' => 'http://xinwen.eastday.com/a/180518140901127.html?qid=news.baidu.com');
		array_push($arr,$arr2);
		return json_encode($arr,JSON_UNESCAPED_UNICODE);
	}
}