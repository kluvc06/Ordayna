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

    function getTimetable(int $intezmeny_id): array|null
    {
        try {
            if ($this->logError($this->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $this->handleQueryResult($this->connection->query("SELECT * FROM timetable"));
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
