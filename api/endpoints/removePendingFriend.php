<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));
$api->removePendingFriend($data->user_id, $data->friend_to_remove);