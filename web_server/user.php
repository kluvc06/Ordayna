<?php

declare(strict_types=1);

namespace User;

require_once "db.php";

use DateTimeImmutable;
use DB\DB;
use Exception;

class User
{
    public int $id;
    public string $display_name;
    public string $email;
    public ?string $phone_number;
    // Hash is intentionally not stored here

    public function __construct(int $id, string $display_name, string $email, ?string $phone_number)
    {
        $this->id = $id;
        $this->display_name = $display_name;
        $this->email = $email;
        $this->phone_number = $phone_number;
    }

    public static function getUser(DB $db, int $id): User|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT id, display_name, email, phone_number FROM users WHERE id = ?',
                array($id)
            ))) === null ? null : new User($ret[0][0], $ret[0][1], $ret[0][2], $ret[0][3]);
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    // This shit might be slow
    public static function getUserViaEmail(DB $db, string $email): User|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT id, display_name, email, phone_number FROM users WHERE email = ?',
                array($email)
            ))) === null ? null : new User($ret[0][0], $ret[0][1], $ret[0][2], $ret[0][3]);
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getUserPasswordHash(DB $db, int $id): string|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT password_hash FROM users WHERE id = ?',
                array($id)
            ))) === null ? null : $ret[0][0];
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function userExists(DB $db, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM users WHERE id = ?)',
                array($id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function userExistsViaEmail(DB $db, string $email): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM users WHERE email = ?)',
                array($email)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createUser(DB $db, string $display_name, string $email, string|null $phone_number, string $password_hash): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'INSERT INTO ordayna_main_db.users (display_name, email, phone_number, password_hash) VALUE (?,?,?,?)',
                array($display_name, $email, $phone_number, $password_hash)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteUser(DB $db, int $id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'DELETE FROM users WHERE id = ?',
                array($id),
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function changeDisplayName(DB $db, int $id, string $new_disp_name): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'UPDATE users SET display_name = ? WHERE id = ?',
                array($new_disp_name, $id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function changePhoneNumber(DB $db, int $id, string $new_phone_number): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'UPDATE users SET phone_number = ? WHERE id = ?',
                array($new_phone_number, $id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function changePasswordHash(DB $db, int $id, string $new_pass_hash): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'UPDATE users SET password_hash = ? WHERE id = ?',
                array($new_pass_hash, $id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function newToken(DB $db, int $id, string $token_uuid, DateTimeImmutable $expires_after): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'INSERT INTO tokens (uid, token_uuid, expires_after) VALUE (?, ?, ?)',
                array($id, $token_uuid, $expires_after->format("Y-m-d H:i:s"))
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function isRevokedToken(DB $db, int $id, string $token_uuid): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS (SELECT * FROM tokens WHERE uid = ? AND token_uuid = ? AND is_revoked = TRUE)',
                array($id, $token_uuid)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function revokeToken(DB $db, int $id, string $token_uuid): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'UPDATE tokens SET is_revoked=TRUE WHERE uid = ? AND token_uuid = ?',
                array($id, $token_uuid)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function revokeAllTokens(DB $db, int $id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'UPDATE tokens SET is_revoked=TRUE WHERE uid = ?',
                array($id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function partOfIntezmeny(DB $db, int $intezmeny_id, int $id, bool $invite_must_be_accepted): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                '
                    SELECT EXISTS(
                        SELECT * FROM intezmeny_users
                        WHERE intezmeny_id = ? AND users_id = ?' . ($invite_must_be_accepted === true ? ' AND invite_accepted = TRUE' : '') . '
                    )
                ',
                array($intezmeny_id, $id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function isThisTeacher(DB $db, int $intezmeny_id, int $teacher_id, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS (SELECT * FROM teacher WHERE id = ? AND user_id = ?)',
                array($teacher_id, $id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function isTeacher(DB $db, int $intezmeny_id, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'CALL isTeacher(?, ?)',
                array($id, $intezmeny_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function isAdmin(DB $db, int $intezmeny_id, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'CALL isAdmin(?, ?)',
                array($id, $intezmeny_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
