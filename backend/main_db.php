
<?php

class MainDb
{
    private $connection = null;

    public function __construct()
    {
        $databaseHost = 'localhost';
        $databaseUsername = 'ordayna_main';
        $databasePassword = '';
        $databaseName = 'ordayna_main_db';

        $this->connection = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);
    }

    function getAllUsers(int $intezmeny_id): mysqli_result|bool
    {
        try {
            return $this->connection->execute_query(
                '
                    SELECT display_name, email, phone_number FROM intezmeny_ids
                    LEFT JOIN intezmeny_ids_users ON intezmeny_ids_id = intezmeny_ids.id
                    LEFT JOIN users ON users_id = users.id

                    WHERE intezmeny_ids.intezmeny_id = ?
                    ;
                ',
                array($intezmeny_id),
            );
        } catch (Exception $e) {
            return false;
        }
    }

    function getUserIdViaEmail(string $email): int|bool
    {
        try {
            return $this->connection->execute_query(
                '
                    SELECT id FROM users WHERE email = ?;
                ',
                array($email),
            )->fetch_all()[0][0];
        } catch (Exception $e) {
            return false;
        }
    }

    function getUserPassViaEmail(string $email): string|bool
    {
        try {
            return $this->connection->execute_query(
                '
                    SELECT password_hash FROM users WHERE email = ?;
                ',
                array($email),
            )->fetch_all()[0][0];
        } catch (Exception $e) {
            return false;
        }
    }

    function userExistsEmail(string $email): bool
    {
        try {
            $ret = $this->connection->execute_query(
                '
                    SELECT EXISTS(SELECT * FROM users WHERE email = ?)
                ',
                array($email)
            );
        } catch (Exception $e) {
            return false;
        }

        return $ret->fetch_all()[0][0];
    }

    /**
     * Assumes the user doesn't exist
     * Returns true on success and false on error
     * phone_number is either a string or null
     */
    function createUser(string $display_name, string $email, mixed $phone_number, string $password_hash): bool
    {
        try {
            return $this->connection->execute_query(
                '
                    INSERT INTO users (display_name, email, phone_number, password_hash) VALUE (?,?,?,?);
                ',
                array($display_name, $email, $phone_number, $password_hash)
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Assumes the user exists
     * Returns true on success and false on error
     */
    function deleteUserViaEmail(string $email): bool
    {
        try {
            return $this->connection->execute_query(
                '
                    DELETE FROM users WHERE email = ?;
                ',
                array($email),
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Assumes the user exists
     * Returns true on success and false on error
     */
    function changeDisplayNameViaEmail(string $email, string $new_disp_name): bool
    {
        try {
            return $this->connection->execute_query(
                '
                    UPDATE users SET display_name = ? WHERE email = ?;
                ',
                array($new_disp_name, $email),
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Assumes the user exists
     * Returns true on success and false on error
     */
    function changePhoneNumberViaEmail(string $email, string $new_phone_number): bool
    {
        try {
            return $this->connection->execute_query(
                '
                    UPDATE users SET phone_number = ? WHERE email = ?;
                ',
                array($new_phone_number, $email),
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Assumes the user exists
     * Returns true on success and false on error
     */
    function changePasswordHashViaEmail(string $email, string $new_pass_hash): bool
    {
        try {
            return $this->connection->execute_query(
                '
                    UPDATE users SET password_hash = ? WHERE email = ?;
                ',
                array($new_pass_hash, $email),
            );
        } catch (Exception $e) {
            return false;
        }
    }
}
