<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));

// $api->getFeeds($_POST['user_id'], 0);
$api->getFeeds($data, 0);
