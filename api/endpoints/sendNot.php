<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();

$api->sendNotification($_POST['title'], $_POST['message'], $_POST['recipient'], $_POST['channel']);
