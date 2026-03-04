<?php

declare(strict_types=1);

namespace DB;

require_once "error.php";
require_once "config.php";
require_once "intezmeny_sql.php";

use Config\Config;
use function Error\logError;
use Exception;
use mysqli;
use mysqli_result;

class DB
{
    public mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public static function init(): DB|null
    {
        try {
            $connection = mysqli_connect(Config::$database_address, Config::$database_username, Config::$database_password, Config::$database_name);
            if ($connection === false) {
                logError(mysqli_connect_error());
                return null;
            }
            return new DB($connection);
        } catch (Exception) {
            logError(mysqli_connect_error());
            return null;
        }
    }

    /** Returns first results contents as 2d array or null if $cur_result is null */
    private function freeRemainingResults(mysqli_result|null $cur_result): array|null
    {
        $arr = null;
        if ($cur_result !== null) {
            $arr = $cur_result->fetch_all();
            $cur_result->free_result();
        }
        while ($this->connection->next_result() !== false) {
            if ($this->connection->errno !== 0) return null;
            $next = $this->connection->store_result();
            if ($next !== false) $next->free_result();
        }
        return $arr;
    }

    /**
     * Return $ret_value
     * Only logs error if $ret_value === false
     */
    function logError(mysqli_result|bool $ret_value): mysqli_result|bool|null
    {
        if ($ret_value === false) {
            logError($this->connection->error);
            return null;
        }
        return $ret_value;
    }

    /** Frees all results but only returns the first */
    function handleQueryResult(mysqli_result|bool $ret): array|bool|null
    {
        if ($ret === false) {
            $this->freeRemainingResults(null);
            return $this->logError(false);
        }
        if ($ret === true) {
            $this->freeRemainingResults(null);
            return true;
        }
        return $this->freeRemainingResults($ret);
    }

    function getIntezmenys(int $uid): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                '
                    SELECT intezmeny.id, intezmeny.name FROM users
                    INNER JOIN intezmeny_users ON intezmeny_users.users_id = users.id
                    INNER JOIN intezmeny ON intezmeny_users.intezmeny_id = intezmeny.id
                    WHERE users.id = ?
                ',
                array($uid)
            ))) === null ? null : $ret;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function classExists(int $intezmeny_id, int $class_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM class WHERE id = ?)',
                array($class_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function classExistsViaName(int $intezmeny_id, string $class_name): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM class WHERE name = ?)',
                array($class_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function lessonExists(int $intezmeny_id, int $lesson_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM lesson WHERE id = ?)',
                array($lesson_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function lessonExistsViaName(int $intezmeny_id, string $lesson_name): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM lesson WHERE name = ?);',
                array($lesson_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function groupExists(int $intezmeny_id, int $group_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM group_ WHERE id = ?)',
                array($group_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function groupExistsViaName(int $intezmeny_id, string $group_name): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM group_ WHERE name = ?)',
                array($group_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function roomExists(int $intezmeny_id, int $room_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM room WHERE id = ?)',
                array($room_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function roomExistsViaName(int $intezmeny_id, string $room_name): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM room WHERE name = ?)',
                array($room_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function teacherExists(int $intezmeny_id, int $teacher_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM teacher WHERE id = ?)',
                array($teacher_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function timetableElementExists(int $intezmeny_id, int $timetable_element_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM timetable WHERE id = ?)',
                array($timetable_element_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function homeworkExists(int $intezmeny_id, int $homework_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM homework WHERE id = ?)',
                array($homework_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function attachmentExists(int $intezmeny_id, int $attachment_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM attachments WHERE id = ?)',
                array($attachment_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function inviteUser(int $intezmeny_id, int $uid): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
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
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT EXISTS (SELECT * FROM intezmeny_users WHERE intezmeny_id = ? AND users_id = ? AND invite_accepted = TRUE)',
                array($intezmeny_id, $uid)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function acceptInvite(int $intezmeny_id, int $uid): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'UPDATE intezmeny_users SET invite_accepted=TRUE WHERE intezmeny_id = ? AND users_id = ?',
                array($intezmeny_id, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createClass(int $intezmeny_id, string $name, int $headcount): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL newClass(?, ?)',
                array($name, $headcount)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createLesson(int $intezmeny_id, string $name): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL newLesson(?)',
                array($name)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createGroup(int $intezmeny_id, string $name, int $headcount, int|null $class_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL newGroup(?, ?, ?)',
                array($name, $headcount, $class_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createRoom(int $intezmeny_id, string $name, string|null $type, int $space): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL newRoom(?, ?, ?)',
                array($name, $type, $space)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createTeacher(int $intezmeny_id, string $name, string $job, int|null $uid): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            if ($this->handleQueryResult($this->connection->execute_query(
                'CALL newTeacher(?, ?, ?)',
                array($name, $job, $uid)
            )) === null) return null;
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'UPDATE intezmeny_users SET role_="teacher" WHERE intezmeny_id = ? AND users_id = ?',
                array($intezmeny_id, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createTimetableElement(
        int $intezmeny_id,
        string $duration,
        int $day,
        string $from,
        string $until,
        int|null $group_id,
        int|null $lesson_id,
        int|null $teacher_id,
        int|null $room_id
    ): true|null {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL newTimetableElement(?, ?, ?, ?, ?, ?, ?, ?)',
                array($duration, $day, $from, $until, $group_id, $lesson_id, $teacher_id, $room_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createHomework(int $intezmeny_id, string|null $due, int|null $lesson_id, int|null $teacher_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL newHomework(?, ?, ?)',
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
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            if ($this->handleQueryResult($this->connection->execute_query(
                'CALL newAttachment(?, ?)',
                array($homework_id, $file_name)
            )) === null) return null;
            // Have to make an sql query here since calling a procedure overwrites mysqli_insert_id
            return ($ret = $this->handleQueryResult(
                $this->connection->query("SELECT LAST_INSERT_ID()")
            )) === null ? null : (int) $ret[0][0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteClass(int $intezmeny_id, int $class_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delClass(?)',
                array($class_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteLesson(int $intezmeny_id, int $lesson_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delLesson(?)',
                array($lesson_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteGroup(int $intezmeny_id, int $group_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delGroup(?)',
                array($group_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteRoom(int $intezmeny_id, int $room_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delRoom(?)',
                array($room_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteTeacher(int $intezmeny_id, int $teacher_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->handleQueryResult($this->connection->execute_query('SELECT user_id FROM teacher WHERE id = ?', array($teacher_id)));
            if ($ret === null) return null;
            $teacher_uid = $ret[0][0];

            if ($teacher_uid !== null) {
                if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
                if ($this->handleQueryResult($this->connection->execute_query(
                    'UPDATE intezmeny_users SET role_ = "student" WHERE intezmeny_id = ? and users_id = ?',
                    array($intezmeny_id, $teacher_uid)
                )) === null) return null;
                if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            }

            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delTeacher(?)',
                array($teacher_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteTimetableElement(int $intezmeny_id, int $timetable_element_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delTimetableElement(?)',
                array($timetable_element_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteHomework(int $intezmeny_id, int $homework_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delHomework(?)',
                array($homework_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteAttachment(int $intezmeny_id, int $attachment_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL delAttachment(?)',
                array($attachment_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateClass(int $intezmeny_id, int $class_id, string $name): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modClass(?, ?)',
                array($class_id, $name)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateLesson(int $intezmeny_id, int $lesson_id, string $name): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modLesson(?, ?)',
                array($lesson_id, $name)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateGroup(int $intezmeny_id, int $group_id, string $name, int $headcount, int|null $class_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modGroup(?, ?, ?, ?)',
                array($group_id, $name, $headcount, $class_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateRoom(int $intezmeny_id, int $room_id, string $name, string|null $type, int $space): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modRoom(?, ?, ?, ?)',
                array($room_id, $name, $type, $space)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateTeacher(int $intezmeny_id, int $teacher_id, string $name, string $job, int|null $uid): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $original_uid = ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT user_id FROM teacher WHERE id = ?',
                array($teacher_id)
            ))) === null ? null : $ret[0][0];
            if ($original_uid !== $uid) {
                if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
                if ($original_uid !== null) {
                    if ($this->handleQueryResult($this->connection->execute_query(
                        'UPDATE intezmeny_users SET role_="student" WHERE intezmeny_id = ? AND users_id = ?;',
                        array($intezmeny_id, $original_uid)
                    )) === null) return null;
                }
                if ($uid !== null) {
                    if ($this->handleQueryResult($this->connection->execute_query(
                        'UPDATE intezmeny_users SET role_="teacher" WHERE intezmeny_id = ? AND users_id = ?;',
                        array($intezmeny_id, $uid)
                    )) === null) return null;
                }
                if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            }
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modTeacher(?, ?, ?, ?)',
                array($teacher_id, $name, $job, $uid)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateTimetableElement(
        int $intezmeny_id,
        int $element_id,
        string $duration,
        int $day,
        string $from,
        string $until,
        int|null $group_id,
        int|null $lesson_id,
        int|null $teacher_id,
        int|null $room_id
    ): true|null {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modTimetableElement(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array($element_id, $duration, $day, $from, $until, $group_id, $lesson_id, $teacher_id, $room_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function updateHomework(int $intezmeny_id, int $homework_id, string|null $due, int|null $lesson_id, int|null $teacher_id): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->execute_query(
                'CALL modHomework(?, ?, ?, ?)',
                array($homework_id, $due, $lesson_id, $teacher_id)
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getClasses(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->query("SELECT id, name FROM class"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getGroups(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->query(
                '
                    SELECT group_.id, group_.name, group_.headcount, class.id, class.name
                    FROM group_ LEFT JOIN class ON group_.class_id = class.id
                '
            ));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getLessons(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->query("SELECT id, name FROM lesson"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getRooms(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->query("SELECT id, name, room_type, space FROM room"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getTeachers(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->handleQueryResult($this->connection->query("SELECT * FROM teacher"));
            if ($ret === null) return null;
            $teachers = $ret;
            for ($i = 0; $i < count($teachers); $i++) {
                $ret = $this->handleQueryResult($this->connection->execute_query(
                    '
                        SELECT * FROM teacher_lesson
                        LEFT JOIN lesson ON lesson.id = teacher_lesson.lesson_id
                        WHERE teacher_lesson.teacher_id = ?
                    ',
                    array($teachers[$i][0])
                ));
                if ($ret === null) return null;
                array_push($teachers[$i], $ret);
                $ret = $this->handleQueryResult($this->connection->execute_query(
                    "SELECT * FROM teacher_availability WHERE teacher_id = ?",
                    array($teachers[$i][0])
                ));
                if ($ret === null) return null;
                array_push($teachers[$i], $ret);
            }
            return $teachers;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getTimetable(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->query("SELECT * FROM timetable"));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getHomeworkAttachments(int $intezmeny_id, int $homework_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                'SELECT attachments.id, file_name FROM homework LEFT JOIN attachments on homework.id = homework_id WHERE homework.id = ?',
                array($homework_id)
            ))) === null ? null : $ret;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getHomeworks(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $this->handleQueryResult($this->connection->query('
                SELECT homework.id, published, due, lesson.name, teacher.name
                FROM homework
                LEFT JOIN lesson ON lesson.id = lesson_id
                LEFT JOIN teacher ON teacher.id = teacher_id
            '));
            if ($ret === null) return null;
            $homeworks = $ret;
            for ($i = 0; $i < count($homeworks); $i++) {
                $ret = $this->handleQueryResult($this->connection->execute_query(
                    "SELECT id, file_name FROM attachments WHERE homework_id = ?",
                    array($homeworks[$i][0])
                ));
                if ($ret === null) return null;
                array_push($homeworks[$i], $ret);
            }
            return $homeworks;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function getAttachmentName(int $intezmeny_id, int $attachment_id): string|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $this->handleQueryResult($this->connection->execute_query(
                "SELECT file_name FROM attachments WHERE id = ?",
                array($attachment_id)
            ))) === null ? null : $ret[0][0];
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function createIntezmeny(string $intezmeny_name, int $admin_uid): true|null
    {
        global $intezmeny_tables, $intezmeny_procedures;
        
        try {
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            if ($this->handleQueryResult($this->connection->execute_query(
                'INSERT INTO intezmeny (name) VALUE (?)',
                array($intezmeny_name)
            )) === null) return null;
            $intezmeny_id = $this->connection->insert_id;
            if ($this->handleQueryResult($this->connection->execute_query(
                'INSERT INTO intezmeny_users (intezmeny_id, users_id, role_, invite_accepted) VALUE (?, ?, "admin", TRUE)',
                array($intezmeny_id, $admin_uid)
            )) === null) return null;
            $this->connection->multi_query(
                '
                    -- This allows us to replace the database without encountering foreign key errors
                    SET FOREIGN_KEY_CHECKS = 0;
                    CREATE OR REPLACE DATABASE ordayna_intezmeny_' . $intezmeny_id . ' CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";
                    SET FOREIGN_KEY_CHECKS = 1;

                    USE ordayna_intezmeny_' . $intezmeny_id . ';
                ' . $intezmeny_tables . $intezmeny_procedures,
            );
            if ($this->connection->errno !== 0) return null;
            while ($this->connection->next_result() !== false) {
                if ($this->connection->errno !== 0) return null;
            }
            return true;
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    function deleteIntezmeny(int $intezmeny_id): bool|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            if ($this->handleQueryResult($this->connection->execute_query(
                'DELETE FROM intezmeny WHERE id = ?',
                array($intezmeny_id)
            )) === null) return null;
            return $this->handleQueryResult($this->connection->query("DROP DATABASE ordayna_intezmeny_" . $intezmeny_id));
        } catch (Exception) {
            return $this->logError(false);
        }
    }

    /** The second worst thing to happen to those orphans */
    function deleteOrphanedIntezmenys(): true|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_main_db')) === null) return null;
            $ids = $this->handleQueryResult($this->connection->query("CALL getOrphanedIntezmenys()"));
            if ($ids === null) return null;
            for ($i = 0; $i < count($ids); $i++) {
                if ($this->handleQueryResult($this->connection->execute_query(
                    'DELETE FROM intezmeny WHERE id = ?',
                    array($ids[$i][0])
                )) === null) return null;
                if ($this->handleQueryResult($this->connection->query('DROP DATABASE ordayna_intezmeny_' . $ids[$i][0])) === null) return null;
            }
            return true;
        } catch (Exception) {
            return $this->logError(false);
        }
    }
}
