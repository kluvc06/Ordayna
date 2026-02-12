<?php

declare(strict_types=1);

include "term.php";

class DB
{
    private $connection = null;

    public function __construct()
    {
        $databaseHost = file_get_contents("database_address");
        if ($databaseHost === false) fwrite(STDOUT, "Failed to read \"database_address\" file\n");
        $databaseUsername = 'ordayna_main';
        $databasePassword = '';
        $databaseName = '';

        $this->connection = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);
    }

    /**
     * Return $ret_value
     * Only logs error if $ret_value === false
     */
    function logError(mysqli_result|bool $ret_value): mysqli_result|bool|null
    {
        if ($ret_value === false) {
            $ret = file_put_contents("error_logs.txt", date("Y-m-d H:i:s", time()) . ": " . $this->connection->error . "\n", FILE_APPEND | LOCK_EX);
            if ($ret === false) fwrite(STDOUT, "Failed to write logs to \"error_logs.txt\" file\n");
            return null;
        }
        return $ret_value;
    }

    function getUserIdViaEmail(string $email): int|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->logError($this->connection->execute_query(
                'SELECT id FROM users WHERE email = ?;',
                array($email)
            ));
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getUserPassViaEmail(string $email): string|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT password_hash FROM users WHERE email = ?;',
                array($email)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function userExistsViaEmail(string $email): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM users WHERE email = ?);',
                array($email)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function userExists(int $uid): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM users WHERE id = ?);',
                array($uid)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createUser(string $display_name, string $email, string|null $phone_number, string $password_hash): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            return $this->logError($this->connection->execute_query(
                'INSERT INTO ordayna_main_db.users (display_name, email, phone_number, password_hash) VALUE (?,?,?,?);',
                array($display_name, $email, $phone_number, $password_hash)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getProfile(int $uid): array|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT id, display_name, email, phone_number FROM users WHERE id = ?',
                array($uid)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteUserViaId(int $uid): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            return $this->logError($this->connection->execute_query(
                'DELETE FROM users WHERE id = ?;',
                array($uid),
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function changeDisplayNameViaId(int $uid, string $new_disp_name): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            return $this->logError($this->connection->execute_query(
                ' UPDATE users SET display_name = ? WHERE id = ?;',
                array($new_disp_name, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function changePhoneNumberViaId(int $uid, string $new_phone_number): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            return $this->logError($this->connection->execute_query(
                'UPDATE users SET phone_number = ? WHERE id = ?;',
                array($new_phone_number, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function changePasswordHashViaId(int $uid, string $new_pass_hash): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            return $this->logError($this->connection->execute_query(
                'UPDATE users SET password_hash = ? WHERE id = ?;',
                array($new_pass_hash, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function isRevokedRefreshToken(string $token_uuid): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS (SELECT * FROM revoked_refresh_tokens WHERE uuid = ?);',
                array($token_uuid)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function newInvalidRefreshToken(string $uuid, string $expires_after): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            return $this->logError($this->connection->execute_query(
                'INSERT INTO revoked_refresh_tokens (uuid, duration) VALUE (?, ?);',
                array($uuid, $expires_after)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function partOfIntezmeny(int $intezmeny_id, int $uid, bool $invite_must_be_accepted): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                '
                    SELECT EXISTS(
                        SELECT * FROM intezmeny_users
                        WHERE intezmeny_id = ? AND users_id = ?'.($invite_must_be_accepted === true ? ' AND invite_accepted = TRUE' : '').'
                    );
                ',
                array($intezmeny_id, $uid)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function isTeacher(int $intezmeny_id, int $uid): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query('CALL isTeacher(?, ?)', array($uid, $intezmeny_id));
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function isAdmin(int $intezmeny_id, int $uid): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query('CALL isAdmin(?, ?)', array($uid, $intezmeny_id));
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getIntezmenys(int $uid): array|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $ret = $this->connection->execute_query(
                '
                    SELECT intezmeny.id, intezmeny.name FROM users
                    INNER JOIN intezmeny_users ON intezmeny_users.users_id = users.id
                    INNER JOIN intezmeny ON intezmeny_users.intezmeny_id = intezmeny.id
                    WHERE users.id = ?;
                ',
                array($uid)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all();
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function classExists(int $intezmeny_id, int $class_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM class WHERE id = ?);',
                array($class_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function classExistsViaName(int $intezmeny_id, string $class_name): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM class WHERE name = ?);',
                array($class_name)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function lessonExists(int $intezmeny_id, int $lesson_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM lesson WHERE id = ?);',
                array($lesson_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function lessonExistsViaName(int $intezmeny_id, string $lesson_name): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM lesson WHERE name = ?);',
                array($lesson_name)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function groupExists(int $intezmeny_id, int $group_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM group_ WHERE id = ?);',
                array($group_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function groupExistsViaName(int $intezmeny_id, string $group_name): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM group_ WHERE name = ?);',
                array($group_name)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function roomExists(int $intezmeny_id, int $room_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM room WHERE id = ?);',
                array($room_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function roomExistsViaName(int $intezmeny_id, string $room_name): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM room WHERE name = ?);',
                array($room_name)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function teacherExists(int $intezmeny_id, int $teacher_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM teacher WHERE id = ?)',
                array($teacher_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function timetableElementExists(int $intezmeny_id, int $timetable_element_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM timetable WHERE id = ?)',
                array($timetable_element_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function homeworkExists(int $intezmeny_id, int $homework_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM homework WHERE id = ?)',
                array($homework_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function attachmentExists(int $intezmeny_id, int $attachment_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM attachments WHERE id = ?)',
                array($attachment_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function inviteUser(int $intezmeny_id, int $uid): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_main_db;')) === null) return null;
            return $this->logError($this->connection->execute_query(
                'INSERT INTO intezmeny_users (intezmeny_id, users_id, role_, invite_accepted) VALUE (? ,?, "student", FALSE)',
                array($intezmeny_id, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    /** Only returns true if the invite exists and is accepted */
    function isInviteAccepted(int $intezmeny_id, int $uid): bool|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_main_db;')) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT EXISTS (SELECT * FROM intezmeny_users WHERE intezmeny_id = ? AND users_id = ? AND invite_accepted = TRUE);',
                array($intezmeny_id, $uid)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function acceptInvite(int $intezmeny_id, int $uid): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_main_db;')) === null) return null;
            return $this->logError($this->connection->execute_query(
                'UPDATE intezmeny_users SET invite_accepted=TRUE WHERE intezmeny_id = ? AND users_id = ?;',
                array($intezmeny_id, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createClass(int $intezmeny_id, string $name, int $headcount): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL newClass(?, ?);',
                array($name, $headcount)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createLesson(int $intezmeny_id, string $name): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL newLesson(?);',
                array($name)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createGroup(int $intezmeny_id, string $name, int $headcount, int|null $class_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL newGroup(?, ?, ?);',
                array($name, $headcount, $class_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createRoom(int $intezmeny_id, string $name, string|null $type, int $space): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL newRoom(?, ?, ?);',
                array($name, $type, $space)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createTeacher(int $intezmeny_id, string $name, string $job, int|null $uid): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            if ($this->logError($this->connection->execute_query(
                'CALL newTeacher(?, ?, ?);',
                array($name, $job, $uid)
            )) === null) return null;
            if ($this->logError($this->connection->query('USE ordayna_main_db;')) === null) return null;
            return $this->logError($this->connection->execute_query(
                'UPDATE intezmeny_users SET role_="teacher" WHERE intezmeny_id = ? AND users_id = ?;',
                array($intezmeny_id, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createTimetableElement(int $intezmeny_id, string $duration, int $day, string $from, string $until): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL newTimetableElement(?, ?, ?, ?);',
                array($duration, $day, $from, $until)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createHomework(int $intezmeny_id, string|null $due, int|null $lesson_id, int|null $teacher_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL newHomework(?, ?, ?);',
                array($due, $lesson_id, $teacher_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    /**
     * Returns the new attachments id
     */
    function createAttachment(int $intezmeny_id, int $homework_id, string $file_name): int|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            if ($this->logError($this->connection->execute_query(
                'CALL newAttachment(?, ?);',
                array($homework_id, $file_name)
            )) === null) return null;
            $ret = $this->connection->query("SELECT LAST_INSERT_ID();");
            if ($ret === false) return $this->logError(false);
            return (int) $ret->fetch_all()[0][0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteClass(int $intezmeny_id, int $class_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delClass(?);',
                array($class_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteLesson(int $intezmeny_id, int $lesson_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delLesson(?);',
                array($lesson_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteGroup(int $intezmeny_id, int $group_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delGroup(?);',
                array($group_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteRoom(int $intezmeny_id, int $room_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delRoom(?);',
                array($room_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteTeacher(int $intezmeny_id, int $teacher_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query('SELECT user_id FROM teacher WHERE id = ?;', array($teacher_id));
            if ($ret === false) return $this->logError(false);
            $teacher_uid = $ret->fetch_all()[0][0];

            if ($teacher_uid !== null) {
                if ($this->logError($this->connection->query('USE ordayna_main_db')) === null) return null;
                if ($this->logError($this->connection->execute_query(
                    'UPDATE intezmeny_users SET role_ = "student" WHERE intezmeny_id = ? and users_id = ?;',
                    array($intezmeny_id, $teacher_uid)
                )) === null) return null;
                if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            }

            return $this->logError($this->connection->execute_query(
                'CALL delTeacher(?);',
                array($teacher_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteTimetableElement(int $intezmeny_id, int $timetable_element_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delTimetableElement(?);',
                array($timetable_element_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteHomework(int $intezmeny_id, int $homework_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delHomework(?);',
                array($homework_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteAttachment(int $intezmeny_id, int $attachment_id): true|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->execute_query(
                'CALL delAttachment(?);',
                array($attachment_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getClasses(int $intezmeny_id): mysqli_result|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->query("SELECT id, name FROM class;"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getGroups(int $intezmeny_id): mysqli_result|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->query(
                '
                    SELECT group_.id, group_.name, group_.headcount, class.id, class.name
                    FROM group_ LEFT JOIN class ON group_.class_id = class.id;
                '
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getLessons(int $intezmeny_id): mysqli_result|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->query("SELECT id, name FROM lesson;"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getRooms(int $intezmeny_id): mysqli_result|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->query("SELECT id, name, room_type, space FROM room;"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getTeachers(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->query("SELECT * FROM teacher;");
            if ($ret === false) return $this->logError(false);
            $teachers = $ret->fetch_all();
            for ($i = 0; $i < count($teachers); $i++) {
                $ret = $this->connection->execute_query(
                    '
                        SELECT * FROM teacher_lesson
                        LEFT JOIN lesson ON lesson.id = teacher_lesson.lesson_id
                        WHERE teacher_lesson.teacher_id = ?;
                    ',
                    array($teachers[$i][0])
                );
                if ($ret === false) return $this->logError(false);
                array_push($teachers[$i], $ret->fetch_all());
                $ret = $this->connection->execute_query(
                    "SELECT * FROM teacher_availability WHERE teacher_id = ?;",
                    array($teachers[$i][0])
                );
                if ($ret === false) return $this->logError(false);
                array_push($teachers[$i], $ret->fetch_all());
            }
            return $teachers;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getTimetable(int $intezmeny_id): mysqli_result|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->logError($this->connection->query("SELECT * FROM timetable;"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getHomeworkAttachments(int $intezmeny_id, int $homework_id): array|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                'SELECT attachments.id, file_name FROM homework LEFT JOIN attachments on homework.id = homework_id WHERE homework.id = ?;',
                array($homework_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all();
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getHomeworks(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->query('
                SELECT homework.id, published, due, lesson.name, teacher.name
                FROM homework
                LEFT JOIN lesson ON lesson.id = lesson_id
                LEFT JOIN teacher ON teacher.id = teacher_id;
            ');
            if ($ret === false) return $this->logError(false);
            $homeworks = $ret->fetch_all();
            for ($i = 0; $i < count($homeworks); $i++) {
                $ret = $this->connection->execute_query(
                    "SELECT id, file_name FROM attachments WHERE homework_id = ?;",
                    array($homeworks[$i][0])
                );
                if ($ret === false) return $this->logError(false);
                array_push($homeworks[$i], $ret->fetch_all());
            }
            return $homeworks;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getAttachmentName(int $intezmeny_id, int $attachment_id): string|null
    {
        try {
            if ($this->logError($this->connection->query('USE ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->connection->execute_query(
                "SELECT file_name FROM attachments WHERE id = ?;",
                array($attachment_id)
            );
            if ($ret === false) return $this->logError(false);
            return $ret->fetch_all()[0][0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createIntezmeny(string $intezmeny_name, int $admin_uid): true|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            $intezmeny_id = $this->connection->query('SELECT IFNULL(MAX(id)+1, 0) FROM intezmeny;');
            if ($intezmeny_id === false) return $this->logError(false);
            $intezmeny_id = $intezmeny_id->fetch_all()[0][0];
            if ($this->logError($this->connection->execute_query(
                'SET @intezmeny_name = ?;',
                array($intezmeny_name)
            )) === null) return null;
            $this->connection->multi_query(
                '
                    SET @admin_uid = ' . $admin_uid . ';
                    SET @intezmeny_id = (SELECT IFNULL(MAX(id)+1, 0) FROM intezmeny);

                    -- This allows us to replace the database without encountering foreign key errors
                    SET FOREIGN_KEY_CHECKS = 0;
                    CREATE OR REPLACE DATABASE ordayna_intezmeny_' . $intezmeny_id . ' CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";
                    SET FOREIGN_KEY_CHECKS = 1;

                    INSERT INTO intezmeny (id, name) VALUE (@intezmeny_id, @intezmeny_name);
                    INSERT INTO intezmeny_users (intezmeny_id, users_id, role_, invite_accepted) VALUE (@intezmeny_id, @admin_uid, "admin", TRUE);

                    USE ordayna_intezmeny_' . $intezmeny_id . ';
                ' . $this->intezmeny_tables . $this->intezmeny_procedures,
            );
            while ($this->connection->more_results()) {
                $this->connection->next_result();
                if (!$this->connection->store_result() and $this->connection->errno != 0) {
                    return $this->logError(false);
                };
            }
            return true;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteIntezmeny(int $intezmeny_id): bool|null
    {
        try {
            if ($this->logError($this->connection->query("USE ordayna_main_db;")) === null) return null;
            if ($this->logError($this->connection->execute_query(
                'DELETE FROM intezmeny WHERE id = ?;',
                array($intezmeny_id)
            )) === null) return null;
            return $this->logError($this->connection->query("DROP DATABASE ordayna_intezmeny_" . $intezmeny_id));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    private $intezmeny_tables = '
        CREATE OR REPLACE TABLE class (
            id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name                 VARCHAR(200) UNIQUE NOT NULL
         );

        CREATE OR REPLACE TABLE group_ (
            id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name                 VARCHAR(200) UNIQUE NOT NULL,
            headcount            SMALLINT UNSIGNED NOT NULL,
            class_id             INT UNSIGNED,
            CONSTRAINT fk_group_class FOREIGN KEY ( class_id ) REFERENCES class( id ) ON DELETE SET NULL ON UPDATE NO ACTION
         );

        CREATE OR REPLACE INDEX fk_group_class ON group_ ( class_id );

        CREATE OR REPLACE TABLE lesson (
            id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name                 VARCHAR(200) UNIQUE NOT NULL
         );

        CREATE OR REPLACE TABLE room (
            id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name                 VARCHAR(200) UNIQUE NOT NULL,
            room_type            VARCHAR(200),
            space                SMALLINT UNSIGNED NOT NULL
         );

        CREATE OR REPLACE TABLE teacher (
            id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name                 VARCHAR(200) NOT NULL,
            job                  VARCHAR(200) NOT NULL,
            user_id              INT UNSIGNED
         );

        CREATE OR REPLACE TABLE teacher_lesson (
          teacher_id INT UNSIGNED NOT NULL,
          lesson_id INT UNSIGNED NOT NULL,
          CONSTRAINT fk_lesson_teacher FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE CASCADE ON UPDATE NO ACTION,
          CONSTRAINT fk_teacher_lesson FOREIGN KEY (teacher_id) REFERENCES teacher (id) ON DELETE CASCADE ON UPDATE NO ACTION,
          PRIMARY KEY (teacher_id, lesson_id)
        );

        CREATE OR REPLACE TABLE teacher_availability (
            id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
            teacher_id           INT UNSIGNED     NOT NULL,
            available_from_day   TINYINT UNSIGNED NOT NULL,
            available_from_time  TIME             NOT NULL,
            available_until_day  TINYINT UNSIGNED NOT NULL,
            available_until_time TIME             NOT NULL,
            CONSTRAINT fk_teacher_availability FOREIGN KEY ( teacher_id ) REFERENCES teacher( id ) ON DELETE CASCADE ON UPDATE NO ACTION
         );

        CREATE OR REPLACE INDEX fk_teacher_availability ON teacher_availability ( teacher_id );

        ALTER TABLE teacher_availability MODIFY available_from_day TINYINT UNSIGNED NOT NULL COMMENT \'0 = monday, ... , 6 = sunday\';

        ALTER TABLE teacher_availability MODIFY available_until_day TINYINT UNSIGNED NOT NULL COMMENT \'0 = monday, ... , 6 = sunday\';

        CREATE OR REPLACE TABLE timetable (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            duration   TIME NOT NULL,
            day        TINYINT UNSIGNED NOT NULL,
            from_      DATE NOT NULL,
            until      DATE NOT NULL,
            group_id   INT UNSIGNED DEFAULT NULL,
            lesson_id  INT UNSIGNED DEFAULT NULL,
            teacher_id INT UNSIGNED DEFAULT NULL,
            room_id    INT UNSIGNED DEFAULT NULL,
            CONSTRAINT fk_timetable_group_ FOREIGN KEY ( group_id ) REFERENCES group_( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
            CONSTRAINT fk_timetable_class FOREIGN KEY ( room_id ) REFERENCES room( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
            CONSTRAINT fk_timetable_lesson FOREIGN KEY ( lesson_id ) REFERENCES lesson( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
            CONSTRAINT fk_timetable_teacher FOREIGN KEY ( teacher_id ) REFERENCES teacher( id ) ON DELETE SET NULL ON UPDATE NO ACTION
         );

        CREATE OR REPLACE INDEX fk_timetable_group ON timetable ( group_id );

        CREATE OR REPLACE INDEX fk_timetable_class ON timetable ( room_id );

        CREATE OR REPLACE INDEX fk_timetable_lesson ON timetable ( lesson_id );

        CREATE OR REPlACE INDEX fk_timetable_teacher ON timetable ( teacher_id );

        CREATE OR REPLACE TABLE homework (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            published     DATETIME     NOT NULL DEFAULT NOW(),
            due           DATETIME,
            lesson_id     INT UNSIGNED,
            teacher_id    INT UNSIGNED,
            CONSTRAINT fk_homework_lesson FOREIGN KEY ( lesson_id ) REFERENCES lesson( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
            CONSTRAINT fk_homework_teacher FOREIGN KEY ( teacher_id ) REFERENCES teacher( id ) ON DELETE SET NULL ON UPDATE NO ACTION
        );

        CREATE OR REPLACE TABLE attachments (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            homework_id INT UNSIGNED NOT NULL,
            file_name VARCHAR(200) NOT NULL,
            CONSTRAINT fk_attachment_homework FOREIGN KEY ( homework_id ) REFERENCES homework( id ) ON DELETE CASCADE ON UPDATE NO ACTION
        );
    ';

    private $intezmeny_procedures = '
        -- procedures

        CREATE OR REPLACE PROCEDURE newClass ( IN in_name VARCHAR(200), IN in_headcount SMALLINT UNSIGNED )
        BEGIN
            INSERT INTO class (name) VALUES (in_name);
            CALL newGroup(in_name, in_headcount, LAST_INSERT_ID());
        END;

        CREATE OR REPLACE PROCEDURE modClass ( IN in_id INT UNSIGNED, IN in_name VARCHAR(200))
        BEGIN
            UPDATE class SET name=in_name WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delClass ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM class WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newGroup ( IN in_name VARCHAR(200), IN in_headcount SMALLINT UNSIGNED, IN in_class_id INT UNSIGNED )
        BEGIN
            INSERT INTO group_ (name, headcount, class_id) VALUES (in_name, in_headcount, in_class_id);
        END;

        CREATE OR REPLACE PROCEDURE modGroup ( IN in_id INT UNSIGNED, IN in_name VARCHAR(200), IN in_headcount SMALLINT UNSIGNED, IN in_class_id INT UNSIGNED )
        BEGIN
            UPDATE group_ SET name=in_name, headcount=in_headcount, class_id=in_class_id WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delGroup ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM group_ WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newLesson ( IN in_name VARCHAR(200) )
        BEGIN
            INSERT INTO lesson (name) VALUES (in_name);
        END;

        CREATE OR REPLACE PROCEDURE modLesson ( IN in_id INT UNSIGNED, IN in_name VARCHAR(200) )
        BEGIN
            UPDATE lesson SET name=in_name WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delLesson ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM lesson WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newRoom ( IN in_name VARCHAR(200), IN in_room_type VARCHAR(200), IN in_space INT UNSIGNED )
        BEGIN
            INSERT INTO room (name, room_type, space) VALUES (in_name, in_room_type, in_space);
        END;

        CREATE OR REPLACE PROCEDURE modRoom ( IN in_id INT UNSIGNED, IN in_name VARCHAR(200), IN in_room_type VARCHAR(200), IN in_space INT UNSIGNED )
        BEGIN
            UPDATE room SET name=in_name, room_type=in_room_type, space=in_space WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delRoom ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM room WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newTeacher ( IN in_name VARCHAR(200), IN in_job VARCHAR(200), IN in_user_id INT UNSIGNED )
        BEGIN
            INSERT INTO teacher (name, job, user_id) VALUES (in_name, in_job, in_user_id);
        END;

        CREATE OR REPLACE PROCEDURE modTeacher ( IN in_id INT UNSIGNED, IN in_name VARCHAR(200), IN in_job VARCHAR(200), in_user_id INT UNSIGNED )
        BEGIN
            UPDATE teacher SET name=in_name, job=in_job, user_id=in_user_id WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delTeacher ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM teacher WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newTeacher_lesson ( IN in_teacher_id INT UNSIGNED, IN in_lesson_id INT UNSIGNED )
        BEGIN
            INSERT INTO teacher_lesson (teacher_id, lesson_id) VALUES (in_teacher_id, in_lesson_id);
        END;

        CREATE OR REPLACE PROCEDURE modTeacher_lesson ( IN in_id INT UNSIGNED, IN in_teacher_id INT UNSIGNED, IN in_lesson_id INT UNSIGNED )
        BEGIN
            UPDATE teacher_lesson SET teacher_id=in_teacher_id, lesson_id=in_lesson_id WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delTeacher_lesson ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM teacher_lesson WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newTeacher_availability ( IN in_teacher_id INT UNSIGNED, IN in_available_from_day TINYINT UNSIGNED, IN in_available_from_time TIME, IN in_available_until_day TINYINT UNSIGNED, IN in_available_until_time TIME )
        BEGIN
            INSERT INTO teacher_availability (teacher_id, available_from_day, available_from_time, available_until_day, available_until_time) VALUES (in_teacher_id, in_available_from_day, in_available_from_time, in_available_until_day, in_available_until_time);
        END;

        CREATE OR REPLACE PROCEDURE modTeacher_availability ( IN in_id INT UNSIGNED, IN in_teacher_id INT UNSIGNED, IN in_available_from_day TINYINT UNSIGNED, IN in_available_from_time TIME, IN in_available_until_day TINYINT UNSIGNED, IN in_available_until_time TIME )
        BEGIN
            UPDATE teacher_availability SET teacher_id=in_teacher_id, available_from_day=in_available_from_day, available_from_time=in_available_from_time, available_until_day=in_available_until_day, available_until_time=in_available_until_time WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delTeacher_availability ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM teacher_availability WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newTimetableElement ( IN in_duration TIME, IN in_day TINYINT UNSIGNED, IN in_from DATE, IN in_until DATE )
        BEGIN
            INSERT INTO timetable (duration, day, from_, until) VALUES (in_duration, in_day, in_from, in_until);
        END;

        CREATE OR REPLACE PROCEDURE modTimetableElement ( IN in_id INT UNSIGNED, IN in_duration TIME, IN in_day TINYINT UNSIGNED, IN in_from DATE, IN in_until DATE, IN in_group_id INT UNSIGNED, IN in_lesson_id INT UNSIGNED, IN in_teacher_id INT UNSIGNED, IN in_room_id INT UNSIGNED )
        BEGIN
            UPDATE timetable SET duration=in_duration, day=in_day, from_=in_from, until=in_until, group_id=in_group_id, lesson_id=in_lesson_id, teacher_id=in_teacher_id, room_id=in_room_id WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delTimetableElement ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM timetable WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newHomework ( IN in_due DATETIME, IN in_lesson_id INT UNSIGNED, IN in_teacher_id INT UNSIGNED )
        BEGIN
            INSERT INTO homework (due, lesson_id, teacher_id) VALUES (in_due, in_lesson_id, in_teacher_id);
        END;

        CREATE OR REPLACE PROCEDURE modHomework ( IN in_id INT UNSIGNED, IN in_due DATETIME, IN in_lesson_id INT UNSIGNED, IN in_teacher_id INT UNSIGNED )
        BEGIN
            UPDATE homework SET due=in_due, lesson_id=in_lesson_id, teacher_id=in_teacher_id WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delHomework ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM homework WHERE id=in_id;
        END;

        CREATE OR REPLACE PROCEDURE newAttachment ( IN in_homework_id INT UNSIGNED, IN in_file_name VARCHAR(200) )
        BEGIN
            INSERT INTO attachments (homework_id, file_name) VALUES (in_homework_id, in_file_name);
        END;

        CREATE OR REPLACE PROCEDURE modAttachment ( IN in_id INT UNSIGNED, IN in_file_name VARCHAR(200) )
        BEGIN
            UPDATE attachments SET file_name=in_file_name WHERE in_id=id;
        END;

        CREATE OR REPLACE PROCEDURE delAttachment ( IN in_id INT UNSIGNED )
        BEGIN
            DELETE FROM attachments WHERE id=in_id;
        END;
    ';
}
