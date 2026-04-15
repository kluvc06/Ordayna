<?php

declare(strict_types=1);

namespace Timetable;

require_once "db.php";

use DB\DB;
use Exception;

class TimetableElement
{
    public int $id;
    public string $start;
    public string $duration;
    public int $day;
    public string $from;
    public string $until;
    public ?int $group_id;
    public ?int $lesson_id;
    public ?int $teacher_id;
    public ?int $room_id;

    public function __construct(
        int $id,
        string $start,
        string $duration,
        int $day,
        string $from,
        string $until,
        ?int $group_id,
        ?int $lesson_id,
        ?int $teacher_id,
        ?int $room_id
    ) {
        $this->id = $id;
        $this->start = $start;
        $this->duration = $duration;
        $this->day = $day;
        $this->from = $from;
        $this->until = $until;
        $this->group_id = $group_id;
        $this->lesson_id = $lesson_id;
        $this->teacher_id = $teacher_id;
        $this->room_id = $room_id;
    }

    public static function timetableElementExists(DB $db, int $intezmeny_id, int $timetable_element_id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM timetable WHERE id = ?)',
                array($timetable_element_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createTimetableElement(
        DB $db,
        int $intezmeny_id,
        string $start,
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
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL newTimetableElement(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array($start, $duration, $day, $from, $until, $group_id, $lesson_id, $teacher_id, $room_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteTimetableElement(DB $db, int $intezmeny_id, int $timetable_element_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delTimetableElement(?)',
                array($timetable_element_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateTimetableElement(
        DB $db,
        int $intezmeny_id,
        int $element_id,
        string $start,
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
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modTimetableElement(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array($element_id, $start, $duration, $day, $from, $until, $group_id, $lesson_id, $teacher_id, $room_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getTimetable(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query("SELECT * FROM timetable"));
            if ($ret === null) return null;
            $arr = array();
            for ($i = 0; $i < count($ret); $i++) {
                array_push($arr, new TimetableElement(
                    (int) $ret[$i][0],
                    $ret[$i][1],
                    $ret[$i][2],
                    (int) $ret[$i][3],
                    $ret[$i][4],
                    $ret[$i][5],
                    $ret[$i][6] === null ? null : (int) $ret[$i][6],
                    $ret[$i][7] === null ? null : (int) $ret[$i][7],
                    $ret[$i][8] === null ? null : (int) $ret[$i][8],
                    $ret[$i][9] === null ? null : (int) $ret[$i][9],
                ));
            }
            return $arr;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
