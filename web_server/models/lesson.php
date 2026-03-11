<?php

declare(strict_types=1);

namespace Lesson;

require_once "db.php";

use DB\DB;
use Exception;

class Lesson
{
    public int $id;
    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function lessonExists(DB $db, int $intezmeny_id, int $lesson_id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM lesson WHERE id = ?)',
                array($lesson_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function lessonExistsViaName(DB $db, int $intezmeny_id, string $lesson_name): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM lesson WHERE name = ?);',
                array($lesson_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createLesson(DB $db, int $intezmeny_id, string $name): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL newLesson(?)',
                array($name)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteLesson(DB $db, int $intezmeny_id, int $lesson_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delLesson(?)',
                array($lesson_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateLesson(DB $db, int $intezmeny_id, int $lesson_id, string $name): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modLesson(?, ?)',
                array($lesson_id, $name)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getLessons(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query("SELECT id, name FROM lesson"));
            if ($ret === null) return null;
            $arr = array();
            for ($i = 0; $i < count($ret); $i++) {
                array_push($arr, new Lesson((int) $ret[$i][0], $ret[$i][1]));
            }
            return $arr;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
