<?php

static $is_test_server = php_sapi_name() === "cli-server";

use Lcobucci\JWT\Token\RegisteredClaims;

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

    public function getRefreshToken(): UserControllerRet
    {
        global $is_test_server;

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->pass) or !is_string($data->pass)
        ) {
            return UserControllerRet::bad_request;
        }

        $main_db = new MainDb();

        if (!$main_db->userExistsEmail($data->email)) {
            return UserControllerRet::user_does_not_exist;
        }

        $user_pass = $main_db->getUserPassViaEmail($data->email);
        if (!$user_pass or !password_verify($data->pass, $user_pass)) {
            return UserControllerRet::unauthorised;
        }

        $user_id = $main_db->getUserIdViaEmail($data->email);
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

        return UserControllerRet::success;
    }

    public function refreshRefreshToken(): UserControllerRet
    {
        global $is_test_server;

        if (!isset($_COOKIE["RefreshToken"]) or !is_string($_COOKIE["RefreshToken"])) {
            return UserControllerRet::bad_request;
        }

        $main_db = new MainDb();

        $token = $this->jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === null) {
            return UserControllerRet::bad_request;
        }

        $invalid_ids = $main_db->getRevokedRefreshTokens();
        if (!$this->jwt->validateRefreshToken($token, $invalid_ids)) {
            return UserControllerRet::unauthorised;
        }
        $user_id = $token->claims()->get("uid");
        $new_token = $this->jwt->createRefreshToken($user_id);

        // Expires after 15 days
        $main_db->newInvalidRefreshToken($token->claims()->get(RegisteredClaims::ID), '15 0:0:0');

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

        return UserControllerRet::success;
    }

    public function getAccessToken(): UserControllerRet
    {
        global $is_test_server;

        if (!isset($_COOKIE["RefreshToken"]) or !is_string($_COOKIE["RefreshToken"])) {
            return UserControllerRet::bad_request;
        }

        $main_db = new MainDb();

        $token = $this->jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === null) {
            return UserControllerRet::bad_request;
        }

        $invalid_ids = $main_db->getRevokedRefreshTokens();
        if (!$this->jwt->validateRefreshToken($token, $invalid_ids)) {
            return UserControllerRet::unauthorised;
        }
        $user_id = $token->claims()->get("uid");
        $new_access_token = $this->jwt->createAccessToken($user_id);

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

        return UserControllerRet::success;
    }

    public function createUser(): UserControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        if (
            !isset($data->disp_name) or !is_string($data->disp_name) or
            !isset($data->email) or !is_string($data->email) or !preg_match('/^[^@]+[@]+[^@]+$/', $data->email) or
            !isset($data->pass) or !is_string($data->pass) or strlen($data->pass) < 8
        ) {
            return UserControllerRet::bad_request;
        }

        $phone_number = null;
        if (isset($data->phone_number)) {
            if (!is_string($data->phone_number) or strlen($data->phone_number) > 15 or !is_numeric($data->phone_number)) {
                return UserControllerRet::bad_request;
            }
            $phone_number = $data->phone_number;
        }

        $main_db = new MainDb();

        if ($main_db->userExistsEmail($data->email)) {
            return UserControllerRet::user_already_exists;
        }

        $pass_hash = password_hash($data->pass, PASSWORD_BCRYPT);
        if (!$main_db->createUser($data->disp_name, $data->email, $phone_number, $pass_hash)) {
            return UserControllerRet::unexpected_error;
        }

        return UserControllerRet::success_created;
    }

    public function deleteUser(): UserControllerRet
    {
        if (!isset($_COOKIE["AccessToken"]) or !is_string($_COOKIE["AccessToken"])) {
            return UserControllerRet::bad_request;
        }

        $token = $this->jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) {
            return UserControllerRet::bad_request;
        }

        if (!$this->jwt->validateAccessToken($token)) {
            return UserControllerRet::unauthorised;
        }
        $user_id = $token->claims()->get("uid");

        $main_db = new MainDb();

        if (!$main_db->userExistsViaId($user_id)) {
            return UserControllerRet::user_does_not_exist;
        }

        if (!$main_db->deleteUserViaId($user_id)) {
            return UserControllerRet::unexpected_error;
        }

        // Unset token cookies
        setcookie('RefreshToken', "", 0);
        setcookie('AccessToken', "", 0);

        return UserControllerRet::success_no_content;
    }

    public function changeDisplayName(): UserControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->new_disp_name) or !is_string($data->new_disp_name) or strlen($data->new_disp_name) > 200) {
            return UserControllerRet::bad_request;
        }

        if (!isset($_COOKIE["AccessToken"]) or !is_string($_COOKIE["AccessToken"])) {
            return UserControllerRet::bad_request;
        }

        $token = $this->jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) {
            return UserControllerRet::bad_request;
        }

        if (!$this->jwt->validateAccessToken($token)) {
            return UserControllerRet::unauthorised;
        }
        $user_id = $token->claims()->get("uid");

        $main_db = new MainDb();

        if (!$main_db->userExistsViaId($user_id)) {
            return UserControllerRet::user_does_not_exist;
        }

        if (!$main_db->changeDisplayNameViaId($user_id, $data->new_disp_name)) {
            return UserControllerRet::unexpected_error;
        }

        return UserControllerRet::success_no_content;
    }

    public function changePhoneNumber(): UserControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        if (
            !isset($data->new_phone_number) or !is_string($data->new_phone_number) or
            strlen($data->new_phone_number) > 15 or !is_numeric($data->new_phone_number)
        ) {
            return UserControllerRet::bad_request;
        }

        if (!isset($_COOKIE["AccessToken"]) or !is_string($_COOKIE["AccessToken"])) {
            return UserControllerRet::bad_request;
        }

        $token = $this->jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) {
            return UserControllerRet::bad_request;
        }

        if (!$this->jwt->validateAccessToken($token)) {
            return UserControllerRet::unauthorised;
        }
        $user_id = $token->claims()->get("uid");

        $main_db = new MainDb();

        if (!$main_db->userExistsViaId($user_id)) {
            return UserControllerRet::user_does_not_exist;
        }

        if (!$main_db->changePhoneNumberViaId($user_id, $data->new_phone_number)) {
            return UserControllerRet::unexpected_error;
        }

        return UserControllerRet::success_no_content;
    }

    public function changePassword(): UserControllerRet
    {
        $data = json_decode(file_get_contents("php://input"));
        if (
            !isset($data->new_pass) or !is_string($data->new_pass) or strlen($data->new_pass) < 8
        ) {
            return UserControllerRet::bad_request;
        }

        if (!isset($_COOKIE["AccessToken"]) or !is_string($_COOKIE["AccessToken"])) {
            return UserControllerRet::bad_request;
        }

        $token = $this->jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === null) {
            return UserControllerRet::bad_request;
        }

        if (!$this->jwt->validateAccessToken($token)) {
            return UserControllerRet::unauthorised;
        }
        $user_id = $token->claims()->get("uid");

        $main_db = new MainDb();

        if (!$main_db->userExistsViaId($user_id)) {
            return UserControllerRet::user_does_not_exist;
        }

        if (!$main_db->changePasswordHashViaId($user_id, password_hash($data->new_pass, PASSWORD_BCRYPT))) {
            return UserControllerRet::unexpected_error;
        }

        return UserControllerRet::success_no_content;
    }
}

function handleReturn(UserControllerRet $ret_val): void
{
    switch ($ret_val) {
        case UserControllerRet::success:
            http_response_code(200);
            break;
        case UserControllerRet::success_created:
            http_response_code(201);
            break;
        case UserControllerRet::success_no_content:
            http_response_code(204);
            break;
        case UserControllerRet::bad_request:
            http_response_code(400);
            echo "Bad request";
            break;
        case UserControllerRet::user_does_not_exist:
            http_response_code(400);
            echo "User does not exist";
            break;
        case UserControllerRet::user_already_exists:
            http_response_code(400);
            echo "User already exists";
            break;
        case UserControllerRet::unauthorised:
            http_response_code(403);
            echo "Unauthorised";
            break;
        case UserControllerRet::unexpected_error:
            http_response_code(500);
            echo "Unexpected error";
            break;
    }
}

enum UserControllerRet
{
    case success;
    case success_created;
    case success_no_content;
    case bad_request;
    case unauthorised;
    case user_does_not_exist;
    case user_already_exists;
    case unexpected_error;
}
