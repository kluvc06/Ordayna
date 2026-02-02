<?php

declare(strict_types=1);
// TODO: Rewrite getAllUsers
// TODO: Use argon2id instead of bcrypt

static $is_test_server = php_sapi_name() === "cli-server";
/** 20 mebibytes */
static $max_file_size = 1024 * 1024 * 20;

use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;

include "db.php";
include "jwt.php";

class UserController
{
    private $jwt;

    function __construct()
    {
        $this->jwt = new JWT();
    }

    /**
     * Returns null on failure and users in json format on success
     */
    public function getAllUsers(): mixed
    {
        if (!isset($_GET["intezmeny_id"]) or intval($_GET["intezmeny_id"]) === 0) {
            return null;
        }

        $intezmeny_id = intval($_GET["intezmeny_id"]);

        $db = new DB();
        $res = $db->getAllUsers($intezmeny_id);

        header('Content-Type: application/json');
        return json_encode($res->fetch_all());
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

        if (!$db->userExistsEmail($email)) return ControllerRet::unauthorised;

        $user_pass = $db->getUserPassViaEmail($email);
        if (!$user_pass or !password_verify($pass, $user_pass)) return ControllerRet::unauthorised;

        $user_id = $db->getUserIdViaEmail($email);
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
        if (is_a($token, "ControllerRet")) return $token;
        $new_token = $this->jwt->createRefreshToken($token->claims()->get("uid"));

        // Expires after 15 days
        $db->newInvalidRefreshToken($token->claims()->get(RegisteredClaims::ID), '15 0:0:0');

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
        if (is_a($token, "ControllerRet")) return $token;
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

        if ($db->userExistsEmail($email)) return ControllerRet::user_already_exists;

        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
        if ($db->createUser($disp_name, $email, $phone_number, $pass_hash) === false) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function deleteUser(): ControllerRet
    {
        $db = new DB();
        
        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet")) return $token;

        // TODO: delete intezmeny's whose sole owner is this user
        if ($db->deleteUserViaId($token->claims()->get("uid")) === false) return ControllerRet::unexpected_error;

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
        if (is_a($token, "ControllerRet")) return $token;

        if ($db->changeDisplayNameViaId($token->claims()->get("uid"), $new_disp_name) === false) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    public function changePhoneNumber(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $new_phone_number = $this->validatePhoneNumber(@$data->new_phone_number, false);
        if ($new_phone_number === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet")) return $token;

        if ($db->changePhoneNumberViaId($token->claims()->get("uid"), $data->new_phone_number) === false) return ControllerRet::unexpected_error;

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
        if (is_a($token, "ControllerRet")) return $token;

        if ($db->changePasswordHashViaId($token->claims()->get("uid"), password_hash($new_pass, PASSWORD_BCRYPT)) === false) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    public function createIntezmeny(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $intezmeny_name = $this->validateString(@$data->intezmeny_name, max_chars: 200);
        if ($intezmeny_name === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet")) return $token;

        if (!$db->createIntezmeny($intezmeny_name, $token->claims()->get("uid"))) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function deleteIntezmeny(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $intezmeny_id = $this->validateInteger(@$data->intezmeny_id);
        if ($intezmeny_id === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet")) return $token;

        if (!$db->userExistsViaId($token->claims()->get("uid"))) return ControllerRet::unauthorised;
        if (!$db->partOfIntezmeny($token->claims()->get("uid"), $intezmeny_id)) return ControllerRet::unauthorised;

        if (!$db->deleteIntezmeny($intezmeny_id)) return ControllerRet::unexpected_error;
        if (rmdirRecursive("user_data/intezmeny_$intezmeny_id") === false) return ControllerRet::unexpected_error;

        return ControllerRet::success_no_content;
    }

    public function getIntezmenys(): ControllerRet
    {
        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet")) return $token;

        $ret = $db->getIntezmenys($token->claims()->get("uid"));
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function createClass(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $headcount = $this->validateInteger(@$data->headcount, 5);
        if ($headcount === null) return ControllerRet::bad_request;
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($db->classExistsViaName($intezmeny_id, $name)) return ControllerRet::bad_request;

        $ret = $db->createClass($intezmeny_id, $name, $headcount);
        if (!$ret) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createLesson(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($db->lessonExistsViaName($intezmeny_id, $name)) return ControllerRet::bad_request;

        $ret = $db->createLesson($intezmeny_id, $name);
        if (!$ret) return ControllerRet::unexpected_error;

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
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($class_id !== null and !$db->classExists($intezmeny_id, $class_id)) return ControllerRet::bad_request;
        if ($db->groupExistsViaName($intezmeny_id, $name)) return ControllerRet::bad_request;

        $ret = $db->createGroup($intezmeny_id, $name, $headcount, $class_id);
        if (!$ret) return ControllerRet::unexpected_error;

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
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($db->roomExistsViaName($intezmeny_id, $name)) return ControllerRet::bad_request;

        $ret = $db->createRoom($intezmeny_id, $name, $type, $space);
        if (!$ret) return ControllerRet::unexpected_error;

        return ControllerRet::success_created;
    }

    public function createTeacher(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = $this->validateString(@$data->name, max_chars: 200);
        if ($name === null) return ControllerRet::bad_request;
        $job = $this->validateString(@$data->job, max_chars: 200);
        if ($job === null) return ControllerRet::bad_request;
        $email = $this->validateEmail(@$data->email, true);
        if ($email === null) return ControllerRet::bad_request;
        if ($email === false) $email = null;
        $phone_number = $this->validatePhoneNumber(@$data->phone_number, true);
        if ($phone_number === null) return ControllerRet::bad_request;
        if ($phone_number === false) $phone_number = null;
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->createTeacher($intezmeny_id, $name, $job, $email, $phone_number);
        if (!$ret) return ControllerRet::unexpected_error;

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
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->createTimetableElement($intezmeny_id, $duration->format("H:i:s"), $day, $from->format("Y-m-d"), $until->format("Y-m-d"));
        if (!$ret) return ControllerRet::unexpected_error;

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
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($lesson_id !== null and !$db->lessonExists($intezmeny_id, $lesson_id)) return ControllerRet::bad_request;
        if ($teacher_id !== null and !$db->teacherExists($intezmeny_id, $teacher_id)) return ControllerRet::bad_request;

        $ret = $db->createHomework($intezmeny_id, $due !== null ? $due->format("Y-m-d h:i:s") : null, $lesson_id, $teacher_id);
        if (!$ret) return ControllerRet::unexpected_error;

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
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if (!$db->homeworkExists($intezmeny_id, $homework_id)) return ControllerRet::unauthorised;

        $attachment_id = $db->createAttachment($intezmeny_id, $homework_id, $file_name);
        if ($attachment_id === false) return ControllerRet::unexpected_error;
        if (file_force_contents("user_data/intezmeny_$intezmeny_id/" . $file_name . "_$attachment_id", $file_contents) === false) {
            // TODO: delete attachment from database
            return ControllerRet::unexpected_error;
        }

        return ControllerRet::success_created;
    }

    public function getClasses(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getClasses($intezmeny_id);
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getLessons(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getLessons($intezmeny_id);
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getGroups(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getGroups($intezmeny_id);
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getRooms(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getRooms($intezmeny_id);
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getTeachers(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getTeachers($intezmeny_id);
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret);

        return ControllerRet::success;
    }

    public function getTimetable(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getTimetable($intezmeny_id);
        if (!$ret) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret->fetch_all());

        return ControllerRet::success;
    }

    public function getHomeworks(): ControllerRet
    {
        $ret = $this->validateGetIntezmenyData(json_decode(file_get_contents("php://input")));
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getHomeworks($intezmeny_id);
        if ($ret === false) return ControllerRet::unexpected_error;

        header('Content-Type: application/json');
        echo json_encode($ret);

        return ControllerRet::success;
    }

    public function getAttachment(): ControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        $attachment_id = $this->validateInteger(@$data->attachment_id);
        if ($attachment_id === null) return ControllerRet::bad_request;
        $ret = $this->validateGetIntezmenyData($data);
        if (is_a($ret, "ControllerRet")) return $ret;
        list($db, $intezmeny_id) = $ret;

        if ($db->attachmentExists($intezmeny_id, $attachment_id) === false) return ControllerRet::unauthorised;

        $attachment_name = $db->getAttachmentName($intezmeny_id, $attachment_id);
        if ($attachment_id === false) return ControllerRet::unexpected_error;

        $file_contents = file_get_contents("user_data/intezmeny_$intezmeny_id/" . $attachment_name . "_" . $attachment_id);
        if ($file_contents === false) return ControllerRet::unexpected_error;

        header('Content-Type: application/octet-stream');
        echo $file_contents;

        return ControllerRet::success;
    }

    /**
     * Returns the database connection and the intezmeny's id
     */
    private function validateGetIntezmenyData(mixed $data): ControllerRet|array
    {
        $intezmeny_id = $this->validateInteger(@$data->intezmeny_id);
        if ($intezmeny_id === null) return ControllerRet::bad_request;

        $db = new DB();

        $token = $this->validateAccessToken($db);
        if (is_a($token, "ControllerRet")) return $token;

        if (!$db->partOfIntezmeny($token->claims()->get("uid"), $intezmeny_id)) return ControllerRet::unauthorised;

        return array($db, $intezmeny_id);
    }

    private function validateRefreshToken(DB $db): ControllerRet|UnencryptedToken
    {
        if (isset($_COOKIE["RefreshToken"]) === false or is_string($_COOKIE["RefreshToken"]) === false) return ControllerRet::bad_request;

        $token = $this->jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === null) return ControllerRet::bad_request;

        $invalid_ids = $db->getRevokedRefreshTokens();
        if ($this->jwt->validateRefreshToken($token, $invalid_ids) === false) return ControllerRet::unauthorised;

        if ($db->userExistsViaId($token->claims()->get("uid")) === false) return ControllerRet::unauthorised;

        return $token;
    }

    private function validateAccessToken(DB $db): ControllerRet|UnencryptedToken
    {
        if (isset($_COOKIE["AccessToken"]) === false or is_string($_COOKIE["AccessToken"]) === false) return ControllerRet::bad_request;

        $token = $this->jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) return ControllerRet::bad_request;

        if ($this->jwt->validateAccessToken($token) === false) return ControllerRet::unauthorised;

        if ($db->userExistsViaId($token->claims()->get("uid")) === false) return ControllerRet::unauthorised;

        return $token;
    }

    // This function handles the case where $number is undefined
    // It's expected that $number is passed in with the "@" stfu operator
    // If null is allowed and $number is null then returns false
    private function validateInteger(mixed $number, int|null $max_digits = null, bool $null_allowed = false): int|null|false
    {
        if (!isset($number)) {
            if ($null_allowed) {
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
            if ($null_allowed) {
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
        if (!isset($email)) {
            if ($null_allowed) {
                return false;
            } else {
                return null;
            }
        }
        if (!is_string($email) or !preg_match('/^[^@]+[@]+[^@]+$/', $email)) return null;
        return (string) $email;
    }

    // This function handles the case where $phone_number is undefined
    // It's expected that $phone_number is passed in with the "@" stfu operator
    // If null is allowed and $phone_number is null then returns false
    private function validatePhoneNumber(mixed $phone_number, bool $null_allowed = false): string|null|false
    {
        if (!isset($phone_number)) {
            if ($null_allowed) {
                return false;
            } else {
                return null;
            }
        }
        if ($this->validateString($phone_number, max_chars: 15, null_allowed: false) === null) return null;
        if (!is_numeric($phone_number)) return null;
        return (string) $phone_number;
    }


    // This function handles the case where $time is undefined
    // It's expected that $time is passed in with the "@" stfu operator
    // If null is allowed and $time is null then returns false
    private function validateTime(mixed $time, bool $date_allowed = false, $time_allowed = false, bool $null_allowed = false): DateTimeImmutable|null|false
    {
        if (!isset($time)) {
            if ($null_allowed) {
                return false;
            } else {
                return null;
            }
        }
        $str_time = $this->validateString(@$time);
        if ($str_time === null) return null;
        if ($date_allowed and $time_allowed) {
            try {
                $ret = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $str_time);
            } catch (ValueError $e) {
                return null;
            }
        } else if ($date_allowed and !$time_allowed) {
            try {
                $ret = DateTimeImmutable::createFromFormat("Y-m-d", $str_time);
            } catch (ValueError $e) {
                return null;
            }
        } else if (!$date_allowed and $time_allowed) {
            try {
                $ret = DateTimeImmutable::createFromFormat("H:i:s", $str_time);
            } catch (ValueError $e) {
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
        if (!isset($file_name)) {
            if ($null_allowed) {
                return false;
            } else {
                return null;
            }
        }
        $str_file_name = $this->validateString($file_name, max_chars: 200);
        if ($str_file_name === null) return null;
        if ($str_file_name === "." or $str_file_name === "..") return null;
        if (preg_match('^[^<>:?"*|\/\\]+$', $str_file_name)) return null;
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

        if (!isset($file_contents)) {
            if ($null_allowed) {
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
        case ControllerRet::user_already_exists:
            http_response_code(400);
            echo "User already exists";
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
    case user_already_exists;
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
    } catch (ValueError $e) {
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
