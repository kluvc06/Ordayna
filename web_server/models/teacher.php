<?php

declare(strict_types=1);

namespace Teacher;

require_once "models/lesson.php";
require_once "models/availability.php";
require_once "db.php";

use Availability\Availability;
use DateTime;
use DateTimeZone;
use DB\DB;
use Exception;
use Lesson\Lesson;

class Teacher
{
    public int $id;
    public string $name;
    public string $job;
    public int $uid;
    public array $lessons;
    public array $availabilitys;

    public function __construct(int $id, string $name, string $job, int $uid, array $lessons, array $availabilitys)
    {
        $this->id = $id;
        $this->name = $name;
        $this->job = $job;
        $this->uid = $uid;
        $this->lessons = $lessons;
        $this->availabilitys = $availabilitys;
    }

    public static function teacherExists(DB $db, int $intezmeny_id, int $teacher_id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM teacher WHERE id = ?)',
                array($teacher_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createTeacher(DB $db, int $intezmeny_id, string $name, string $job, int|null $uid): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            if ($db->handleQueryResult($db->connection->execute_query(
                'CALL newTeacher(?, ?, ?)',
                array($name, $job, $uid)
            )) === null) return null;
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'UPDATE intezmeny_users SET role_="teacher" WHERE intezmeny_id = ? AND users_id = ?',
                array($intezmeny_id, $uid)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteTeacher(DB $db, int $intezmeny_id, int $teacher_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->execute_query('SELECT user_id FROM teacher WHERE id = ?', array($teacher_id)));
            if ($ret === null) return null;
            $teacher_uid = $ret[0][0];

            if ($teacher_uid !== null) {
                if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
                if ($db->handleQueryResult($db->connection->execute_query(
                    'UPDATE intezmeny_users SET role_ = "student" WHERE intezmeny_id = ? and users_id = ?',
                    array($intezmeny_id, $teacher_uid)
                )) === null) return null;
                if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            }

            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delTeacher(?)',
                array($teacher_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateTeacher(DB $db, int $intezmeny_id, int $teacher_id, string $name, string $job, int|null $uid): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $original_uid = ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT user_id FROM teacher WHERE id = ?',
                array($teacher_id)
            ))) === null ? null : $ret[0][0];
            if ($original_uid !== $uid) {
                if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
                if ($original_uid !== null) {
                    if ($db->handleQueryResult($db->connection->execute_query(
                        'UPDATE intezmeny_users SET role_="student" WHERE intezmeny_id = ? AND users_id = ?;',
                        array($intezmeny_id, $original_uid)
                    )) === null) return null;
                }
                if ($uid !== null) {
                    if ($db->handleQueryResult($db->connection->execute_query(
                        'UPDATE intezmeny_users SET role_="teacher" WHERE intezmeny_id = ? AND users_id = ?;',
                        array($intezmeny_id, $uid)
                    )) === null) return null;
                }
                if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            }
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modTeacher(?, ?, ?, ?)',
                array($teacher_id, $name, $job, $uid)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getTeachers(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query("SELECT * FROM teacher"));
            if ($ret === null) return null;
            $teachers = $ret;
            for ($i = 0; $i < count($teachers); $i++) {
                $ret = $db->handleQueryResult($db->connection->execute_query(
                    '
                        SELECT lesson.id, lesson.name FROM teacher_lesson
                        LEFT JOIN lesson ON lesson.id = teacher_lesson.lesson_id
                        WHERE teacher_lesson.teacher_id = ?
                    ',
                    array($teachers[$i][0])
                ));
                if ($ret === null) return null;
                array_push($teachers[$i], $ret);
                $ret = $db->handleQueryResult($db->connection->execute_query(
                    "SELECT * FROM teacher_availability WHERE teacher_id = ?",
                    array($teachers[$i][0])
                ));
                if ($ret === null) return null;
                array_push($teachers[$i], $ret);
            }
            for ($i = 0; $i < count($teachers); $i++) {
                for ($j = 0; $j < count($teachers[$i][4]); $j++) {
                    $teachers[$i][4][$j] = new Lesson((int) $teachers[$i][4][$j][0], $teachers[$i][4][$j][1]);
                }
                for ($j = 0; $j < count($teachers[$i][5]); $j++) {
                    $teachers[$i][5][$j] = new Availability(
                        (int) $teachers[$i][5][$j][0],
                        (int) $teachers[$i][5][$j][1],
                        new DateTime($teachers[$i][5][$j][2], new DateTimeZone("UTC")),
                        (int) $teachers[$i][5][$j][3],
                        new DateTime($teachers[$i][5][$j][4], new DateTimeZone("UTC")),
                    );
                }
                $teachers[$i] = new Teacher(
                    (int) $teachers[$i][0],
                    $teachers[$i][1],
                    $teachers[$i][2],
                    (int) $teachers[$i][3],
                    $teachers[$i][4],
                    $teachers[$i][5]
                );
            }
            return $teachers;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
