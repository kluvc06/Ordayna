<?php

declare(strict_types=1);

require 'vendor/autoload.php';

include "controller.php";
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
        include "resource/index.php";
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
    case "token":
        if (count($req_uri) <= 2) {
            http_response_code(404);
            break;
        }

        switch ($req_uri[2]) {
            case "get_refresh_token":
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->getRefreshToken());
                break;
            case "refresh_refresh_token":
                if ($_SERVER["REQUEST_METHOD"] != "GET") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->refreshRefreshToken());
                break;
            case "get_access_token":
                if ($_SERVER["REQUEST_METHOD"] != "GET") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->getAccessToken());
                break;
            default:
                http_response_code(404);
                return;
        }
        break;
    case "user":
        if (count($req_uri) <= 2) {
            http_response_code(404);
            break;
        }

        switch ($req_uri[2]) {
            case "create_user":
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->createUser());
                break;
            case "delete_user":
                if ($_SERVER["REQUEST_METHOD"] != "DELETE") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->deleteUser());
                break;
            case "change_disp_name":
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->changeDisplayName());
                break;
            case "change_phone_number":
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->changePhoneNumber());
                break;
            case "change_pass":
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($user_controller->changePassword());
                break;
            default:
                http_response_code(404);
                break;
        }
        break;
    case "create_intezmeny":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }
        handleReturn($user_controller->createIntezmeny());
        break;
    case "delete_intezmeny":
        if ($_SERVER["REQUEST_METHOD"] != "DELETE") {
            http_response_code(405);
            break;
        }
        handleReturn($user_controller->deleteIntezmeny());
        break;
    case "get_intezmenys":
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            http_response_code(405);
            break;
        }
        handleReturn($user_controller->getIntezmenys());
        break;
    case "intezmeny":
        if (count($req_uri) <= 2) {
            http_response_code(404);
            break;
        }
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            break;
        }

        switch ($req_uri[2]) {
            case "create":
                if (count($req_uri) <= 3) {
                    http_response_code(404);
                    break;
                }
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                switch ($req_uri[3]) {
                    case "class":
                        handleReturn($user_controller->createClass());
                        break;
                    case "lesson":
                        handleReturn($user_controller->createLesson());
                        break;
                    case "group":
                        handleReturn($user_controller->createGroup());
                        break;
                    case "room":
                        handleReturn($user_controller->createRoom());
                        break;
                    case "teacher":
                        handleReturn($user_controller->createTeacher());
                        break;
                    case "timetable_element":
                        handleReturn($user_controller->createTimetableElement());
                        break;
                    case "homework":
                        handleReturn($user_controller->createHomework());
                        break;
                    case "attachment":
                        handleReturn($user_controller->createAttachment());
                        break;
                    default:
                        http_response_code(404);
                        break;
                }
                break;
            case "get":
                if (count($req_uri) <= 3) {
                    http_response_code(404);
                    break;
                }
                if ($_SERVER["REQUEST_METHOD"] != "POST") {
                    http_response_code(405);
                    break;
                }
                switch ($req_uri[3]) {
                    case "classes":
                        handleReturn($user_controller->getClasses());
                        break;
                    case "groups":
                        handleReturn($user_controller->getGroups());
                        break;
                    case "lessons":
                        handleReturn($user_controller->getLessons());
                        break;
                    case "rooms":
                        handleReturn($user_controller->getRooms());
                        break;
                    case "teachers":
                        handleReturn($user_controller->getTeachers());
                        break;
                    case "timetable":
                        handleReturn($user_controller->getTimetable());
                        break;
                    case "homeworks":
                        handleReturn($user_controller->getHomeworks());
                        break;
                    case "attachment":
                        handleReturn($user_controller->getAttachment());
                        break;
                    default:
                        http_response_code(404);
                        break;
                }
                break;
            default:
                http_response_code(404);
                break;
        }
        break;
    default:
        http_response_code(404);
        break;
}
