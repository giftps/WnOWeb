<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();
switch ($requestMethod) {
	case 'POST':
		$data = json_decode(file_get_contents('php://input'));
		$api->login($data);
		// $api->login($_POST);
		break;
	default:
		header("HTTP/1.0 405 Method Not Allowed");
		break;
}
