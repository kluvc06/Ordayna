<?php

include "user_controller.php";
$user_controller = new UserController();

$req_uri = explode("/", explode("?", $_SERVER["REQUEST_URI"])[0]);

if (count($req_uri) <= 1) {
    if ($_SERVER["REQUEST_METHOD"] != "GET") {
        http_response_code(405);
        return;
    }
    include "../base/index.php";
    http_response_code(200);
    return;
}

switch ($req_uri[1]) {
    case "":
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            http_response_code(405);
            break;
        }
        include "../base/index.php";
        http_response_code(200);
        break;
    case "login":
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            http_response_code(405);
            break;
        }
        include "../base/login.php";
        http_response_code(200);
        break;
    case "signup":
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            http_response_code(405);
            break;
        }
        include "../base/signup.php";
        http_response_code(200);
        break;
    case "get_all_users":
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->getAllUsers();
        if ($res == null) {
            http_response_code(400);
            break;
        }
        echo $res;
        http_response_code(200);
        break;
    default:
        http_response_code(404);
        break;
}
