-- procedures

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newClass (
IN in_name VARCHAR(200),
IN in_headcount SMALLINT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.class
(name, headcount)
VALUES
(in_name, in_headcount);

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getClassByName (
IN in_name VARCHAR(200)
 )

BEGIN

SELECT * FROM intezmeny.class WHERE name=in_name;

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getClassById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.class WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modClass (
IN in_id INT UNSIGNED,
IN in_name VARCHAR(200),
IN in_headcount SMALLINT UNSIGNED
)

BEGIN

UPDATE intezmeny.class SET
name=in_name,
headcount=in_headcount
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delClass (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.class WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newGroup_ (
IN in_name VARCHAR(200),
IN in_headcount SMALLINT UNSIGNED,
IN in_class_id INT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.group_
(name, headcount, class_id)
VALUES
(in_name, in_headcount, in_class_id);

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getGroup_ByName (
IN in_name VARCHAR(200)
 )

BEGIN

SELECT * FROM intezmeny.group_ WHERE name=in_name;

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getGroup_ById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.group_ WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modGroup_ (
IN in_id INT UNSIGNED,
IN in_name VARCHAR(200),
IN in_headcount SMALLINT UNSIGNED,
IN in_class_id INT UNSIGNED
)

BEGIN

UPDATE intezmeny.group_ SET
name=in_name,
headcount=in_headcount,
class_id=in_class_id
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delGroup_ (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.group_ WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newLesson (
IN in_name VARCHAR(200)
)

BEGIN

INSERT INTO intezmeny.lesson
(name)
VALUES
(in_name);

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getLessonByName (
IN in_name VARCHAR(200)
 )

BEGIN

SELECT * FROM intezmeny.lesson WHERE name=in_name;

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getLessonById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.lesson WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modLesson (
IN in_id INT UNSIGNED,
IN in_name VARCHAR(200)
)

BEGIN

UPDATE intezmeny.lesson SET
name=in_name
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delLesson (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.lesson WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newRoom (
IN in_name VARCHAR(200),
IN in_room_type VARCHAR(200),
IN in_space INT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.room
(name, room_type, space)
VALUES
(in_name, in_room_type, in_space);

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getRoomByName (
IN in_name VARCHAR(200)
 )

BEGIN

SELECT * FROM intezmeny.room WHERE name=in_name;

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getRoomById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.room WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modRoom (
IN in_id INT UNSIGNED,
IN in_name VARCHAR(200),
IN in_room_type VARCHAR(200),
IN in_space INT UNSIGNED
)

BEGIN

UPDATE intezmeny.room SET
name=in_name,
room_type=in_room_type,
space=in_space
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delRoom (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.room WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newTeacher (
IN in_name VARCHAR(200),
IN in_job VARCHAR(200),
IN in_email VARCHAR(254),
IN in_phone_number VARCHAR(15)
)

BEGIN

INSERT INTO intezmeny.teacher
(name, job, email, phone_number)
VALUES
(in_name, in_job, in_email, in_phone_number);

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getTeacherByName (
IN in_name VARCHAR(200)
 )

BEGIN

SELECT * FROM intezmeny.teacher WHERE name=in_name;

END//

DELIMITER ;

DELIMITER //

CREATE PROCEDURE intezmeny.getTeacherById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.teacher WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modTeacher (
IN in_id INT UNSIGNED,
IN in_name VARCHAR(200),
IN in_job VARCHAR(200),
IN in_email VARCHAR(254),
IN in_phone_number VARCHAR(15)
)

BEGIN

UPDATE intezmeny.teacher SET
name=in_name,
job=in_job,
email=in_email,
phone_number=in_phone_number
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delTeacher (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.teacher WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newTeacher_lesson (
IN in_teacher_id INT UNSIGNED,
IN in_lesson_id INT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.teacher_lesson
(teacher_id, lesson_id)
VALUES
(in_teacher_id, in_lesson_id);

END//

DELIMITER ;



DELIMITER //

CREATE PROCEDURE intezmeny.getTeacher_lessonById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.teacher_lesson WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modTeacher_lesson (
IN in_id INT UNSIGNED,
IN in_teacher_id INT UNSIGNED,
IN in_lesson_id INT UNSIGNED
)

BEGIN

UPDATE intezmeny.teacher_lesson SET
teacher_id=in_teacher_id,
lesson_id=in_lesson_id
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delTeacher_lesson (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.teacher_lesson WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newTeacher_availability (
IN in_teacher_id INT UNSIGNED,
IN in_available_from_day TINYINT UNSIGNED,
IN in_available_from_time TIME,
IN in_available_until_day TINYINT UNSIGNED,
IN in_available_until_time TIME
)

BEGIN

INSERT INTO intezmeny.teacher_availability
(teacher_id, available_from_day, available_from_time, available_until_day, available_until_time)
VALUES
(in_teacher_id, in_available_from_day, in_available_from_time, in_available_until_day, in_available_until_time);

END//

DELIMITER ;



DELIMITER //

CREATE PROCEDURE intezmeny.getTeacher_availabilityById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.teacher_availability WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modTeacher_availability (
IN in_id INT UNSIGNED,
IN in_teacher_id INT UNSIGNED,
IN in_available_from_day TINYINT UNSIGNED,
IN in_available_from_time TIME,
IN in_available_until_day TINYINT UNSIGNED,
IN in_available_until_time TIME
)

BEGIN

UPDATE intezmeny.teacher_availability SET
teacher_id=in_teacher_id,
available_from_day=in_available_from_day,
available_from_time=in_available_from_time,
available_until_day=in_available_until_day,
available_until_time=in_available_until_time
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delTeacher_availability (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.teacher_availability WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newTimetable (
IN in_duration TIME,
IN in_group_id INT UNSIGNED,
IN in_lesson_id INT UNSIGNED,
IN in_teacher_id INT UNSIGNED,
IN in_room_id INT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.timetable
(duration, group_id, lesson_id, teacher_id, room_id)
VALUES
(in_duration, in_group_id, in_lesson_id, in_teacher_id, in_room_id);

END//

DELIMITER ;



DELIMITER //

CREATE PROCEDURE intezmeny.getTimetableById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.timetable WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modTimetable (
IN in_id INT UNSIGNED,
IN in_duration TIME,
IN in_group_id INT UNSIGNED,
IN in_lesson_id INT UNSIGNED,
IN in_teacher_id INT UNSIGNED,
IN in_room_id INT UNSIGNED
)

BEGIN

UPDATE intezmeny.timetable SET
duration=in_duration,
group_id=in_group_id,
lesson_id=in_lesson_id,
teacher_id=in_teacher_id,
room_id=in_room_id
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delTimetable (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.timetable WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newHomework (
IN in_due DATETIME,
IN in_lesson_id INT UNSIGNED,
IN in_teacher_id INT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.homework
(due, lesson_id, teacher_id)
VALUES
(in_due, in_lesson_id, in_teacher_id);

END//

DELIMITER ;



DELIMITER //

CREATE PROCEDURE intezmeny.getHomeworkById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.homework WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modHomework (
IN in_id INT UNSIGNED,
IN in_due DATETIME,
IN in_lesson_id INT UNSIGNED,
IN in_teacher_id INT UNSIGNED
)

BEGIN

UPDATE intezmeny.homework SET
due=in_due,
lesson_id=in_lesson_id,
teacher_id=in_teacher_id
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delHomework (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.homework WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newAttachments (
IN in_file_name VARCHAR(255)
)

BEGIN

INSERT INTO intezmeny.attachments
(file_name)
VALUES
(in_file_name);

END//

DELIMITER ;



DELIMITER //

CREATE PROCEDURE intezmeny.getAttachmentsById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.attachments WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modAttachments (
IN in_id INT UNSIGNED,
IN in_file_name VARCHAR(255)
)

BEGIN

UPDATE intezmeny.attachments SET
file_name=in_file_name
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delAttachments (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.attachments WHERE id=in_id;

END//

DELIMITER ;


DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.newHomework_attachments (
IN in_homework_id INT UNSIGNED,
IN in_attachments_id INT UNSIGNED
)

BEGIN

INSERT INTO intezmeny.homework_attachments
(homework_id, attachments_id)
VALUES
(in_homework_id, in_attachments_id);

END//

DELIMITER ;



DELIMITER //

CREATE PROCEDURE intezmeny.getHomework_attachmentsById (
IN in_id INT UNSIGNED
 )

BEGIN

SELECT * FROM intezmeny.homework_attachments WHERE id=in_id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.modHomework_attachments (
IN in_id INT UNSIGNED,
IN in_homework_id INT UNSIGNED,
IN in_attachments_id INT UNSIGNED
)

BEGIN

UPDATE intezmeny.homework_attachments SET
homework_id=in_homework_id,
attachments_id=in_attachments_id
WHERE in_id=id;

END//

DELIMITER ;

DELIMITER //

CREATE OR REPLACE PROCEDURE intezmeny.delHomework_attachments (
IN in_id INT UNSIGNED
)

BEGIN

DELETE FROM intezmeny.homework_attachments WHERE id=in_id;

END//

DELIMITER ;


