<?php

declare(strict_types=1);

namespace Homework;

require_once "models/lesson.php";
require_once "models/attachment.php";
require_once "db.php";

use Lesson\Lesson;
use Attachment\Attachment;
use Class_\Class_;
use DB\DB;
use Exception;
use Group\Group;

class Homework
{
    public int $id;
    public string $description;
    public string $published;
    public ?string $due;
    public Group $group;
    public ?Lesson $lesson;
    public ?Teacher $teacher;
    public array $attachments;

    public function __construct(int $id, string $description, string $published, ?string $due, Group $group, ?Lesson $lesson, ?Teacher $teacher, array $attachments)
    {
        $this->id = $id;
        $this->description = $description;
        $this->published = $published;
        $this->due = $due;
        $this->group = $group;
        $this->lesson = $lesson;
        $this->teacher = $teacher;
        $this->attachments = $attachments;
    }

    public static function homeworkExists(DB $db, int $intezmeny_id, int $homework_id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM homework WHERE id = ?)',
                array($homework_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createHomework(DB $db, int $intezmeny_id, string $description, string|null $due, int $group_id, int|null $lesson_id, int|null $teacher_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL newHomework(?, ?, ?, ?, ?)',
                array($description, $due, $group_id, $lesson_id, $teacher_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteHomework(DB $db, int $intezmeny_id, int $homework_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delHomework(?)',
                array($homework_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateHomework(DB $db, int $intezmeny_id, int $homework_id, string $description, string|null $due, int $group_id, int|null $lesson_id, int|null $teacher_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modHomework(?, ?, ?, ?, ?, ?)',
                array($homework_id, $description, $due, $group_id, $lesson_id, $teacher_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getHomeworks(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query('
                SELECT homework.id, description, published, due, group_.id, group_.name, group_.headcount,
                       class.id, class.name, lesson.id, lesson.name, teacher.id, teacher.name
                FROM homework
                LEFT JOIN group_ ON group_.id = group_id
                LEFT JOIN class ON class.id = group_.class_id
                LEFT JOIN lesson ON lesson.id = lesson_id
                LEFT JOIN teacher ON teacher.id = teacher_id
                ORDER BY published DESC, due DESC
            '));
            if ($ret === null) return null;
            $homeworks = $ret;
            for ($i = 0; $i < count($homeworks); $i++) {
                $ret = $db->handleQueryResult($db->connection->execute_query(
                    "SELECT id, file_name FROM attachments WHERE homework_id = ?",
                    array($homeworks[$i][0])
                ));
                if ($ret === null) return null;
                array_push($homeworks[$i], $ret);
            }
            for ($i = 0; $i < count($homeworks); $i++) {
                for ($j = 0; $j < count($homeworks[$i][13]); $j++) {
                    $homeworks[$i][13][$j] = new Attachment($homeworks[$i][13][$j][0], $homeworks[$i][13][$j][1]);
                }
                $homeworks[$i] = new Homework(
                    (int) $homeworks[$i][0],
                    $homeworks[$i][1],
                    $homeworks[$i][2],
                    $homeworks[$i][3],
                    $homeworks[$i][4] === null ? null : new Group((int) $homeworks[$i][4], $homeworks[$i][5], (int) $homeworks[$i][6], new Class_(
                        (int) $homeworks[$i][7],
                        $homeworks[$i][8]
                    )),
                    $homeworks[$i][9] === null ? null : new Lesson((int) $homeworks[$i][9], $homeworks[$i][10]),
                    $homeworks[$i][11] === null ? null : new Teacher((int) $homeworks[$i][11], $homeworks[$i][12]),
                    $homeworks[$i][13]
                );
            }
            return $homeworks;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}

class Teacher
{
    public int $id;
    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
