<?php
$server_config = include( 'serverConfig.php' );

return [
	'basePath'=>dirname(__FILE__) . '/' . '..',
	'name'=>'ForwardCMS',
	'defaultRoute'=>'index',
	'timeZone' => 'Asia/Shanghai',

	// application components
	'components'=> [
		// uncomment the following to enable URLs in path-format
		'urlManager'=> [
			//Url模式 false 普通模式, true pathinfo模式
			'enablePrettyUrl' => false,
			'enableStrictParsing' => false,
			//url伪装后缀
			'suffix'=>'.html',

			//route
			'rules'=> [
				'<controller:\w+>'=>'<controller>',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>[/<params:.*>]'=>'<controller>/<action>',
			],
		],
		/*
		'db' => [
			'connectionString' => 'mysql:host=127.0.0.1;dbname=forwardcms',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => '',
			'charset' => 'utf8',
			'tablePrefix' => 'iforward_',
		],
		 */
		'redis' => [
			'class' => 'app\components\Redis',
			'hostname' => $server_config['XYWYSRV_REDIS_HOST_1'],
			'port' => $server_config['XYWYSRV_REDIS_PORT_1'],
			'database' => 0,
			'prefix' => '',
		],

		'redislal' => [
			'class' => 'app\components\Redis',
			'hostname' => $server_config['XYWYSRV_REDIS_HOST_2'],
			'port' => $server_config['XYWYSRV_REDIS_PORT_2'],
			'database' => 1,
			'prefix' => '',
		],

		'redisip' => [
			'class' => 'app\components\Redis',
			'hostname' => $server_config['XYWYSRV_REDIS_HOST_3'],
			'port' => $server_config['XYWYSRV_REDIS_PORT_3'],
			'database' => 0,
			'prefix' => '',
		],

		'curl' => [
			'class'=>'app\components\Curl',
			'slowtime' => '3',
			'slowlog' => false,
		],

		'keyword' => [
			'class'=>'app\components\Keywords',
			'slowlog' => false,
			//http://apiservice.kddi.xywy.com/seg/disect/filterKey.do?m=exe&type=allKeyAd&&charset=UTF-8&num=10&keyword=xxxxxxxx
			#'parse_url'=>'http://apiservice.kddi.xywy.com/seg/disect/filterKey.do?m=exe&type=allKeyAd&&charset=UTF-8&num=10',
			'parse_url'=>'http://adterm.rt.xywy.com/xywy-tag-analyze/ad_analyze?version=old',
		],
		'lal' => [
			'class'=>'app\components\LongitudeAndLatitude',
			'slowlog' => false,
			//http://geocode.mapbar.com/inverse/getInverseGeocoding.json?detail=1&zoom=11&road=1&outGb=g02&inGb=g02&latlon=116.346,39.984
			'parse_url'=>'http://geocode.mapbar.com/inverse/getInverseGeocoding.json?detail=1&zoom=11&road=1&outGb=g02&inGb=g02',
		],

		/*
		'log' => [
			'targets' => [
				'file' => [
					'class' => 'q\log\FileTarget',
					'levels' => ['error'],
					//'levels' => ['trace', 'info','profile'],
					//'levels' => ['error', 'debug','info','profile'],
					'maxFileSize' => 4096000, //KB
				],
			],
		],
		*/

		'errorHandler' => [
			'discardExistingOutput'=>true,
		],

	],

	/*
	'modules'=>array(
		'demo' => [
			'class'=>'modules\demo\Module',
		],
        'test' => [
            'class' => 'app\modules\test\Module',
        ],
	),
*/


	// using Q::$app->params['paramName']
	'params'=> [
		'domain'=>'display.xywy.com',
		'email'=>'87281405@qq.com',
		'putin'=> [ 'keyword'=>1, 'department'=>'2', 'domain'=>'3', 'domain2'=>'4', 'column'=>'5' ],
	],
];
