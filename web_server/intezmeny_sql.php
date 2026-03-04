<?php

declare(strict_types=1);

$intezmeny_tables = '
CREATE OR REPLACE TABLE class (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) UNIQUE NOT NULL
);

CREATE OR REPLACE TABLE group_ (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(200) UNIQUE NOT NULL,
    headcount SMALLINT UNSIGNED NOT NULL,
    class_id  INT UNSIGNED,
    CONSTRAINT fk_group_class FOREIGN KEY ( class_id ) REFERENCES class( id ) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE OR REPLACE TABLE lesson (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) UNIQUE NOT NULL
);

CREATE OR REPLACE TABLE room (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(200) UNIQUE NOT NULL,
    room_type VARCHAR(200),
    space     SMALLINT UNSIGNED NOT NULL
);

CREATE OR REPLACE TABLE teacher (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(200) NOT NULL,
    job     VARCHAR(200) NOT NULL,
    user_id INT UNSIGNED UNIQUE
);

CREATE OR REPLACE TABLE teacher_lesson (
    teacher_id INT UNSIGNED NOT NULL,
    lesson_id  INT UNSIGNED NOT NULL,
    CONSTRAINT fk_lesson_teacher FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_teacher_lesson FOREIGN KEY (teacher_id) REFERENCES teacher (id) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY (teacher_id, lesson_id)
);

CREATE OR REPLACE TABLE teacher_availability (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id           INT UNSIGNED     NOT NULL,
    available_from_day   TINYINT UNSIGNED NOT NULL,
    available_from_time  TIME             NOT NULL,
    available_until_day  TINYINT UNSIGNED NOT NULL,
    available_until_time TIME             NOT NULL,
    CONSTRAINT fk_teacher_availability FOREIGN KEY ( teacher_id ) REFERENCES teacher( id ) ON DELETE CASCADE ON UPDATE CASCADE
);

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
    CONSTRAINT fk_timetable_group_ FOREIGN KEY ( group_id ) REFERENCES group_( id ) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_timetable_class FOREIGN KEY ( room_id ) REFERENCES room( id ) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_timetable_lesson FOREIGN KEY ( lesson_id ) REFERENCES lesson( id ) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_timetable_teacher FOREIGN KEY ( teacher_id ) REFERENCES teacher( id ) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE OR REPLACE TABLE homework (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    published  DATETIME     NOT NULL DEFAULT NOW(),
    due        DATETIME,
    lesson_id  INT UNSIGNED,
    teacher_id INT UNSIGNED,
    CONSTRAINT fk_homework_lesson FOREIGN KEY ( lesson_id ) REFERENCES lesson( id ) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_homework_teacher FOREIGN KEY ( teacher_id ) REFERENCES teacher( id ) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE OR REPLACE TABLE attachments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    homework_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(200) NOT NULL,
    CONSTRAINT fk_attachment_homework FOREIGN KEY ( homework_id ) REFERENCES homework( id ) ON DELETE CASCADE ON UPDATE CASCADE
);
';

$intezmeny_procedures = '
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

CREATE OR REPLACE PROCEDURE newTimetableElement ( IN in_duration TIME, IN in_day TINYINT UNSIGNED, IN in_from DATE, IN in_until DATE, IN in_group_id INT UNSIGNED, IN in_lesson_id INT UNSIGNED, IN in_teacher_id INT UNSIGNED, IN in_room_id INT UNSIGNED )
BEGIN
    INSERT INTO timetable (duration, day, from_, until, group_id, lesson_id, teacher_id, room_id) VALUES (in_duration, in_day, in_from, in_until, in_group_id, in_lesson_id, in_teacher_id, in_room_id);
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

CREATE OR REPLACE PROCEDURE delAttachment ( IN in_id INT UNSIGNED )
BEGIN
    DELETE FROM attachments WHERE id=in_id;
END;
';
