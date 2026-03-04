<?php

declare(strict_types=1);

namespace Router;

require 'vendor/autoload.php';
require_once "controller.php";

use Controller\Controller;

$controller = new Controller();

function route(string $method, callable $function): never
{
    if ($_SERVER["REQUEST_METHOD"] === $method) {
        call_user_func($function);
    } else {
        http_response_code(405);
    }
    exit();
}

// This is never reached when the backend is behind nginx but the built in php server depends on this
if (explode("/", explode("?", $_SERVER["REQUEST_URI"])[0])[1] === 'resource') {
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        return false;
    } else {
        http_response_code(405);
    }
    exit();
}

$req_uri = explode("?", $_SERVER["REQUEST_URI"])[0];

if ($req_uri === '/' or $req_uri === '') {
    route("GET", function () {
        header("Location: /resource/index.html", true, 308);
    });
}

match ($req_uri) {
    '/token/get_refresh_token' => route('POST', [$controller, 'getRefreshToken']),
    '/token/refresh_refresh_token' => route('GET', [$controller, 'refreshRefreshToken']),
    '/token/get_access_token' => route('GET', [$controller, 'getAccessToken']),
    '/user/create' => route('POST', [$controller, 'createUser']),
    '/user/profile' => route('GET', [$controller, 'getProfile']),
    '/user/delete' => route('DELETE', [$controller, 'deleteUser']),
    '/user/change/display_name' => route('POST', [$controller, 'changeDisplayName']),
    '/user/change/phone_number' => route('POST', [$controller, 'changePhoneNumber']),
    '/user/change/password' => route('POST', [$controller, 'changePassword']),
    '/create_intezmeny' => route('POST', [$controller, 'createIntezmeny']),
    '/delete_intezmeny' => route('DELETE', [$controller, 'deleteIntezmeny']),
    '/get_intezmenys' => route('GET', [$controller, 'getIntezmenys']),
    '/intezmeny/user/invite' => route('POST', [$controller, 'inviteToIntezmeny']),
    '/intezmeny/user/accept_invite' => route('POST', [$controller, 'acceptInviteToIntezmeny']),
    '/intezmeny/create/class' => route('POST', [$controller, 'createClass']),
    '/intezmeny/create/lesson' => route('POST', [$controller, 'createLesson']),
    '/intezmeny/create/group' => route('POST', [$controller, 'createGroup']),
    '/intezmeny/create/room' => route('POST', [$controller, 'createRoom']),
    '/intezmeny/create/teacher' => route('POST', [$controller, 'createTeacher']),
    '/intezmeny/create/timetable_element' => route('POST', [$controller, 'createTimetableElement']),
    '/intezmeny/create/homework' => route('POST', [$controller, 'createHomework']),
    '/intezmeny/create/attachment' => route('POST', [$controller, 'createAttachment']),
    '/intezmeny/delete/class' => route('DELETE', [$controller, 'deleteClass']),
    '/intezmeny/delete/lesson' => route('DELETE', [$controller, 'deleteLesson']),
    '/intezmeny/delete/group' => route('DELETE', [$controller, 'deleteGroup']),
    '/intezmeny/delete/room' => route('DELETE', [$controller, 'deleteRoom']),
    '/intezmeny/delete/teacher' => route('DELETE', [$controller, 'deleteTeacher']),
    '/intezmeny/delete/timetable_element' => route('DELETE', [$controller, 'deleteTimetableElement']),
    '/intezmeny/delete/homework' => route('DELETE', [$controller, 'deleteHomework']),
    '/intezmeny/delete/attachment' => route('DELETE', [$controller, 'deleteAttachment']),
    '/intezmeny/update/class' => route('POST', [$controller, 'updateClass']),
    '/intezmeny/update/lesson' => route('POST', [$controller, 'updateLesson']),
    '/intezmeny/update/group' => route('POST', [$controller, 'updateGroup']),
    '/intezmeny/update/room' => route('POST', [$controller, 'updateRoom']),
    '/intezmeny/update/teacher' => route('POST', [$controller, 'updateTeacher']),
    '/intezmeny/update/timetable_element' => route('POST', [$controller, 'updateTimetableElement']),
    '/intezmeny/update/homework' => route('POST', [$controller, 'updateHomework']),
    '/intezmeny/get/classes' => route('POST', [$controller, 'getClasses']),
    '/intezmeny/get/lessons' => route('POST', [$controller, 'getLessons']),
    '/intezmeny/get/groups' => route('POST', [$controller, 'getGroups']),
    '/intezmeny/get/rooms' => route('POST', [$controller, 'getRooms']),
    '/intezmeny/get/teachers' => route('POST', [$controller, 'getTeachers']),
    '/intezmeny/get/timetable' => route('POST', [$controller, 'getTimetable']),
    '/intezmeny/get/homeworks' => route('POST', [$controller, 'getHomeworks']),
    '/intezmeny/get/attachment' => route('POST', [$controller, 'getAttachment']),
    default => route("GET", function () {
        header("Location: /resource/index.html", true, 308);
    }),
};
