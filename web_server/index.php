<?php

declare(strict_types=1);

require 'vendor/autoload.php';

include "controller.php";
$controller = new Controller();

$req_uri = explode("/", explode("?", $_SERVER["REQUEST_URI"])[0]);

if (count($req_uri) <= 1) {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        return;
    }
    include "../base/index.php";
    http_response_code(200);
    return;
}

switch ($req_uri[1]) {
    case "":
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            break;
        }
        include "resource/index.php";
        http_response_code(200);
        break;
    case "token":
        if (count($req_uri) <= 2) {
            http_response_code(404);
            break;
        }

        switch ($req_uri[2]) {
            case "get_refresh_token":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->getRefreshToken());
                break;
            case "refresh_refresh_token":
                if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->refreshRefreshToken());
                break;
            case "get_access_token":
                if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->getAccessToken());
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
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->createUser());
                break;
            case "profile":
                if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->getProfile());
                break;
            case "delete_user":
                if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->deleteUser());
                break;
            case "change_disp_name":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->changeDisplayName());
                break;
            case "change_phone_number":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->changePhoneNumber());
                break;
            case "change_pass":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                handleReturn($controller->changePassword());
                break;
            default:
                http_response_code(404);
                break;
        }
        break;
    case "create_intezmeny":
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            break;
        }
        handleReturn($controller->createIntezmeny());
        break;
    case "delete_intezmeny":
        if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
            http_response_code(405);
            break;
        }
        handleReturn($controller->deleteIntezmeny());
        break;
    case "get_intezmenys":
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            break;
        }
        handleReturn($controller->getIntezmenys());
        break;
    case "intezmeny":
        if (count($req_uri) <= 2) {
            http_response_code(404);
            break;
        }

        switch ($req_uri[2]) {
            case "user":
                if (count($req_uri) <= 3) {
                    http_response_code(404);
                    break;
                }
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                switch ($req_uri[3]) {
                    case "invite":
                        handleReturn($controller->inviteToIntezmeny());
                        break;
                    case "accept_invite":
                        handleReturn($controller->acceptInviteToIntezmeny());
                        break;
                    default:
                        http_response_code(404);
                        break;
                }
                break;
            case "create":
                if (count($req_uri) <= 3) {
                    http_response_code(404);
                    break;
                }
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                switch ($req_uri[3]) {
                    case "class":
                        handleReturn($controller->createClass());
                        break;
                    case "lesson":
                        handleReturn($controller->createLesson());
                        break;
                    case "group":
                        handleReturn($controller->createGroup());
                        break;
                    case "room":
                        handleReturn($controller->createRoom());
                        break;
                    case "teacher":
                        handleReturn($controller->createTeacher());
                        break;
                    case "timetable_element":
                        handleReturn($controller->createTimetableElement());
                        break;
                    case "homework":
                        handleReturn($controller->createHomework());
                        break;
                    case "attachment":
                        handleReturn($controller->createAttachment());
                        break;
                    default:
                        http_response_code(404);
                        break;
                }
                break;
            case "delete":
                if (count($req_uri) <= 3) {
                    http_response_code(404);
                    break;
                }
                if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
                    http_response_code(405);
                    break;
                }
                switch ($req_uri[3]) {
                    case "class":
                        handleReturn($controller->deleteClass());
                        break;
                    case "lesson":
                        handleReturn($controller->deleteLesson());
                        break;
                    case "group":
                        handleReturn($controller->deleteGroup());
                        break;
                    case "room":
                        handleReturn($controller->deleteRoom());
                        break;
                    case "teacher":
                        handleReturn($controller->deleteTeacher());
                        break;
                    case "timetable_element":
                        handleReturn($controller->deleteTimetableElement());
                        break;
                    case "homework":
                        handleReturn($controller->deleteHomework());
                        break;
                    case "attachment":
                        handleReturn($controller->deleteAttachment());
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
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                switch ($req_uri[3]) {
                    case "classes":
                        handleReturn($controller->getClasses());
                        break;
                    case "groups":
                        handleReturn($controller->getGroups());
                        break;
                    case "lessons":
                        handleReturn($controller->getLessons());
                        break;
                    case "rooms":
                        handleReturn($controller->getRooms());
                        break;
                    case "teachers":
                        handleReturn($controller->getTeachers());
                        break;
                    case "timetable":
                        handleReturn($controller->getTimetable());
                        break;
                    case "homeworks":
                        handleReturn($controller->getHomeworks());
                        break;
                    case "attachment":
                        handleReturn($controller->getAttachment());
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
