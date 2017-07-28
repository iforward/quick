<?php
namespace app\controllers;
use q;
use app\models as models;
use \app\libs\ipip\IP;

class GetController extends \q\web\Controller {

	private $debug = false;
	private $model = false;

	public function actionIp() {

		$action = strip_tags( Q::$app->request->get( 'action' ) );
		$action = $action ? : false;
		$parse_request_model = new models\ParseRequest;
		$ip = $parse_request_model->getIP();
		$addr = $parse_request_model->getArea();

		if( $action == 'load' ) {
			echo 'var _RET_IP = '.json_encode( [ 'status'=> '0', 'data'=>[ 'ip'=>$ip, 'area'=>$addr ] ] );
		}
		elseif( $action == 'callback' ) {
			echo 'ipback('.json_encode( [ 'status'=> '0', 'data'=>[ 'ip'=>$ip, 'area'=>$addr ] ] ).')';
		}
		else {
			echo json_encode( [ 'status'=> '0', 'data'=>[ 'ip'=>$ip, 'area'=>$addr ] ] );
		}
	}

	public function actionArea() {
		$action = strip_tags( Q::$app->request->get( 'action' ) );
		$action = $action ? : false;
		$parse_request_model = new models\ParseRequest;
		$ip = $parse_request_model->getIP();
		$area = IP::find( $ip );
		return json_encode( $area );
	}

	public function actionUserip() {
		$parse_request_model = new models\ParseRequest;
		$ip = $parse_request_model->getIP();
		$addr = $parse_request_model->getArea();

		echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf8" /></head>';
		echo '<script type="text/javascript" src="http://static.js.xywy.com/poster_pos/js/pos.js?n='.time().'"></script><script>(function(){getPos(function(x, y){im=new Image; im.src="http://stat-z.xywy.com/test.png?t_c=2&tt"+Math.random();document.write("lng:"+x+"<br>");document.write("lat:"+y+"<br>");});})();</script><br/>';
		echo 'self_browser_x_forwarded_for:'. ( isset( $_SERVER['self_browser_x_forwarded_for'] ) ? $_SERVER['self_browser_x_forwarded_for'] : '' )."<br>\n";
		echo 'self_http_x_forwarded_for:'.( isset( $_SERVER['self_http_x_forwarded_for'] ) ? $_SERVER['self_http_x_forwarded_for'] : '' )."<br>\n";
		echo 'self_remote_addr:'. ( isset( $_SERVER['self_remote_addr'] ) ? $_SERVER['self_remote_addr'] : '' )."<br>\n";
		echo 'SELF_HTTP_X_FORWARDED_FOR:'. ( isset( $_SERVER['SELF_HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['SELF_HTTP_X_FORWARDED_FOR'] : '' )."<br>\n";
		echo 'SELF_REMOTE_ADDR:'. ( isset( $_SERVER['SELF_REMOTE_ADDR'] ) ? $_SERVER['SELF_REMOTE_ADDR'] : '' ) ."<br>\n";
		echo 'HTTP_X_FORWARDED_FOR:' . ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '' ) ."<br>\n";
		echo 'REMOTE_ADDR:' . ( isset ( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ) ."<br>\n";
		echo 'real ip:'.$ip."<br>\n<br>\n";

		echo '-----------------------------------------------------华丽的分割线------------------------------------------------------------'."<br>\n<br>\n";

		echo "ipip->cz88地区:".@join( '-', $addr )."<br>\n";
		echo "ipip解析地区:".$parse_request_model->parseIp($ip)."<br>\n";
		echo "cz88解析地区:".$parse_request_model->parseIpCz88($ip)."<br>\n";

		return false;
	}


	public function beforeAction() {
		//是否开调试模式
		Q::$app->request->get( 'debug' ) && $this->debug = true; 
		unset( $_GET['debug'] );
	}

	public function afterAction() {
		/**********调试模式**********/
		if( $this->debug === true ) {
			header('Content-Type:text/html;charset=utf8');
			foreach( Q::getLogger()->getMessages() as $val ) {
				if( $val[1] == \q\log\Logger::LEVEL_DEBUG  ) {
				echo "<div style='clear: both;margin:0;padding:0;text-decoration:none;border:0 none;font-size:14px;font-family:\5FAE\8F6F\96C5\9ED1;'><div style='float:left;width:170px;'>Time: [ {$val[3]} ]</div><div style='float:left;margin-left:4px;margin-right:4px;'>--</div><div style='float:left;width:80%'> Message: [ {$val[0]} ]</div></div>\n";
				}
			}
			echo '<div style="clear:both;">===========================================================================</div>';
		}
		/************end************/
	}


}

?>
