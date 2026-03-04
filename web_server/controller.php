<?php

declare(strict_types=1);

namespace Controller;

require_once "db.php";
require_once "jwt.php";

use DB\DB;
use JWT\JWT;
use DateTimeImmutable;
use ValueError;

use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;

/** 20 mebibytes */
static $max_file_size = 1024 * 1024 * 20;

class Controller
{
    public static function getRefreshToken(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $email = Controller::validateEmail(@$data->email);
        if ($email === null) return handleReturn(ControllerRet::bad_request);
        $pass = Controller::validateString(@$data->pass, min_chars: 12, max_chars: 500);
        if ($pass === null) return handleReturn(ControllerRet::bad_request);

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $ret = $db->userExistsViaEmail($email);
        if ($ret === false) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        $pass_hash = $db->getUserPassHashViaEmail($email);
        if ($pass_hash === null) return handleReturn(ControllerRet::unexpected_error);
        if (password_verify($pass, $pass_hash) === false) return handleReturn(ControllerRet::unauthorised);

        $user_id = $db->getUserIdViaEmail($email);
        if ($user_id === null) return handleReturn(ControllerRet::unexpected_error);

        if (password_needs_rehash($pass_hash, PASSWORD_DEFAULT)) {
            $new_pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            if ($new_pass_hash === false) return handleReturn(ControllerRet::unexpected_error);
            if ($new_pass_hash === null) return handleReturn(ControllerRet::unexpected_error);
            if ($db->changePasswordHash($user_id, $new_pass_hash) === null) return handleReturn(ControllerRet::unexpected_error);
        }

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $refresh_token = $jwt->createRefreshToken($user_id);

        if ($db->newToken(
            $refresh_token->claims()->get("uid"),
            $refresh_token->claims()->get(RegisteredClaims::ID),
            $refresh_token->claims()->get(RegisteredClaims::EXPIRATION_TIME)
        ) === null) return handleReturn(ControllerRet::unexpected_error);

        $age = $refresh_token->claims()->get(RegisteredClaims::EXPIRATION_TIME)->getTimestamp();
        $age -= $refresh_token->claims()->get(RegisteredClaims::ISSUED_AT)->getTimestamp();
        header(
            'Set-Cookie: RefreshToken=' . $refresh_token->toString()
                . '; Max-Age=' . $age
                . '; Path=/token/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );

        return handleReturn(ControllerRet::success);
    }

    public static function refreshRefreshToken(): null
    {
        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateRefreshToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);
        $new_token = $jwt->createRefreshToken($token->claims()->get("uid"));

        if ($db->newToken(
            $new_token->claims()->get("uid"),
            $new_token->claims()->get(RegisteredClaims::ID),
            $new_token->claims()->get(RegisteredClaims::EXPIRATION_TIME)
        ) === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->revokeToken($token->claims()->get("uid"), $token->claims()->get(RegisteredClaims::ID)) === null) {
            return handleReturn(ControllerRet::unexpected_error);
        }

        $age = $new_token->claims()->get(RegisteredClaims::EXPIRATION_TIME)->getTimestamp();
        $age -= $new_token->claims()->get(RegisteredClaims::ISSUED_AT)->getTimestamp();
        header(
            'Set-Cookie: RefreshToken=' . $new_token->toString()
                . '; Max-Age=' . $age
                . '; Path=/token/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );

        return handleReturn(ControllerRet::success);
    }

    public static function getAccessToken(): null
    {
        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateRefreshToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);
        $new_access_token = $jwt->createAccessToken($token->claims()->get("uid"));

        if ($db->newToken(
            $new_access_token->claims()->get("uid"),
            $new_access_token->claims()->get(RegisteredClaims::ID),
            $new_access_token->claims()->get(RegisteredClaims::EXPIRATION_TIME)
        ) === null) return handleReturn(ControllerRet::unexpected_error);
        $age = $new_access_token->claims()->get(RegisteredClaims::EXPIRATION_TIME)->getTimestamp();
        $age -= $new_access_token->claims()->get(RegisteredClaims::ISSUED_AT)->getTimestamp();
        header(
            'Set-Cookie: AccessToken=' . $new_access_token->toString()
                . '; Max-Age=' . $age
                . '; Path=/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );

        return handleReturn(ControllerRet::success);
    }

    public static function createUser(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $disp_name = Controller::validateString(@$data->disp_name, max_chars: 200);
        if ($disp_name === null) return handleReturn(ControllerRet::bad_request);
        $email = Controller::validateEmail(@$data->email);
        if ($email === null) return handleReturn(ControllerRet::bad_request);
        $pass = Controller::validateString(@$data->pass, min_chars: 12, max_chars: 500);
        if ($pass === null) return handleReturn(ControllerRet::bad_request);
        $phone_number = Controller::validatePhoneNumber(@$data->phone_number, true);
        if ($phone_number === null) return handleReturn(ControllerRet::bad_request);
        if ($phone_number === false) $phone_number = null;

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $ret = $db->userExistsViaEmail($email);
        if ($ret === true) return handleReturn(ControllerRet::already_exists);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
        if ($pass_hash === false) return handleReturn(ControllerRet::unexpected_error);
        if ($pass_hash === null) return handleReturn(ControllerRet::unexpected_error);
        if ($db->createUser($disp_name, $email, $phone_number, $pass_hash) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function deleteUser(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $pass = Controller::validateString(@$data->pass, min_chars: 12, max_chars: 500);
        if ($pass === null) return handleReturn(ControllerRet::bad_request);
        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        $pass_hash = $db->getUserPassHash($token->claims()->get("uid"));
        if ($pass_hash === null) return handleReturn(ControllerRet::unexpected_error);
        if (password_verify($pass, $pass_hash) === false) return handleReturn(ControllerRet::unauthorised);

        if ($db->deleteUserViaId($token->claims()->get("uid")) === null) return handleReturn(ControllerRet::unexpected_error);
        if ($db->deleteOrphanedIntezmenys() === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->revokeAllTokens($token->claims()->get("uid")) === null) return handleReturn(ControllerRet::unexpected_error);

        // Unset token cookies
        header(
            'Set-Cookie: RefreshToken='
                . '; Max-Age=0'
                . '; Path=/token/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );
        header(
            'Set-Cookie: AccessToken='
                . '; Max-Age=0'
                . '; Path=/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function changeDisplayName(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $new_disp_name = Controller::validateString(@$data->new_disp_name, max_chars: 200);
        if ($new_disp_name === null) return handleReturn(ControllerRet::bad_request);

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        if ($db->changeDisplayName($token->claims()->get("uid"), $new_disp_name) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function changePhoneNumber(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $new_phone_number = Controller::validatePhoneNumber(@$data->new_phone_number, false);
        if ($new_phone_number === null) return handleReturn(ControllerRet::bad_request);

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        if ($db->changePhoneNumber($token->claims()->get("uid"), $data->new_phone_number) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function changePassword(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $pass = Controller::validateString(@$data->pass, min_chars: 12, max_chars: 500);
        if ($pass === null) return handleReturn(ControllerRet::bad_request);
        $new_pass = Controller::validateString(@$data->new_pass, min_chars: 12, max_chars: 500);
        if ($new_pass === null) return handleReturn(ControllerRet::bad_request);

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        $pass_hash = $db->getUserPassHash($token->claims()->get("uid"));
        if ($pass_hash === null) return handleReturn(ControllerRet::unexpected_error);
        if (password_verify($pass, $pass_hash) === false) return handleReturn(ControllerRet::unauthorised);

        $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        if ($new_pass_hash === false) return handleReturn(ControllerRet::unexpected_error);
        if ($new_pass_hash === null) return handleReturn(ControllerRet::unexpected_error);
        if ($db->changePasswordHash($token->claims()->get("uid"), $new_pass_hash) === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->revokeAllTokens($token->claims()->get("uid")) === null) return handleReturn(ControllerRet::unexpected_error);

        // Unset token cookies
        header(
            'Set-Cookie: RefreshToken='
                . '; Max-Age=0'
                . '; Path=/token/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );
        header(
            'Set-Cookie: AccessToken='
                . '; Max-Age=0'
                . '; Path=/'
                . (php_sapi_name() === "cli-server" ? '' : '; Secure')
                . '; SameSite=Strict'
                . '; HttpOnly'
                . '; Partitioned'
            , false
        );

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function createIntezmeny(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $intezmeny_name = Controller::validateString(@$data->intezmeny_name, max_chars: 200);
        if ($intezmeny_name === null) return handleReturn(ControllerRet::bad_request);

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        if ($db->createIntezmeny($intezmeny_name, $token->claims()->get("uid")) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function deleteIntezmeny(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $intezmeny_id = Controller::validateInteger(@$data->intezmeny_id);
        if ($intezmeny_id === null) return handleReturn(ControllerRet::bad_request);

        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        $ret = $db->userExists($token->claims()->get("uid"));
        if ($ret === false) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        $ret = $db->partOfIntezmeny($intezmeny_id, $token->claims()->get("uid"), true);
        if ($ret === false) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        $ret = $db->isAdmin($intezmeny_id, $token->claims()->get("uid"));
        if ($ret === false) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteIntezmeny($intezmeny_id) === null) return handleReturn(ControllerRet::unexpected_error);
        if (rmdirRecursive("user_data/intezmeny_$intezmeny_id") === false) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function getIntezmenys(): null
    {
        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        $ret = $db->getIntezmenys($token->claims()->get("uid"));
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getProfile(): null
    {
        $db = DB::init();
        if ($db === null) return handleReturn(ControllerRet::unexpected_error);

        $jwt = JWT::init();
        if ($jwt === false) return handleReturn(ControllerRet::unexpected_error);
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return handleReturn($token);

        $ret = $db->getProfile($token->claims()->get("uid"));
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function inviteToIntezmeny(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $email = Controller::validateEmail(@$data->email);
        if ($email === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, false);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->userExistsViaEmail($email);
        if ($ret === false) return handleReturn(ControllerRet::not_found);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        $invitee_uid = $db->getUserIdViaEmail($email);
        if ($invitee_uid === null) return handleReturn(ControllerRet::unexpected_error);
        $ret = $db->partOfIntezmeny($intezmeny_id, $invitee_uid, false);
        if ($ret === true) return handleReturn(ControllerRet::already_exists);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->inviteUser($intezmeny_id, $invitee_uid) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success);
    }

    public static function acceptInviteToIntezmeny(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $ret = Controller::validateIntezmenyData($data, false);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id, $uid) = $ret;

        $ret = $db->isInviteAccepted($intezmeny_id, $uid);
        if ($ret === true) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->acceptInvite($intezmeny_id, $uid) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success);
    }

    public static function createClass(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $headcount = Controller::validateInteger(@$data->headcount, 5);
        if ($headcount === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->classExistsViaName($intezmeny_id, $name);
        if ($ret === true) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        $ret = $db->groupExistsViaName($intezmeny_id, $name);
        if ($ret === true) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->createClass($intezmeny_id, $name, $headcount) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createLesson(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->lessonExistsViaName($intezmeny_id, $name);
        if ($ret === true) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->createLesson($intezmeny_id, $name) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createGroup(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $headcount = Controller::validateInteger(@$data->headcount, 5);
        if ($headcount === null) return handleReturn(ControllerRet::bad_request);
        $class_id = Controller::validateInteger(@$data->class_id, null_allowed: true);
        if ($class_id === null) return handleReturn(ControllerRet::bad_request);
        if ($class_id === false) $class_id = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        if ($class_id !== null) {
            $ret = $db->classExists($intezmeny_id, $class_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        $ret = $db->groupExistsViaName($intezmeny_id, $name);
        if ($ret === true) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->createGroup($intezmeny_id, $name, $headcount, $class_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createRoom(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $type = Controller::validateString(@$data->type, max_chars: 200, null_allowed: true);
        if ($type === null) return handleReturn(ControllerRet::bad_request);
        if ($type === false) $type = null;
        $space = Controller::validateInteger(@$data->space, 5);
        if ($space === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->roomExistsViaName($intezmeny_id, $name);
        if ($ret === true) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->createRoom($intezmeny_id, $name, $type, $space) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createTeacher(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $job = Controller::validateString(@$data->job, max_chars: 200);
        if ($job === null) return handleReturn(ControllerRet::bad_request);
        $teacher_uid = Controller::validateInteger(@$data->teacher_uid, null_allowed: true);
        if ($teacher_uid === null) return handleReturn(ControllerRet::bad_request);
        if ($teacher_uid === false) $teacher_uid = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        if ($teacher_uid !== null) {
            $ret = $db->partOfIntezmeny($intezmeny_id, $teacher_uid, true);
            if ($ret === false) return handleReturn(ControllerRet::unauthorised);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
            $ret = $db->isTeacher($intezmeny_id, $teacher_uid);
            if ($ret === true) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }

        if ($db->createTeacher($intezmeny_id, $name, $job, $teacher_uid) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createTimetableElement(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $duration = Controller::validateTime(@$data->duration, time_allowed: true);
        if ($duration === null) return handleReturn(ControllerRet::bad_request);
        $day = Controller::validateInteger(@$data->day);
        if ($day === null) return handleReturn(ControllerRet::bad_request);
        if ($day > 6 or $day < 0) return handleReturn(ControllerRet::bad_request);
        $from = Controller::validateTime(@$data->from, date_allowed: true);
        if ($from === null) return handleReturn(ControllerRet::bad_request);
        $until = Controller::validateTime(@$data->until, date_allowed: true);
        if ($until === null) return handleReturn(ControllerRet::bad_request);
        if ($from->getTimestamp() > $until->getTimestamp()) return handleReturn(ControllerRet::bad_request);
        $group_id = Controller::validateInteger(@$data->group_id, null_allowed: true);
        if ($group_id === null) return handleReturn(ControllerRet::bad_request);
        if ($group_id === false) $group_id = null;
        $lesson_id = Controller::validateInteger(@$data->lesson_id, null_allowed: true);
        if ($lesson_id === null) return handleReturn(ControllerRet::bad_request);
        if ($lesson_id === false) $lesson_id = null;
        $teacher_id = Controller::validateInteger(@$data->teacher_id, null_allowed: true);
        if ($teacher_id === null) return handleReturn(ControllerRet::bad_request);
        if ($teacher_id === false) $teacher_id = null;
        $room_id = Controller::validateInteger(@$data->room_id, null_allowed: true);
        if ($room_id === null) return handleReturn(ControllerRet::bad_request);
        if ($room_id === false) $room_id = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        if ($group_id !== null) {
            $ret = $db->groupExists($intezmeny_id, $group_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($lesson_id !== null) {
            $ret = $db->lessonExists($intezmeny_id, $lesson_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($teacher_id !== null) {
            $ret = $db->teacherExists($intezmeny_id, $teacher_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($room_id !== null) {
            $ret = $db->roomExists($intezmeny_id, $room_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }

        if ($db->createTimetableElement(
            $intezmeny_id,
            $duration->format("H:i:s"),
            $day,
            $from->format("Y-m-d"),
            $until->format("Y-m-d"),
            $group_id,
            $lesson_id,
            $teacher_id,
            $room_id
        ) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createHomework(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $due = Controller::validateTime(@$data->due, date_allowed: true, time_allowed: true, null_allowed: true);
        if ($due === null) return handleReturn(ControllerRet::bad_request);
        if ($due === false) $due = null;
        $lesson_id = Controller::validateInteger(@$data->lesson_id, null_allowed: true);
        if ($lesson_id === null) return handleReturn(ControllerRet::bad_request);
        if ($lesson_id === false) $lesson_id = null;
        $teacher_id = Controller::validateInteger(@$data->teacher_id, null_allowed: true);
        if ($teacher_id === null) return handleReturn(ControllerRet::bad_request);
        if ($teacher_id === false) $teacher_id = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        if ($lesson_id !== null) {
            $ret = $db->lessonExists($intezmeny_id, $lesson_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($teacher_id !== null) {
            $ret = $db->teacherExists($intezmeny_id, $teacher_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }

        if ($db->createHomework($intezmeny_id, $due !== null ? $due->format("Y-m-d h:i:s") : null, $lesson_id, $teacher_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_created);
    }

    public static function createAttachment(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $homework_id = Controller::validateInteger(@$data->homework_id);
        if ($homework_id === null) return handleReturn(ControllerRet::bad_request);
        $file_name = Controller::validateFileName(@$data->file_name);
        if ($file_name === null) return handleReturn(ControllerRet::bad_request);
        $file_contents = Controller::validateFileContents(@$data->file_contents);
        if ($file_contents === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->homeworkExists($intezmeny_id, $homework_id);
        if ($ret === false) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        $attachment_id = $db->createAttachment($intezmeny_id, $homework_id, $file_name);
        if ($attachment_id === null) return handleReturn(ControllerRet::unexpected_error);
        if (file_force_contents("user_data/intezmeny_$intezmeny_id/" . $file_name . "_$attachment_id", $file_contents) === false) {
            if ($db->deleteAttachment($intezmeny_id, $attachment_id) === null) return handleReturn(ControllerRet::unexpected_error);
            return handleReturn(ControllerRet::unexpected_error);
        }

        return handleReturn(ControllerRet::success_created);
    }

    public static function deleteClass(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $class_id = Controller::validateInteger(@$data->class_id);
        if ($class_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->classExists($intezmeny_id, $class_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteClass($intezmeny_id, $class_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteLesson(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $lesson_id = Controller::validateInteger(@$data->lesson_id);
        if ($lesson_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->lessonExists($intezmeny_id, $lesson_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteLesson($intezmeny_id, $lesson_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteGroup(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $group_id = Controller::validateInteger(@$data->group_id);
        if ($group_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->groupExists($intezmeny_id, $group_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteGroup($intezmeny_id, $group_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteRoom(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $room_id = Controller::validateInteger(@$data->room_id);
        if ($room_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->roomExists($intezmeny_id, $room_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteRoom($intezmeny_id, $room_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteTeacher(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $teacher_id = Controller::validateInteger(@$data->teacher_id);
        if ($teacher_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->teacherExists($intezmeny_id, $teacher_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteTeacher($intezmeny_id, $teacher_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteTimetableElement(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $timetable_element_id = Controller::validateInteger(@$data->timetable_element_id);
        if ($timetable_element_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->timetableElementExists($intezmeny_id, $timetable_element_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->deleteTimetableElement($intezmeny_id, $timetable_element_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteHomework(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $homework_id = Controller::validateInteger(@$data->homework_id);
        if ($homework_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->homeworkExists($intezmeny_id, $homework_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        $attachments = $db->getHomeworkAttachments($intezmeny_id, $homework_id);
        if ($attachments === null) return handleReturn(ControllerRet::unexpected_error);

        for ($i = 0; $i < count($attachments); $i++) {
            if (unlink("user_data/intezmeny_$intezmeny_id/" . $attachments[$i][1] . "_" . $attachments[$i][0]) === false) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($db->deleteHomework($intezmeny_id, $homework_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function deleteAttachment(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $attachment_id = Controller::validateInteger(@$data->attachment_id);
        if ($attachment_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->attachmentExists($intezmeny_id, $attachment_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        $attachment_name = $db->getAttachmentName($intezmeny_id, $attachment_id);
        if ($attachment_name === null) return handleReturn(ControllerRet::unexpected_error);

        if (unlink("user_data/intezmeny_$intezmeny_id/" . $attachment_name . "_" . $attachment_id) === false) return handleReturn(ControllerRet::unexpected_error);
        if ($db->deleteAttachment($intezmeny_id, $attachment_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateClass(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $class_id = Controller::validateInteger(@$data->class_id);
        if ($class_id === null) return handleReturn(ControllerRet::bad_request);
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->classExists($intezmeny_id, $class_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->updateClass($intezmeny_id, $class_id, $name) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateLesson(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $lesson_id = Controller::validateInteger(@$data->lesson_id);
        if ($lesson_id === null) return handleReturn(ControllerRet::bad_request);
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->lessonExists($intezmeny_id, $lesson_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->updateLesson($intezmeny_id, $lesson_id, $name) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateGroup(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $group_id = Controller::validateInteger(@$data->group_id);
        if ($group_id === null) return handleReturn(ControllerRet::bad_request);
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $headcount = Controller::validateInteger(@$data->headcount, 5);
        if ($headcount === null) return handleReturn(ControllerRet::bad_request);
        $class_id = Controller::validateInteger(@$data->class_id, null_allowed: true);
        if ($class_id === null) return handleReturn(ControllerRet::bad_request);
        if ($class_id === false) $class_id = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        if ($class_id !== null) {
            $ret = $db->classExists($intezmeny_id, $class_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        $ret = $db->groupExists($intezmeny_id, $group_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->updateGroup($intezmeny_id, $group_id, $name, $headcount, $class_id) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateRoom(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $room_id = Controller::validateInteger(@$data->room_id);
        if ($room_id === null) return handleReturn(ControllerRet::bad_request);
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $type = Controller::validateString(@$data->type, max_chars: 200, null_allowed: true);
        if ($type === null) return handleReturn(ControllerRet::bad_request);
        if ($type === false) $type = null;
        $space = Controller::validateInteger(@$data->space, 5);
        if ($space === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->roomExists($intezmeny_id, $room_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($db->updateRoom($intezmeny_id, $room_id, $name, $type, $space) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateTeacher(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $teacher_id = Controller::validateInteger(@$data->teacher_id);
        if ($teacher_id === null) return handleReturn(ControllerRet::bad_request);
        $name = Controller::validateString(@$data->name, max_chars: 200);
        if ($name === null) return handleReturn(ControllerRet::bad_request);
        $job = Controller::validateString(@$data->job, max_chars: 200);
        if ($job === null) return handleReturn(ControllerRet::bad_request);
        $teacher_uid = Controller::validateInteger(@$data->teacher_uid, null_allowed: true);
        if ($teacher_uid === null) return handleReturn(ControllerRet::bad_request);
        if ($teacher_uid === false) $teacher_uid = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->teacherExists($intezmeny_id, $teacher_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        if ($teacher_uid !== null) {
            $ret = $db->partOfIntezmeny($intezmeny_id, $teacher_uid, true);
            if ($ret === false) return handleReturn(ControllerRet::unauthorised);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
            $ret = $db->isThisTeacher($intezmeny_id, $teacher_id, $teacher_uid);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
            if ($ret === false) {
                $ret = $db->isTeacher($intezmeny_id, $teacher_uid);
                if ($ret === true) return handleReturn(ControllerRet::bad_request);
                if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
            }
        }

        if ($db->updateTeacher($intezmeny_id, $teacher_id, $name, $job, $teacher_uid) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateTimetableElement(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $element_id = Controller::validateInteger(@$data->element_id);
        if ($element_id === null) return handleReturn(ControllerRet::bad_request);
        $duration = Controller::validateTime(@$data->duration, time_allowed: true);
        if ($duration === null) return handleReturn(ControllerRet::bad_request);
        $day = Controller::validateInteger(@$data->day);
        if ($day === null) return handleReturn(ControllerRet::bad_request);
        if ($day > 6 or $day < 0) return handleReturn(ControllerRet::bad_request);
        $from = Controller::validateTime(@$data->from, date_allowed: true);
        if ($from === null) return handleReturn(ControllerRet::bad_request);
        $until = Controller::validateTime(@$data->until, date_allowed: true);
        if ($until === null) return handleReturn(ControllerRet::bad_request);
        if ($from->getTimestamp() > $until->getTimestamp()) return handleReturn(ControllerRet::bad_request);
        $group_id = Controller::validateInteger(@$data->group_id, null_allowed: true);
        if ($group_id === null) return handleReturn(ControllerRet::bad_request);
        if ($group_id === false) $group_id = null;
        $lesson_id = Controller::validateInteger(@$data->lesson_id, null_allowed: true);
        if ($lesson_id === null) return handleReturn(ControllerRet::bad_request);
        if ($lesson_id === false) $lesson_id = null;
        $teacher_id = Controller::validateInteger(@$data->teacher_id, null_allowed: true);
        if ($teacher_id === null) return handleReturn(ControllerRet::bad_request);
        if ($teacher_id === false) $teacher_id = null;
        $room_id = Controller::validateInteger(@$data->room_id, null_allowed: true);
        if ($room_id === null) return handleReturn(ControllerRet::bad_request);
        if ($room_id === false) $room_id = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->timetableElementExists($intezmeny_id, $element_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        if ($group_id !== null) {
            $ret = $db->groupExists($intezmeny_id, $group_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($lesson_id !== null) {
            $ret = $db->lessonExists($intezmeny_id, $lesson_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($teacher_id !== null) {
            $ret = $db->teacherExists($intezmeny_id, $teacher_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($room_id !== null) {
            $ret = $db->roomExists($intezmeny_id, $room_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }

        if ($db->updateTimetableElement(
            $intezmeny_id,
            $element_id,
            $duration->format("H:i:s"),
            $day,
            $from->format("Y-m-d"),
            $until->format("Y-m-d"),
            $group_id,
            $lesson_id,
            $teacher_id,
            $room_id
        ) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function updateHomework(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $homework_id = Controller::validateInteger(@$data->homework_id);
        if ($homework_id === null) return handleReturn(ControllerRet::bad_request);
        $due = Controller::validateTime(@$data->due, date_allowed: true, time_allowed: true, null_allowed: true);
        if ($due === null) return handleReturn(ControllerRet::bad_request);
        if ($due === false) $due = null;
        $lesson_id = Controller::validateInteger(@$data->lesson_id, null_allowed: true);
        if ($lesson_id === null) return handleReturn(ControllerRet::bad_request);
        if ($lesson_id === false) $lesson_id = null;
        $teacher_id = Controller::validateInteger(@$data->teacher_id, null_allowed: true);
        if ($teacher_id === null) return handleReturn(ControllerRet::bad_request);
        if ($teacher_id === false) $teacher_id = null;
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->homeworkExists($intezmeny_id, $homework_id);
        if ($ret === false) return handleReturn(ControllerRet::bad_request);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        if ($lesson_id !== null) {
            $ret = $db->lessonExists($intezmeny_id, $lesson_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }
        if ($teacher_id !== null) {
            $ret = $db->teacherExists($intezmeny_id, $teacher_id);
            if ($ret === false) return handleReturn(ControllerRet::bad_request);
            if ($ret === null) return handleReturn(ControllerRet::unexpected_error);
        }

        if ($db->updateHomework(
            $intezmeny_id,
            $homework_id,
            $due !== null ? $due->format("Y-m-d h:i:s") : null,
            $lesson_id,
            $teacher_id
        ) === null) return handleReturn(ControllerRet::unexpected_error);

        return handleReturn(ControllerRet::success_no_content);
    }

    public static function getClasses(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getClasses($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getLessons(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getLessons($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getGroups(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getGroups($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getRooms(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getRooms($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getTeachers(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getTeachers($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getTimetable(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getTimetable($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getHomeworks(): null
    {
        $ret = Controller::validateIntezmenyData(json_decode(file_get_contents("php://input")), true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->getHomeworks($intezmeny_id);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/json');
        echo json_encode($ret);

        return handleReturn(ControllerRet::success);
    }

    public static function getAttachment(): null
    {
        $data = json_decode(file_get_contents("php://input"));
        $attachment_id = Controller::validateInteger(@$data->attachment_id);
        if ($attachment_id === null) return handleReturn(ControllerRet::bad_request);
        $ret = Controller::validateIntezmenyData($data, true);
        if (is_a($ret, "Controller\ControllerRet") === true) return handleReturn($ret);
        list($db, $intezmeny_id) = $ret;

        $ret = $db->attachmentExists($intezmeny_id, $attachment_id);
        if ($ret === false) return handleReturn(ControllerRet::unauthorised);
        if ($ret === null) return handleReturn(ControllerRet::unexpected_error);

        $attachment_name = $db->getAttachmentName($intezmeny_id, $attachment_id);
        if ($attachment_id === null) return handleReturn(ControllerRet::unexpected_error);

        $file_contents = file_get_contents("user_data/intezmeny_$intezmeny_id/" . $attachment_name . "_" . $attachment_id);
        if ($file_contents === false) return handleReturn(ControllerRet::unexpected_error);

        header('Content-Type: application/octet-stream');
        echo $file_contents;

        return handleReturn(ControllerRet::success);
    }

    /**
     * Returns the database connection and the intezmeny's id and the uid
     */
    private static function validateIntezmenyData(mixed $data, bool $invite_must_be_accepted): ControllerRet|array
    {
        $intezmeny_id = Controller::validateInteger(@$data->intezmeny_id);
        if ($intezmeny_id === null) return ControllerRet::bad_request;

        $db = DB::init();
        if ($db === null) return ControllerRet::unexpected_error;

        $jwt = JWT::init();
        if ($jwt === false) ControllerRet::unexpected_error;
        $token = Controller::validateAccessToken($db, $jwt);
        if (is_a($token, "Controller\ControllerRet") === true) return $token;

        $ret = $db->partOfIntezmeny($intezmeny_id, $token->claims()->get("uid"), $invite_must_be_accepted);
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        return array($db, $intezmeny_id, $token->claims()->get("uid"));
    }

    private static function validateRefreshToken(DB $db, JWT $jwt): ControllerRet|UnencryptedToken
    {
        if (isset($_COOKIE["RefreshToken"]) === false or is_string($_COOKIE["RefreshToken"]) === false) return ControllerRet::bad_request;

        $token = $jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === null) return ControllerRet::bad_request;

        $ret = $db->isRevokedToken($token->claims()->get("uid"), $token->claims()->get(RegisteredClaims::ID));
        if ($ret === true) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;
        if ($jwt->validateRefreshToken($token) === false) return ControllerRet::unauthorised;

        $ret = $db->userExists($token->claims()->get("uid"));
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        return $token;
    }

    private static function validateAccessToken(DB $db, JWT $jwt): ControllerRet|UnencryptedToken
    {
        if (isset($_COOKIE["AccessToken"]) === false or is_string($_COOKIE["AccessToken"]) === false) return ControllerRet::bad_request;

        $token = $jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) return ControllerRet::bad_request;

        $ret = $db->isRevokedToken($token->claims()->get("uid"), $token->claims()->get(RegisteredClaims::ID));
        if ($ret === true) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;
        if ($jwt->validateAccessToken($token) === false) return ControllerRet::unauthorised;

        $ret = $db->userExists($token->claims()->get("uid"));
        if ($ret === false) return ControllerRet::unauthorised;
        if ($ret === null) return ControllerRet::unexpected_error;

        return $token;
    }

    // This static function handles the case where $number is undefined
    // It's expected that $number is passed in with the "@" stfu operator
    // If null is allowed and $number is null then returns false
    private static function validateInteger(mixed $number, int|null $max_digits = null, bool $null_allowed = false): int|null|false
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

    // This static function handles the case where $string is undefined
    // It's expected that $string is passed in with the "@" stfu operator
    // If null is allowed and $string is null then returns false
    private static function validateString(mixed $string, int $min_chars = 1, int|null $max_chars = null, bool $null_allowed = false): string|null|false
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

    // This static function handles the case where $email is undefined
    // It's expected that $email is passed in with the "@" stfu operator
    // If null is allowed and $email is null then returns false
    private static function validateEmail(mixed $email, bool $null_allowed = false): string|null|false
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

    // This static function handles the case where $phone_number is undefined
    // It's expected that $phone_number is passed in with the "@" stfu operator
    // If null is allowed and $phone_number is null then returns false
    private static function validatePhoneNumber(mixed $phone_number, bool $null_allowed = false): string|null|false
    {
        if (isset($phone_number) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        if (Controller::validateString($phone_number, max_chars: 15, null_allowed: false) === null) return null;
        if (is_numeric($phone_number) === false) return null;
        return (string) $phone_number;
    }


    // This static function handles the case where $time is undefined
    // It's expected that $time is passed in with the "@" stfu operator
    // If null is allowed and $time is null then returns false
    private static function validateTime(mixed $time, bool $date_allowed = false, $time_allowed = false, bool $null_allowed = false): DateTimeImmutable|null|false
    {
        if (isset($time) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        $str_time = Controller::validateString(@$time);
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
     * This static function handles the case where $time is undefined
     * It's expected that $time is passed in with the "@" stfu operator
     * If null is allowed and $time is null then returns false
     * https://gist.github.com/doctaphred/d01d05291546186941e1b7ddc02034d3
     */
    private static function validateFileName(mixed $file_name, bool $null_allowed = false): string|null|false
    {
        if (isset($file_name) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        $str_file_name = Controller::validateString($file_name, max_chars: 200);
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

    private static function validateFileContents(mixed $file_contents, bool $null_allowed = false): string|null|false
    {
        global $max_file_size;

        if (isset($file_contents) === false) {
            if ($null_allowed === true) {
                return false;
            } else {
                return null;
            }
        }
        $str_file_contents = Controller::validateString($file_contents, min_chars: 0);
        if ($str_file_contents === null) return null;
        if (mb_strlen($str_file_contents, "8bit") > $max_file_size) return null;
        return $str_file_contents;
    }
}

function handleReturn(ControllerRet $ret_val): null
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
            header('Content-Type: text/plain');
            echo "Bad request";
            break;
        case ControllerRet::already_exists:
            http_response_code(400);
            header('Content-Type: text/plain');
            echo "Already exists";
            break;
        case ControllerRet::unauthorised:
            http_response_code(403);
            header('Content-Type: text/plain');
            echo "Unauthorised";
            break;
        case ControllerRet::not_found:
            http_response_code(404);
            header('Content-Type: text/plain');
            echo "Not found";
            break;
        case ControllerRet::unexpected_error:
            http_response_code(500);
            header('Content-Type: text/plain');
            echo "Unexpected error";
            break;
        default:
            http_response_code(500);
            header('Content-Type: text/plain');
            echo "You shouldn't be seeing this, congrats";
            break;
    }
    return null;
}

enum ControllerRet
{
    case success;
    case success_created;
    case success_no_content;
    case bad_request;
    case already_exists;
    case unauthorised;
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
 * ReturnshandleReturn( true on success and null on failur)e
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
