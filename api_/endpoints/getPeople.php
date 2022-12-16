<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));
// $id = $_POST['user_id'];
// $api->getUsers($id);
$api->getUsers($data->user_id);
