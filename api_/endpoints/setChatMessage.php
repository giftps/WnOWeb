<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));

$api->setNewMessage($data->uid, $data->ToId, $data->msg);
