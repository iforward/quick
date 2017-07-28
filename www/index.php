<?php
defined('Q_ENV') or define('Q_ENV', 'prod');

//error_reporting( "E_ERROR | E_PARSE | E_NOTICE" );
error_reporting(0); 

ini_set('memory_limit','32M');
ini_set("max_execution_time",10);
ini_set('session.gc_maxlifetime',43200);
ini_set('date.timezone','Asia/Shanghai');


// change the following paths if necessary
$quick = dirname(__FILE__).'/../framework/Q.php';
$config = include( dirname(__FILE__).'/../cms/config/main.php' );

require_once($quick);
Q::beginProfile( 'ad' );
(new q\web\Application($config))->run();
Q::endProfile( 'ad' );
?>
