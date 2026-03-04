-- This allows us to replace the database without encountering foreign key errors
SET FOREIGN_KEY_CHECKS = 0;

CREATE OR REPLACE DATABASE ordayna_main_db CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";

SET FOREIGN_KEY_CHECKS = 1;

USE ordayna_main_db;

-- If you delete an intezmeny delete the intezmeny's id from this table
CREATE OR REPLACE TABLE intezmeny ( 
	id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(200) NOT NULL
 );

CREATE OR REPLACE TABLE users ( 
	id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	display_name VARCHAR(200) NOT NULL,
	email VARCHAR(254) UNIQUE NOT NULL,
	phone_number VARCHAR(15),
	password_hash VARCHAR(255) NOT NULL
 );

CREATE OR REPLACE TABLE intezmeny_users (
	intezmeny_id INT UNSIGNED NOT NULL,
	users_id INT UNSIGNED NOT NULL,
	role_ ENUM("student","teacher","admin") NOT NULL,
	invite_accepted BOOLEAN NOT NULL,
	CONSTRAINT FOREIGN KEY ( intezmeny_id ) REFERENCES intezmeny( id ) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT FOREIGN KEY ( users_id ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (intezmeny_id, users_id)
 );

CREATE OR REPLACE TABLE tokens (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	uid INT UNSIGNED NOT NULL,
	token_uuid UUID NOT NULL,
	expires_after DATETIME NOT NULL,
	is_revoked BOOLEAN NOT NULL DEFAULT FALSE,
	CONSTRAINT FOREIGN KEY ( uid ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE
 );

CREATE OR REPLACE EVENT token_cleanup
  ON SCHEDULE EVERY 5 MINUTE DO
    DELETE FROM tokens
    WHERE expires_after < UTC_TIMESTAMP();
