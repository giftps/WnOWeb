<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
include('../class/Rest.php');
$api = new Users();
switch ($requestMethod) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        // if ($_POST['id']) {
        //     $api->getUserFeeds($_POST['id']);
        // }
        if ($data) {
            $api->getUserFeeds($data->uid);
        }
        break;
    default:
        $api->getUserFeeds(1);
        // header("HTTP/1.0 405 Method Not Allowed");
        break;
}
