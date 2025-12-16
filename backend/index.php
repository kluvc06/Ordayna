<?php
declare(strict_types=1);

require 'vendor/autoload.php';

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
    case "get_session_token":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->getSessionToken();
        switch ($res) {
            case TokenRet::success:
                http_response_code(200);
                break;
            case TokenRet::bad_request:
                http_response_code(400);
                echo "Bad request";
                break;
            case TokenRet::user_does_not_exist:
                http_response_code(400);
                echo "User does not exist";
                break;
            case TokenRet::unauthorised:
                http_response_code(403);
                echo "Unauthorised";
                break;
            case TokenRet::unexpected_error:
                http_response_code(400);
                echo "Unexpected error";
                break;
        };
        break;
    case "create_user":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->createUser();
        switch ($res) {
            case CreateUserRet::success:
                http_response_code(201);
                break;
            case CreateUserRet::bad_request:
                http_response_code(400);
                echo "Bad request";
                break;
            case CreateUserRet::user_already_exists:
                http_response_code(400);
                echo "User already exists";
                break;
            case CreateUserRet::unexpected_error:
                http_response_code(400);
                echo "Unexpected error";
                break;
        };
        break;
    case "delete_user":
        if ($_SERVER["REQUEST_METHOD"] != "DELETE") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->deleteUser();
        switch ($res) {
            case DeleteUserRet::success:
                http_response_code(204);
                break;
            case DeleteUserRet::bad_request:
                http_response_code(400);
                echo "Bad request";
                break;
            case DeleteUserRet::user_does_not_exist:
                http_response_code(400);
                echo "User does not exist";
                break;
            case DeleteUserRet::unauthorised:
                http_response_code(403);
                echo "Incorrect email and password pair";
                break;
            case DeleteUserRet::unexpected_error:
                http_response_code(400);
                echo "Unexpected error";
                break;
        }
        break;
    case "change_disp_name":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->changeDisplayName();
        switch ($res) {
            case ChangeUserRet::success:
                http_response_code(204);
                break;
            case ChangeUserRet::bad_request:
                http_response_code(400);
                echo "Bad request";
                break;
            case ChangeUserRet::user_does_not_exist:
                http_response_code(400);
                echo "User does not exist";
                break;
            case ChangeUserRet::unauthorised:
                http_response_code(403);
                echo "Incorrect email and password pair";
                break;
            case ChangeUserRet::unexpected_error:
                http_response_code(400);
                echo "Unexpected error";
                break;
        }
        break;
    case "change_phone_number":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->changePhoneNumber();
        switch ($res) {
            case ChangeUserRet::success:
                http_response_code(204);
                break;
            case ChangeUserRet::bad_request:
                http_response_code(400);
                echo "Bad request";
                break;
            case ChangeUserRet::user_does_not_exist:
                http_response_code(400);
                echo "User does not exist";
                break;
            case ChangeUserRet::unauthorised:
                http_response_code(403);
                echo "Incorrect email and password pair";
                break;
            case ChangeUserRet::unexpected_error:
                http_response_code(400);
                echo "Unexpected error";
                break;
        }
        break;
    case "change_pass":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }
        $res = $user_controller->changePassword();
        switch ($res) {
            case ChangeUserRet::success:
                http_response_code(204);
                break;
            case ChangeUserRet::bad_request:
                http_response_code(400);
                echo "Bad request";
                break;
            case ChangeUserRet::user_does_not_exist:
                http_response_code(400);
                echo "User does not exist";
                break;
            case ChangeUserRet::unauthorised:
                http_response_code(403);
                echo "Incorrect email and password pair";
                break;
            case ChangeUserRet::unexpected_error:
                http_response_code(400);
                echo "Unexpected error";
                break;
        }
        break;
    default:
        http_response_code(404);
        break;
}
