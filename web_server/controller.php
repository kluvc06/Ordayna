<?php

declare(strict_types=1);
// TODO: Use argon2id instead of bcrypt
// TODO: Rework session token revocation

static $is_test_server = php_sapi_name() === "cli-server";
/** 20 mebibytes */
static $max_file_size = 1024 * 1024 * 20;

use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;

include "db.php";
include "jwt.php";

class Controller
{
    private $jwt;

    function __construct()
    {
        $this->jwt = new JWT();
    }

    public function getRefreshToken(): ControllerRet
    {
        global $is_test_server;

        $data = json_decode(file_get_contents("php://input"));
        $email = $this->validateEmail(@$data->email);
        if ($email === null) return ControllerRet::bad_request;
        $pass = $this->validateString(@$data->pass, min_chars: 8, max_chars: 300);
        if ($pass === null) return ControllerRet::bad_request;

        $db = new DB();

        $ret = $db->userExistsViaEmail($email);
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        $user_pass = $db->getUserPassViaEmail($email);
        if ($user_pass === null) return ControllerRet::unexpected_error;
        if (password_verify($pass, $user_pass) === false) return ControllerRet::unauthorised;

        $user_id = $db->getUserIdViaEmail($email);
        if ($user_id === null) return ControllerRet::unexpected_error;

        $refresh_token = $this->jwt->createRefreshToken($user_id);

        $arr_cookie_options = array(
            'expires' => time() + 60 * 60 * 24 * 15,
            'path' => '/token/',
            'domain' => '',
            'secure' => !$is_test_server,
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', $refresh_token->toString(), $arr_cookie_options);

        return ControllerRet::success;
    }

    public function refreshRefreshToken(): ControllerRet
    {
        global $is_test_server;
        $db = new DB();

        $token = $this->validateRefreshToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;
        $new_token = $this->jwt->createRefreshToken($token->claims()->get("uid"));

        // Expires after 15 days
        if ($db->newInvalidRefreshToken($token->claims()->get(RegisteredClaims::ID), '15 0:0:0') === null) return ControllerRet::unexpected_error;

        $arr_cookie_options = array(
            // 15 days
            'expires' => time() + 60 * 60 * 24 * 15,
            'path' => '/token/',
            'domain' => '',
            'secure' => !$is_test_server,
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', $new_token->toString(), $arr_cookie_options);

        return ControllerRet::success;
    }

    public function getAccessToken(): ControllerRet
    {
        global $is_test_server;
        $db = new DB();

        $token = $this->validateRefreshToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;
        $new_access_token = $this->jwt->createAccessToken($token->claims()->get("uid"));

        $arr_cookie_options = array(
            // 10 minutes
            'expires' => time() + 60 * 10,
            'path' => '/',
            'domain' => '',
            'secure' => !$is_test_server,
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('AccessToken', $new_access_token->toString(), $arr_cookie_options);

        return ControllerRet::success;
    }

    public function createUser(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $disp_name = $this->validateString(@$data->disp_name, max_chars: 200);
        if ($disp_name === null) return ControllerRet::bad_request;
        $email = $this->validateEmail(@$data->email);
        if ($email === null) return ControllerRet::bad_request;
        $pass = $this->validateString(@$data->pass, min_chars: 8, max_chars: 300);
        if ($pass === null) return ControllerRet::bad_request;
        $phone_number = $this->validatePhoneNumber(@$data->phone_number, true);
        if ($phone_number === null) return ControllerRet::bad_request;
        if ($phone_number === false) $phone_number = null;

        $db = new DB();

        $ret = $db->userExistsViaEmail($email);
        if ($ret === true) return ControllerRet::already_exists;
        if ($ret === null) return ControllerRet::unexpected_error;

        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
        if ($db->createUser($disp_name, $email, $phone_number, $pass_hash) === false) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function deleteUser(): ControllerRet
    {
        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        // TODO: delete intezmeny's whose sole owner is this user
        if ($db->deleteUserViaId($token->claims()->get("uid")) === null) return ControllerRet::unexpected_error;

        // Unset token cookies
        setcookie('RefreshToken', "", 0);
        setcookie('AccessToken', "", 0);

        return ControllerRet::success_no_content;
    }

    public function changeDisplayName(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $new_disp_name = $this->validateString(@$data->new_disp_name, max_chars: 200);
        if ($new_disp_name === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        if ($db->changeDisplayNameViaId($token->claims()->get("uid"), $new_disp_name) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    public function changePhoneNumber(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $new_phone_number = $this->validatePhoneNumber(@$data->new_phone_number, false);
        if ($new_phone_number === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        if ($db->changePhoneNumberViaId($token->claims()->get("uid"), $data->new_phone_number) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    // TODO: Ask for the old password
    public function changePassword(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $new_pass = $this->validateString(@$data->new_pass, min_chars: 8, max_chars: 300);
        if ($new_pass === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        if ($db->changePasswordHashViaId($token->claims()->get("uid"), password_hash($new_pass, PASSWORD_BCRYPT)) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    public function createIntezmeny(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $intezmeny_name = $this->validateString(@$data->intezmeny_name, max_chars: 200);
        if ($intezmeny_name === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        if ($db->createIntezmeny($intezmeny_name, $token->claims()->get("uid")) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function deleteIntezmeny(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $intezmeny_id = $this->validateInteger(@$data->intezmeny_id);
        if ($intezmeny_id === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        $ret = $db->userExists($token->claims()->get("uid"));
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;
        $ret = $db->partOfIntezmeny($intezmeny_id, $token->claims()->get("uid"), true);
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;
        $ret = $db->isAdmin($intezmeny_id, $token->claims()->get("uid"));
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteIntezmeny($intezmeny_id) === null) return ControllerRet::unexpected_error;
        if (rmdirRecursive("user_data/intezmeny_$intezmeny_id") === false) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    public function getIntezmenys(): ControllerRet
    {
        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        $ret = $db->getIntezmenys($token->claims()->get("uid"));
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret);

        return ControllerRet::success;
    }

    public function getProfile(): ControllerRet
    {
        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        $ret = $db->getProfile($token->claims()->get("uid"));
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret);

        return ControllerRet::success;
    }

    public function inviteToIntezmeny(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $email = $this->validateEmail(@$data->email);
        if ($email === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, false);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->userExistsViaEmail($email);
        if ($ret === false) return ControllerRet::not_found;
        if ($ret === null) return ControllerRet::unexpected_error;
        $invitee_uid = $db->getUserIdViaEmail($email);
        if ($invitee_uid === null) return ControllerRet::unexpected_error;
        $ret = $db->partOfIntezmeny($intezmeny_id, $invitee_uid, false);
        if ($ret === true) return ControllerRet::already_exists;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->inviteUser($intezmeny_id, $invitee_uid) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function acceptInviteToIntezmeny(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $ret = $this->validateIntezmenyData($data, false);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id, $uid) = $ret;

        $ret = $db->isInviteAccepted($intezmeny_id, $uid);
        if ($ret === true) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->acceptInvite($intezmeny_id, $uid) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function createClass(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $headcount = $this->validateInteger(@$data->headcount, 5);
        if ($headcount === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->classExistsViaName($intezmeny_id, $name);
        if ($ret === true) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;
        $ret = $db->groupExistsViaName($intezmeny_id, $name);
        if ($ret === true) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->createClass($intezmeny_id, $name, $headcount) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createLesson(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->lessonExistsViaName($intezmeny_id, $name);
        if ($ret === true) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->createLesson($intezmeny_id, $name) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createGroup(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $headcount = $this->validateInteger(@$data->headcount, 5);
        if ($headcount === null) return ControllerRet::bad_request;
        $class_id = $this->validateInteger(@$data->class_id, null_allowed: true);
        if ($class_id === null) return ControllerRet::bad_request;
        if ($class_id === false) $class_id = null;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($class_id !== null) {
            $ret = $db->classExists($intezmeny_id, $class_id);
            if ($ret === false) return ControllerRet::bad_request;
            if ($ret === null) return ControllerRet::unexpected_error;
        }
        $ret = $db->groupExistsViaName($intezmeny_id, $name);
        if ($ret === true) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->createGroup($intezmeny_id, $name, $headcount, $class_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createRoom(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $type = $this->validateString(@$data->type, max_chars: 200, null_allowed: true);
        if ($type === null) return ControllerRet::bad_request;
        if ($type === false) $type = null;
        $space = $this->validateInteger(@$data->space, 5);
        if ($space === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->roomExistsViaName($intezmeny_id, $name);
        if ($ret === true) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->createRoom($intezmeny_id, $name, $type, $space) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createTeacher(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $job = $this->validateString(@$data->job, max_chars: 200);
        if ($job === null) return ControllerRet::bad_request;
        $teacher_uid = $this->validateInteger(@$data->teacher_uid, null_allowed: true);
        if ($teacher_uid === null) return ControllerRet::bad_request;
        if ($teacher_uid === false) $teacher_uid = null;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($teacher_uid !== null) {
            $ret = $db->partOfIntezmeny($intezmeny_id, $teacher_uid, true);
            if ($ret === false) return ControllerRet::unauthorised;
            if ($ret === null) return ControllerRet::unexpected_error;
            $ret = $db->isTeacher($intezmeny_id, $teacher_uid);
            if ($ret === true) return ControllerRet::bad_request;
            if ($ret === null) return ControllerRet::unexpected_error;
        }

        if ($db->createTeacher($intezmeny_id, $name, $job, $teacher_uid) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createTimetableElement(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $duration = $this->validateTime(@$data->duration, time_allowed: true);
        if ($duration === null) return ControllerRet::bad_request;
        $day = $this->validateInteger(@$data->day);
        if ($day === null) return ControllerRet::bad_request;
        if ($day > 6 or $day < 0) return ControllerRet::bad_request;
        $from = $this->validateTime(@$data->from, date_allowed: true);
        if ($from === null) return ControllerRet::bad_request;
        $until = $this->validateTime(@$data->until, date_allowed: true);
        if ($until === null) return ControllerRet::bad_request;
        if ($from->getTimestamp() > $until->getTimestamp()) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($db->createTimetableElement($intezmeny_id, $duration->format("H:i:s"), $day, $from->format("Y-m-d"), $until->format("Y-m-d")) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createHomework(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $due = $this->validateTime(@$data->due, date_allowed: true, time_allowed: true, null_allowed: true);
        if ($due === null) return ControllerRet::bad_request;
        if ($due === false) $due = null;
        $lesson_id = $this->validateInteger(@$data->lesson_id, null_allowed: true);
        if ($lesson_id === null) return ControllerRet::bad_request;
        if ($lesson_id === false) $lesson_id = null;
        $teacher_id = $this->validateInteger(@$data->teacher_id, null_allowed: true);
        if ($teacher_id === null) return ControllerRet::bad_request;
        if ($teacher_id === false) $teacher_id = null;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($lesson_id !== null) {
            $ret = $db->lessonExists($intezmeny_id, $lesson_id);
            if ($ret === false) return ControllerRet::bad_request;
            if ($ret === null) return ControllerRet::unexpected_error;
        }
        if ($teacher_id !== null) {
            $ret = $db->teacherExists($intezmeny_id, $teacher_id);
            if ($ret === false) return ControllerRet::bad_request;
            if ($ret === null) return ControllerRet::unexpected_error;
        }

        if ($db->createHomework($intezmeny_id, $due !== null ? $due->format("Y-m-d h:i:s") : null, $lesson_id, $teacher_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createAttachment(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $homework_id = $this->validateInteger(@$data->homework_id);
        if ($homework_id === null) return ControllerRet::bad_request;
        $file_name = $this->validateFileName(@$data->file_name);
        if ($file_name === null) return ControllerRet::bad_request;
        $file_contents = $this->validateFileContents(@$data->file_contents);
        if ($file_contents === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->homeworkExists($intezmeny_id, $homework_id);
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        $attachment_id = $db->createAttachment($intezmeny_id, $homework_id, $file_name);
        if ($attachment_id === null) return ControllerRet::unexpected_error;
        if (file_force_contents("user_data/intezmeny_$intezmeny_id/" . $file_name . "_$attachment_id", $file_contents) === false) {
            if ($db->deleteAttachment($intezmeny_id, $attachment_id) === null) return ControllerRet::unexpected_error;
            return ControllerRet::unexpected_error;
        }

        return ControllerRet::success_created;
    }

    public function deleteClass(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $class_id = $this->validateInteger(@$data->class_id);
        if ($class_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->classExists($intezmeny_id, $class_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteClass($intezmeny_id, $class_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteLesson(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $lesson_id = $this->validateInteger(@$data->lesson_id);
        if ($lesson_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->lessonExists($intezmeny_id, $lesson_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteLesson($intezmeny_id, $lesson_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteGroup(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $group_id = $this->validateInteger(@$data->group_id);
        if ($group_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->groupExists($intezmeny_id, $group_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteGroup($intezmeny_id, $group_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteRoom(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $room_id = $this->validateInteger(@$data->room_id);
        if ($room_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->roomExists($intezmeny_id, $room_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteRoom($intezmeny_id, $room_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteTeacher(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $teacher_id = $this->validateInteger(@$data->teacher_id);
        if ($teacher_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->teacherExists($intezmeny_id, $teacher_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteTeacher($intezmeny_id, $teacher_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteTimetableElement(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $timetable_element_id = $this->validateInteger(@$data->timetable_element_id);
        if ($timetable_element_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->timetableElementExists($intezmeny_id, $timetable_element_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        if ($db->deleteTimetableElement($intezmeny_id, $timetable_element_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteHomework(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $homework_id = $this->validateInteger(@$data->homework_id);
        if ($homework_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->homeworkExists($intezmeny_id, $homework_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        $attachments = $db->getHomeworkAttachments($intezmeny_id, $homework_id);
        if ($attachments === null) return ControllerRet::unexpected_error;

        for ($i = 0; $i < count($attachments); $i++) {
            if (unlink("user_data/intezmeny_$intezmeny_id/" . $attachments[$i][1] . "_" . $attachments[$i][0]) === false) return ControllerRet::unexpected_error;
        }
        if ($db->deleteHomework($intezmeny_id, $homework_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function deleteAttachment(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $attachment_id = $this->validateInteger(@$data->attachment_id);
        if ($attachment_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->attachmentExists($intezmeny_id, $attachment_id);
        if ($ret === false) return ControllerRet::bad_request;
        if ($ret === null) return ControllerRet::unexpected_error;

        $attachment_name = $db->getAttachmentName($intezmeny_id, $attachment_id);
        if ($attachment_name === null) return ControllerRet::unexpected_error;

        if (unlink("user_data/intezmeny_$intezmeny_id/" . $attachment_name . "_" . $attachment_id) === false) return ControllerRet::unexpected_error;
        if ($db->deleteAttachment($intezmeny_id, $attachment_id) === null) return ControllerRet::unexpected_error;

        return ControllerRet::success;
    }

    public function getClasses(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getClasses($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getLessons(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getLessons($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getGroups(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getGroups($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getRooms(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getRooms($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getTeachers(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getTeachers($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret);

        return ControllerRet::success;
    }

    public function getTimetable(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getTimetable($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getHomeworks(): ControllerRet
    {
        $ret = $this->validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getHomeworks($intezmeny_id);
        if ($ret === null) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret);

        return ControllerRet::success;
    }

    public function getAttachment(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $attachment_id = $this->validateInteger(@$data->attachment_id);
        if ($attachment_id === null) return ControllerRet::bad_request;
        $ret = $this->validateIntezmenyData($data, true);
        if (is_a($ret, "ControllerRet") === true) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->attachmentExists($intezmeny_id, $attachment_id);
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        $attachment_name = $db->getAttachmentName($intezmeny_id, $attachment_id);
        if ($attachment_id === null) return ControllerRet::unexpected_error;

        $file_contents = file_get_contents("user_data/intezmeny_$intezmeny_id/" . $attachment_name . "_" . $attachment_id);
        if ($file_contents === false) return ControllerRet::unexpected_error;

        header('Content-Type: application/octet-stream');
        echo $file_contents;

        return ControllerRet::success;
    }

    /**
     * Returns the database connection and the intezmeny's id and the uid
     */
    private function validateIntezmenyData(mixed $data, bool $invite_must_be_accepted): ControllerRet|array
    {
        $intezmeny_id = $this->validateInteger(@$data->intezmeny_id);
        if ($intezmeny_id === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet") === true) return $token;

        $ret = $db->partOfIntezmeny($intezmeny_id, $token->claims()->get("uid"), $invite_must_be_accepted);
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        return array($db, $intezmeny_id, $token->claims()->get("uid"));
    }

    private function validateRefreshToken(DB $db): ControllerRet|UnencryptedToken
    {
        if (isset($_COOKIE["RefreshToken"]) === false or is_string($_COOKIE["RefreshToken"]) === false) return ControllerRet::bad_request;

        $token = $this->jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === null) return ControllerRet::bad_request;

        $ret = $db->isRevokedRefreshToken($token->claims()->get(RegisteredClaims::ID));
        if ($ret === true) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;
        if ($this->jwt->validateRefreshToken($token) === false) return ControllerRet::unauthorised;

        $ret = $db->userExists($token->claims()->get("uid"));
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        return $token;
    }

    private function validateAccessToken(DB $db): ControllerRet|UnencryptedToken
    {
        if (isset($_COOKIE["AccessToken"]) === false or is_string($_COOKIE["AccessToken"]) === false) return ControllerRet::bad_request;

        $token = $this->jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) return ControllerRet::bad_request;

        if ($this->jwt->validateAccessToken($token) === false) return ControllerRet::unauthorised;

        $ret = $db->userExists($token->claims()->get("uid"));
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        return $token;
    }

    // This function handles the case where $number is undefined
    // It's expected that $number is passed in with the "@" stfu operator
    // If null is allowed and $number is null then returns false
    private function validateInteger(mixed $number, int|null $max_digits = null, bool $null_allowed = false): int|null|false
    {
        if (!isset($number)) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        if (!is_string($number) or !is_numeric($number)) return null;
        if ($max_digits !== null and strlen($number) > $max_digits) return null;
        $int = intval($number);
        if (($int >= PHP_INT_MAX and $number !== strval(PHP_INT_MAX)) or ($int === 0 and $number !== "0")) return null;
        return $int;
    }

    // This function handles the case where $string is undefined
    // It's expected that $string is passed in with the "@" stfu operator
    // If null is allowed and $string is null then returns false
    private function validateString(mixed $string, int $min_chars = 1, int|null $max_chars = null, bool $null_allowed = false): string|null|false
    {
        if (!isset($string)) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        if (!is_string($string) or strlen($string) < $min_chars or ($max_chars !== null and strlen($string) > $max_chars)) return null;
        return (string) $string;
    }

    // This function handles the case where $email is undefined
    // It's expected that $email is passed in with the "@" stfu operator
    // If null is allowed and $email is null then returns false
    private function validateEmail(mixed $email, bool $null_allowed = false): string|null|false
    {
        if (isset($email) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        if (is_string($email) === false or preg_match('/^[^@]+[@]+[^@]+$/', $email) === 0) return null;
        return (string) $email;
    }

    // This function handles the case where $phone_number is undefined
    // It's expected that $phone_number is passed in with the "@" stfu operator
    // If null is allowed and $phone_number is null then returns false
    private function validatePhoneNumber(mixed $phone_number, bool $null_allowed = false): string|null|false
    {
        if (isset($phone_number) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        if ($this->validateString($phone_number, max_chars: 15, null_allowed: false) === null) return null;
        if (is_numeric($phone_number) === false) return null;
        return (string) $phone_number;
    }


    // This function handles the case where $time is undefined
    // It's expected that $time is passed in with the "@" stfu operator
    // If null is allowed and $time is null then returns false
    private function validateTime(mixed $time, bool $date_allowed = false, $time_allowed = false, bool $null_allowed = false): DateTimeImmutable|null|false
    {
        if (isset($time) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        $str_time = $this->validateString(@$time);
        if ($str_time === null) return null;
        if ($date_allowed === true and $time_allowed === true) {
            try {
                $ret = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $str_time);
            } catch (ValueError) {
                return null;
            }
        } else if ($date_allowed === true and $time_allowed === false) {
            try {
                $ret = DateTimeImmutable::createFromFormat("Y-m-d", $str_time);
            } catch (ValueError) {
                return null;
            }
        } else if ($date_allowed === false and $time_allowed === true) {
            try {
                $ret = DateTimeImmutable::createFromFormat("H:i:s", $str_time);
            } catch (ValueError) {
                return null;
            }
        } else {
            return null;
        }
        if ($ret === false) return null;
        if (DateTimeImmutable::getLastErrors() !== false) return null;
        return $ret;
    }

    /**
     * This function handles the case where $time is undefined
     * It's expected that $time is passed in with the "@" stfu operator
     * If null is allowed and $time is null then returns false
     * https://gist.github.com/doctaphred/d01d05291546186941e1b7ddc02034d3
     */
    function validateFileName(mixed $file_name, bool $null_allowed = false): string|null|false
    {
        if (isset($file_name) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        $str_file_name = $this->validateString($file_name, max_chars: 200);
        if ($str_file_name === null) return null;
        if ($str_file_name === "." or $str_file_name === "..") return null;
        if (preg_match('/^[^<>:?"*|\\/\\\\]+$/', $str_file_name) === 0) return null;
        if (str_ends_with(".", $str_file_name) or str_ends_with(" ", $str_file_name)) return null;
        $blown_name = explode(".", $str_file_name);
        if ($blown_name[0] === 'CON' or $blown_name[0] === 'PRN' or $blown_name[0] === 'AUX' or $blown_name[0] === 'NUL') return null;
        if ($blown_name[0] === 'COM1' or $blown_name[0] === 'COM2' or $blown_name[0] === 'COM3') return null;
        if ($blown_name[0] === 'COM4' or $blown_name[0] === 'COM5' or $blown_name[0] === 'COM6') return null;
        if ($blown_name[0] === 'COM7' or $blown_name[0] === 'COM8' or $blown_name[0] === 'COM9') return null;
        if ($blown_name[0] === 'LPT1' or $blown_name[0] === 'LPT2' or $blown_name[0] === 'LPT3') return null;
        if ($blown_name[0] === 'LPT4' or $blown_name[0] === 'LPT5' or $blown_name[0] === 'LPT6') return null;
        if ($blown_name[0] === 'LPT7' or $blown_name[0] === 'LPT8' or $blown_name[0] === 'LPT9') return null;
        for ($i = 0; $i < strlen($str_file_name); $i++) {
            if (ord($str_file_name[$i]) < 32) return null;
        }
        return $str_file_name;
    }

    function validateFileContents(mixed $file_contents, bool $null_allowed = false): string|null|false
    {
        global $max_file_size;

        if (isset($file_contents) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        $str_file_contents = $this->validateString($file_contents, min_chars: 0);
        if ($str_file_contents === null) return null;
        if (mb_strlen($str_file_contents, "8bit") > $max_file_size) return null;
        return $str_file_contents;
    }
}

function handleReturn(ControllerRet $ret_val): void
{
    switch ($ret_val) {
        case ControllerRet::success:
            http_response_code(200);
            break;
        case ControllerRet::success_created:
            http_response_code(201);
            break;
        case ControllerRet::success_no_content:
            http_response_code(204);
            break;
        case ControllerRet::bad_request:
            http_response_code(400);
            echo "Bad request";
            break;
        case ControllerRet::already_exists:
            http_response_code(400);
            echo "Already exists";
            break;
        case ControllerRet::unauthorised:
            http_response_code(403);
            echo "Unauthorised";
            break;
        case ControllerRet::unexpected_error:
            http_response_code(500);
            echo "Unexpected error";
            break;
        default:
            http_response_code(500);
            echo "You shouldn't be seeing this, congrats";
            break;
    }
}

enum ControllerRet
{
    case success;
    case success_created;
    case success_no_content;
    case bad_request;
    case unauthorised;
    case already_exists;
    case not_found;
    case unexpected_error;
}

function file_force_contents(string $dir, string $contents): int|false
{
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = implode("/", $parts);
    if (is_dir($dir) === false) mkdir($dir, recursive: true);
    try {
        return file_put_contents("$dir/$file", $contents, LOCK_EX);
    } catch (ValueError) {
        return false;
    }
}

/**
 * Returns true on success and null on failure
 */
function rmdirRecursive(string $dir): bool
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        if ($objects === false) return false;
        foreach ($objects as $object) {
            if ($object !== "." && $object !== "..") {
                if (is_dir($dir . '/' . $object) && !is_link($dir . '/' . $object)) {
                    if (rmdirRecursive($dir . '/' . $object) === false) return false;
                } else {
                    if (unlink($dir . '/' . $object) === false) return false;
                }
            }
        }
        if (rmdir($dir) === false) return false;
    }
    return true;
}
