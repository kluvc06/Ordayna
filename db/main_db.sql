-- This allows us to replace the database without encountering foreign key errors
SET FOREIGN_KEY_CHECKS = 0;

CREATE OR REPLACE DATABASE ordayna_main_db CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";

SET FOREIGN_KEY_CHECKS = 1;

USE ordayna_main_db;

-- If you delete an intezmeny delete the intezmeny's id from this table
CREATE OR REPLACE TABLE intezmeny ( 
	id   INT UNSIGNED NOT NULL PRIMARY KEY,
	name VARCHAR(200) NOT NULL
 );

DELIMITER //
CREATE OR REPLACE PROCEDURE detach_intezmeny(
	intezmeny_id_to_detach INT UNSIGNED
) BEGIN
	DELETE FROM foadatbazis.intezmeny_ids
	WHERE intezmeny_ids.id = intezmeny_id_to_detach
	;
END;
//
DELIMITER ;

CREATE OR REPLACE TABLE users ( 
	id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	display_name  VARCHAR(200) NOT NULL,
	email         VARCHAR(254) UNIQUE NOT NULL,
	phone_number  VARCHAR(15),
	password_hash BINARY(60) NOT NULL
 );

CREATE OR REPLACE TABLE intezmeny_users (
	id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	intezmeny_id INT UNSIGNED NOT NULL,
	users_id     INT UNSIGNED NOT NULL,
	is_admin     BOOLEAN NOT NULL,
	CONSTRAINT fk_intezmeny_users FOREIGN KEY ( intezmeny_id ) REFERENCES intezmeny( id ) ON DELETE CASCADE ON UPDATE NO ACTION,
	CONSTRAINT fk_intezmeny_users_users FOREIGN KEY ( users_id ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE NO ACTION
 );

CREATE OR REPLACE TABLE revoked_refresh_tokens (
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	uuid                 UUID           NOT NULL,
	created_at           DATETIME       NOT NULL DEFAULT current_timestamp(),
	duration             TIME           NOT NULL
 ) ENGINE=InnoDB;

CREATE EVENT remove_token
  ON SCHEDULE EVERY 5 MINUTE DO 
   DELETE FROM revoked_refresh_tokens
	WHERE ADDTIME(created_at, duration)<current_timestamp();


CREATE OR REPLACE USER ordayna_main;

GRANT ALL ON *.* TO ordayna_main;
