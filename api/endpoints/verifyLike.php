<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();
switch ($requestMethod) {
	case 'POST':
		$api->verifyLike($_POST['user_id'], $_POST['post'], $_POST['type']);
		break;
	default:
		header("HTTP/1.0 405 Method Not Allowed");
		break;
}
