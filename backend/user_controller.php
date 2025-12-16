<?php

include "main_db.php";
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

        $main_db = new MainDb();
        $res = $main_db->getAllUsers($intezmeny_id);

        return json_encode($res->fetch_all());
    }

    public function getSessionToken(): TokenRet
    {
        $data = json_decode(file_get_contents("php://input"));

        $main_db = new MainDb();

        if (
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->pass) or !is_string($data->pass)
        ) {
            return TokenRet::bad_request;
        }

        if (!$main_db->userExistsEmail($data->email)) {
            return TokenRet::user_does_not_exist;
        }

        $user_pass = $main_db->getUserPassViaEmail($data->email);
        if (!$user_pass or !password_verify($data->pass, $user_pass)) {
            return TokenRet::unauthorised;
        }

        $user_id = $main_db->getUserIdViaEmail($data->email);
        $ses_token = $this->jwt->createSessionToken($user_id);

        $arr_cookie_options = array(
            'expires' => time() + 60 * 60 * 24 * 15,
            'path' => '/refresh_refresh_token',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', $ses_token->toString(), $arr_cookie_options);

        return TokenRet::success;
    }

    public function refreshRefreshToken(): TokenRet
    {
        $data = json_decode(file_get_contents("php://input"));

        $main_db = new MainDb();

        $token = $this->jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === null) {
            return TokenRet::bad_request;
        }
        $user_id = $token->claims()->get("uid");
        $ses_token = $this->jwt->createSessionToken($user_id);

        $arr_cookie_options = array(
            'expires' => time() + 60 * 60 * 24 * 15,
            'path' => '/refresh_refresh_token',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', $ses_token->toString(), $arr_cookie_options);

        return TokenRet::success;
    }

    public function createUser(): CreateUserRet
    {
        $data = json_decode(file_get_contents("php://input"));
        if (
            !isset($data->disp_name) or !is_string($data->disp_name) or
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->pass) or !is_string($data->pass) or strlen($data->pass) < 8
        ) {
            return CreateUserRet::bad_request;
        }

        $phone_number = null;
        if (isset($data->phone_number)) {
            if (!is_string($data->phone_number) or strlen($data->phone_number) > 15 or !is_numeric($data->phone_number)) {
                return CreateUserRet::bad_request;
            }
            $phone_number = $data->phone_number;
        }

        $main_db = new MainDb();

        if ($main_db->userExistsEmail($data->email)) {
            return CreateUserRet::user_already_exists;
        }

        $pass_hash = password_hash($data->pass, PASSWORD_BCRYPT);
        if (!$main_db->createUser($data->disp_name, $data->email, $phone_number, $pass_hash)) {
            return CreateUserRet::unexpected_error;
        }

        return CreateUserRet::success;
    }

    public function deleteUser(): DeleteUserRet
    {
        $data = json_decode(file_get_contents("php://input"));

        $main_db = new MainDb();

        if (
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->pass) or !is_string($data->pass)
        ) {
            return DeleteUserRet::bad_request;
        }

        if (!$main_db->userExistsEmail($data->email)) {
            return DeleteUserRet::user_does_not_exist;
        }

        $user_pass = $main_db->getUserPassViaEmail($data->email);

        if (!$user_pass or !password_verify($data->pass, $user_pass)) {
            return DeleteUserRet::unauthorised;
        }

        if (!$main_db->deleteUserViaEmail($data->email)) {
            return DeleteUserRet::unexpected_error;
        }

        return DeleteUserRet::success;
    }

    public function changeDisplayName(): ChangeUserRet
    {
        $data = json_decode(file_get_contents("php://input"));

        $main_db = new MainDb();

        if (
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->new_disp_name) or !is_string($data->new_disp_name) or
            !isset($data->pass) or !is_string($data->pass)
        ) {
            return ChangeUserRet::bad_request;
        }

        if (!$main_db->userExistsEmail($data->email)) {
            return ChangeUserRet::user_does_not_exist;
        }

        $user_pass = $main_db->getUserPassViaEmail($data->email);

        if (!$user_pass or !password_verify($data->pass, $user_pass)) {
            return ChangeUserRet::unauthorised;
        }

        if (!$main_db->changeDisplayNameViaEmail($data->email, $data->new_disp_name)) {
            return ChangeUserRet::unexpected_error;
        }

        return ChangeUserRet::success;
    }

    public function changePhoneNumber(): ChangeUserRet
    {
        $data = json_decode(file_get_contents("php://input"));

        $main_db = new MainDb();

        if (
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->new_phone_number) or !is_string($data->new_phone_number) or
            strlen($data->new_phone_number) > 15 or !is_numeric($data->new_phone_number) or
            !isset($data->pass) or !is_string($data->pass)
        ) {
            return ChangeUserRet::bad_request;
        }

        if (!$main_db->userExistsEmail($data->email)) {
            return ChangeUserRet::user_does_not_exist;
        }

        $user_pass = $main_db->getUserPassViaEmail($data->email);

        if (!$user_pass or !password_verify($data->pass, $user_pass)) {
            return ChangeUserRet::unauthorised;
        }

        if (!$main_db->changePhoneNumberViaEmail($data->email, $data->new_phone_number)) {
            return ChangeUserRet::unexpected_error;
        }

        return ChangeUserRet::success;
    }

    public function changePassword(): ChangeUserRet
    {
        $data = json_decode(file_get_contents("php://input"));

        $main_db = new MainDb();

        if (
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->new_pass) or !is_string($data->new_pass) or strlen($data->new_pass) < 8 or
            !isset($data->pass) or !is_string($data->pass)
        ) {
            return ChangeUserRet::bad_request;
        }

        if (!$main_db->userExistsEmail($data->email)) {
            return ChangeUserRet::user_does_not_exist;
        }

        $user_pass = $main_db->getUserPassViaEmail($data->email);

        if (!$user_pass or !password_verify($data->pass, $user_pass)) {
            return ChangeUserRet::unauthorised;
        }

        if (!$main_db->changePasswordHashViaEmail($data->email, password_hash($data->new_pass, PASSWORD_BCRYPT))) {
            return ChangeUserRet::unexpected_error;
        }

        return ChangeUserRet::success;
    }
}

enum TokenRet
{
    case success;
    case bad_request;
    case unauthorised;
    case user_does_not_exist;
    case unexpected_error;
}

enum CreateUserRet
{
    case success;
    case bad_request;
    case user_already_exists;
    case unexpected_error;
}

enum DeleteUserRet
{
    case success;
    case bad_request;
    case user_does_not_exist;
    case unauthorised;
    case unexpected_error;
}

enum ChangeUserRet
{
    case success;
    case bad_request;
    case user_does_not_exist;
    case unauthorised;
    case unexpected_error;
}
