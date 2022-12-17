<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$data = json_decode(file_get_contents('php://input'));

$api->getGroupPosts($data);
// $api->getComments($_POST['post_id'], 0);
