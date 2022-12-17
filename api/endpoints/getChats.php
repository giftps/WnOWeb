<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));

$api->getChatMessages($data->uid, $data->cid, $data->start, $data->type);
// $api->getChatMessages($_POST['uid'], $_POST['cid'], $_POST['start'], $_POST['type']);
