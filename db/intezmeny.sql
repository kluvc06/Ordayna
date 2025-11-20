CREATE OR REPLACE SCHEMA intezmeny_terv intezmeny_terv;

CREATE OR REPLACE TABLE intezmeny_terv.class ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	name                 VARCHAR(200)    NOT NULL   ,
	headcount            SMALLINT UNSIGNED   NOT NULL   ,
	CONSTRAINT name UNIQUE ( name ) 
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE TABLE intezmeny_terv.group_ ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	name                 VARCHAR(200)    NOT NULL   ,
	headcount            SMALLINT UNSIGNED   NOT NULL   ,
	class_id             INT UNSIGNED      ,
	CONSTRAINT name UNIQUE ( name ) ,
	CONSTRAINT fk_group_class FOREIGN KEY ( class_id ) REFERENCES intezmeny_terv.class( id ) ON DELETE SET NULL ON UPDATE NO ACTION
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE INDEX fk_group_class ON intezmeny_terv.group_ ( class_id );

CREATE OR REPLACE TABLE intezmeny_terv.lesson ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	name                 VARCHAR(200)    NOT NULL   ,
	CONSTRAINT lesson_name UNIQUE ( name ) 
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE TABLE intezmeny_terv.room ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	name                 VARCHAR(200)    NOT NULL   ,
	room_type            VARCHAR(200)       ,
	space                INT UNSIGNED   NOT NULL   ,
	CONSTRAINT name UNIQUE ( name ) 
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE TABLE intezmeny_terv.teacher ( 
	id                   INT UNSIGNED   NOT NULL   PRIMARY KEY,
	name                 VARCHAR(200)    NOT NULL   ,
	job                  VARCHAR(200)    NOT NULL   ,
	subjects_undertaken  VARCHAR(400)       ,
	email                VARCHAR(254)       ,
	phone_number         VARCHAR(15)       ,
	CONSTRAINT name UNIQUE ( name ) 
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

ALTER TABLE intezmeny_terv.teacher MODIFY email VARCHAR(254)     COMMENT 'The max length of a valid email address is technically 320 but you can''t really use that due to the limit of the mailbox being 256 bytes (254 due to it always including a < and > bracket).
https://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address';

ALTER TABLE intezmeny_terv.teacher MODIFY phone_number VARCHAR(15)     COMMENT 'The max length of a phone number is 15 digits (not including the "+" sign and any spaces):
https://en.wikipedia.org/wiki/E.164';

CREATE OR REPLACE TABLE intezmeny_terv.teacher_availability ( 
	id                   INT UNSIGNED   NOT NULL   PRIMARY KEY,
	teacher_id           INT UNSIGNED   NOT NULL   ,
	available_from_day   TINYINT UNSIGNED   NOT NULL   ,
	available_from_time  TIME    NOT NULL   ,
	available_until_day  TINYINT UNSIGNED   NOT NULL   ,
	available_until_time TIME    NOT NULL   ,
	CONSTRAINT fk_teacher_availability FOREIGN KEY ( teacher_id ) REFERENCES intezmeny_terv.teacher( id ) ON DELETE CASCADE ON UPDATE NO ACTION
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE INDEX fk_teacher_availability ON intezmeny_terv.teacher_availability ( teacher_id );

ALTER TABLE intezmeny_terv.teacher_availability MODIFY available_from_day TINYINT UNSIGNED NOT NULL   COMMENT '0 = monday, ... , 6 = sunday';

ALTER TABLE intezmeny_terv.teacher_availability MODIFY available_until_day TINYINT UNSIGNED NOT NULL   COMMENT '0 = monday, ... , 6 = sunday';

CREATE OR REPLACE TABLE intezmeny_terv.timetable ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	duration             TIME    NOT NULL   ,
	group_id             INT UNSIGNED   NOT NULL   ,
	lesson_id            INT UNSIGNED   NOT NULL   ,
	teacher_id           INT UNSIGNED   NOT NULL   ,
	room_id              INT UNSIGNED   NOT NULL   ,
	CONSTRAINT fk_timetable_group_ FOREIGN KEY ( group_id ) REFERENCES intezmeny_terv.group_( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_timetable_class FOREIGN KEY ( room_id ) REFERENCES intezmeny_terv.room( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_timetable_lesson FOREIGN KEY ( lesson_id ) REFERENCES intezmeny_terv.lesson( id ) ON DELETE SET NULL ON UPDATE NO ACTION,
	CONSTRAINT fk_timetable_teacher FOREIGN KEY ( teacher_id ) REFERENCES intezmeny_terv.teacher( id ) ON DELETE SET NULL ON UPDATE NO ACTION
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE INDEX fk_timetable_group_ ON intezmeny_terv.timetable ( group_id );

CREATE OR REPLACE INDEX fk_timetable_class ON intezmeny_terv.timetable ( room_id );

CREATE OR REPLACE INDEX fk_timetable_lesson ON intezmeny_terv.timetable ( lesson_id );

CREATE OR REPlACE INDEX fk_timetable_teacher ON intezmeny_terv.timetable ( teacher_id );
