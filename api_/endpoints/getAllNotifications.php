<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));

// $api->getNotifications($_POST['user_id'], 0);
$api->getNotifications($data);
