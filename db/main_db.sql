-- This allows us to replace the database without encountering foreign key errors
SET FOREIGN_KEY_CHECKS = 0;

CREATE OR REPLACE DATABASE ordayna_main_db CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";

SET FOREIGN_KEY_CHECKS = 1;

-- If you delete an intezmeny delete the intezmeny's id from this table
CREATE OR REPLACE TABLE ordayna_main_db.intezmeny_ids ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	intezmeny_id         INT UNSIGNED   NOT NULL   
 ) ENGINE = InnoDB;

DELIMITER //
CREATE OR REPLACE PROCEDURE ordayna_main_db.detach_intezmeny(
	intezmeny_id_to_detach INT UNSIGNED
) BEGIN
	DELETE FROM foadatbazis.intezmeny_ids
	WHERE intezmeny_ids.intezmeny_id = intezmeny_id_to_detach
	;
END;
//
DELIMITER ;

CREATE OR REPLACE TABLE ordayna_main_db.users ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	display_name         VARCHAR(200)   NOT NULL,
	email                VARCHAR(254)   UNIQUE NOT NULL,
	phone_number         VARCHAR(15),
	password_hash        BINARY(60)     NOT NULL
 ) ENGINE = InnoDB;

CREATE OR REPLACE TABLE ordayna_main_db.intezmeny_ids_users (
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	intezmeny_ids_id     INT UNSIGNED   NOT NULL   ,
	users_id             INT UNSIGNED   NOT NULL   ,
	CONSTRAINT fk_intezmeny_ids_users FOREIGN KEY ( intezmeny_ids_id ) REFERENCES ordayna_main_db.intezmeny_ids( id ) ON DELETE CASCADE ON UPDATE NO ACTION,
	CONSTRAINT fk_intezmeny_ids_users_users FOREIGN KEY ( users_id ) REFERENCES ordayna_main_db.users( id ) ON DELETE CASCADE ON UPDATE NO ACTION
 ) ENGINE = InnoDB;

CREATE OR REPLACE USER ordayna_main;

GRANT ALL ON ordayna_main_db.* TO ordayna_main;
