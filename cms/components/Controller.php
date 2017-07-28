<?php
namespace app\components;

use q;
use \app\libs\ipip\IP;

class Controller extends \q\web\Controller {


	//获取ip
	protected function getIp() {
		$ip = '';
		if( isset( $_SERVER['self_http_x_forwarded_for'] ) ) {
			return $_SERVER['self_http_x_forwarded_for'];
		}
		elseif( isset( $_SERVER['self_remote_addr'] ) ) {
			return $_SERVER['self_remote_addr'];
		}
		if( isset( $_SERVER['SELF_HTTP_X_FORWARDED_FOR'] ) ) {
			return $_SERVER['SELF_HTTP_X_FORWARDED_FOR'];
		}
		elseif( isset( $_SERVER['SELF_REMOTE_ADDR'] ) ) {
			return $_SERVER['SELF_REMOTE_ADDR'];
		}
		elseif( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else {
			return $_SERVER['REMOTE_ADDR'];
		}
	}

	protected function getArea ( $ip ) {
		$city_area = Q::$app->redis->getClient()->get('ad_city_area');
		$all_area = Q::$app->redis->getClient()->get('ad_all_area');
	}

	//解析ip
	protected function parseIp( $ip='127.0.0.1' ) {
		return IP::find( $ip );
	}


	//解析经纬度
	protected function parseLal() {
	
	}

	//获取投放方式
	protected function getPutInMode ( $key ) {
	
	}

	//解词
	protected function getKeyword() {
	
	}

	//获取广告数据
	protected function getAdData() {
	
	}

	//关键字投放
	protected function keywordPutIn() {
	
	}

	//科室投放
	protected function departPutIn() {
	
	}

	//域名投放
	protected function domainPutIn() {
	
	}

	//域名2投放
	protected function domain2PutIn() {
	
	}

	//栏目2投放
	protected function topicPutIn() {
	
	}
}

?>
