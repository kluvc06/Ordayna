-- This allows us to replace the database without encountering foreign key errors
SET FOREIGN_KEY_CHECKS = 0;

CREATE OR REPLACE DATABASE intezmeny CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";

SET FOREIGN_KEY_CHECKS = 1;

CREATE OR REPLACE TABLE intezmeny.class ( 
	id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name                 VARCHAR(200) UNIQUE NOT NULL,
	headcount            SMALLINT UNSIGNED NOT NULL
 ) ENGINE=InnoDB;

CREATE OR REPLACE TABLE intezmeny.group_ ( 
	id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name                 VARCHAR(200) UNIQUE NOT NULL,
	headcount            SMALLINT UNSIGNED NOT NULL,
	class_id             INT UNSIGNED,
	CONSTRAINT fk_group_class FOREIGN KEY ( class_id ) REFERENCES intezmeny.class( id ) ON DELETE SET NULL ON UPDATE NO ACTION
 ) ENGINE=InnoDB;

CREATE OR REPLACE INDEX fk_group_class ON intezmeny.group_ ( class_id );

CREATE OR REPLACE TABLE intezmeny.lesson ( 
	id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name                 VARCHAR(200) UNIQUE NOT NULL   
 ) ENGINE=InnoDB;

CREATE OR REPLACE TABLE intezmeny.room ( 
	id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name                 VARCHAR(200) UNIQUE NOT NULL,
	room_type            VARCHAR(200),
	space                INT UNSIGNED NOT NULL
 ) ENGINE=InnoDB;

CREATE OR REPLACE TABLE intezmeny.teacher ( 
	id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name                 VARCHAR(200) UNIQUE NOT NULL,
	job                  VARCHAR(200) NOT NULL,
	subjects_undertaken  VARCHAR(400), --TODO: connect teacher with lesson via a connecting table
	email                VARCHAR(254),
	phone_number         VARCHAR(15)       
 ) ENGINE=InnoDB;

ALTER TABLE intezmeny.teacher MODIFY email VARCHAR(254) COMMENT 'The max length of a valid email address is technically 320 but you can''t really use that due to the limit of the mailbox being 256 bytes (254 due to it always including a < and > bracket).
https://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address';

ALTER TABLE intezmeny.teacher MODIFY phone_number VARCHAR(15) COMMENT 'The max length of a phone number is 15 digits (not including the "+" sign and any spaces):
https://en.wikipedia.org/wiki/E.164';

CREATE OR REPLACE TABLE intezmeny.teacher_availability ( 
	id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
	teacher_id           INT UNSIGNED     NOT NULL,
	available_from_day   TINYINT UNSIGNED NOT NULL,
	available_from_time  TIME             NOT NULL,
	available_until_day  TINYINT UNSIGNED NOT NULL,
	available_until_time TIME             NOT NULL,
	CONSTRAINT fk_teacher_availability FOREIGN KEY ( teacher_id ) REFERENCES intezmeny.teacher( id ) ON DELETE CASCADE ON UPDATE NO ACTION
 ) ENGINE=InnoDB;

CREATE OR REPLACE INDEX fk_teacher_availability ON intezmeny.teacher_availability ( teacher_id );

ALTER TABLE intezmeny.teacher_availability MODIFY available_from_day TINYINT UNSIGNED NOT NULL COMMENT '0 = monday, ... , 6 = sunday';

ALTER TABLE intezmeny.teacher_availability MODIFY available_until_day TINYINT UNSIGNED NOT NULL COMMENT '0 = monday, ... , 6 = sunday';

CREATE OR REPLACE TABLE intezmeny.timetable ( 
	id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
	duration             TIME          NOT NULL,
	group_id             INT UNSIGNED,
	lesson_id            INT UNSIGNED,
	teacher_id           INT UNSIGNED,
	room_id              INT UNSIGNED,
	CONSTRAINT fk_timetable_group_ FOREIGN KEY ( group_id ) REFERENCES intezmeny.group_( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_timetable_class FOREIGN KEY ( room_id ) REFERENCES intezmeny.room( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_timetable_lesson FOREIGN KEY ( lesson_id ) REFERENCES intezmeny.lesson( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_timetable_teacher FOREIGN KEY ( teacher_id ) REFERENCES intezmeny.teacher( id ) ON DELETE SET NULL ON UPDATE NO ACTION
 ) ENGINE=InnoDB;

CREATE OR REPLACE INDEX fk_timetable_group ON intezmeny.timetable ( group_id );

CREATE OR REPLACE INDEX fk_timetable_class ON intezmeny.timetable ( room_id );

CREATE OR REPLACE INDEX fk_timetable_lesson ON intezmeny.timetable ( lesson_id );

CREATE OR REPlACE INDEX fk_timetable_teacher ON intezmeny.timetable ( teacher_id );

CREATE OR REPLACE TABLE intezmeny.homework (
	id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	published     DATETIME     NOT NULL DEFAULT NOW(),
	due           DATETIME,
	lesson_id     INT UNSIGNED,
	teacher_id    INT UNSIGNED,
	CONSTRAINT fk_homework_lesson FOREIGN KEY ( lesson_id ) REFERENCES intezmeny.lesson( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_homework_teacher FOREIGN KEY ( teacher_id ) REFERENCES intezmeny.teacher( id ) ON DELETE SET NULL ON UPDATE NO ACTION
);

CREATE OR REPLACE TABLE intezmeny.attachments (
	id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	file_name VARCHAR(255) NOT NULL
);

CREATE OR REPLACE TABLE intezmeny.homework_attachments (
	id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	homework_id    INT UNSIGNED NOT NULL,
	attachments_id INT UNSIGNED NOT NULL,
	
	CONSTRAINT fk_join_homework FOREIGN KEY ( homework_id ) REFERENCES intezmeny.homework( id ) ON DELETE CASCADE ON UPDATE NO ACTION,
	CONSTRAINT fk_join_attachment FOREIGN KEY ( attachments_id ) REFERENCES intezmeny.attachments( id ) ON DELETE CASCADE ON UPDATE NO ACTION
);

delimiter //

create procedure newTeacher
(
in in_name VARCHAR(200),
in in_job VARCHAR(200),
in in_subjects_undertaken VARCHAR(200), --TODO: same thing here
in in_email VARCHAR(254),
in in_phone_number VARCHAR(15)
)

BEGIN

INSERT INTO teacher
(name, job, subjects_undertaken, email, phone_number)
VALUES
(in_name, in_job, in_subjects_undertaken,in_email, in_phone_number);

END//

delimiter ;
