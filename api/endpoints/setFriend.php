<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));
// $id = $_POST['user_id'];
// $api->getUsers($id);
$api->setFriend($data->friend_to_be, $data->user_id);
// echo json_encode($data);
