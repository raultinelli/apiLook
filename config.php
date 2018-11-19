<?php
require 'environment.php';

global $config;
$config = array();
if(ENVIRONMENT == 'development') {
	define("BASE_URL", "http://lookupgg.com/apis/lookupgg/");
	$config['dbname'] = 'ponta451_lookup';
	$config['host'] = 'localhost';
	$config['dbuser'] = 'ponta451_raul';
	$config['dbpass'] = '1597530';
	$config['jwt_secret_key'] = "Look123!";
} else {
	define("BASE_URL", "http://localhost/apis/devstagram/");
	$config['dbname'] = 'devstagram';
	$config['host'] = 'localhost';
	$config['dbuser'] = 'root';
	$config['dbpass'] = 'root';
	$config['jwt_secret_key'] = "abC123!";
}

global $db;
try {
	$db = new PDO("mysql:dbname=".$config['dbname'].";host=".$config['host'], $config['dbuser'], $config['dbpass']);
} catch(PDOException $e) {
	echo "ERRO: ".$e->getMessage();
	exit;
}