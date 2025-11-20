-- This allows us to replace the database without encountering foreign key errors
SET FOREIGN_KEY_CHECKS = 0;

CREATE OR REPLACE DATABASE foadatbazis;

SET FOREIGN_KEY_CHECKS = 1;

-- If you delete an intezmeny delete the intezmeny's id from this table
CREATE OR REPLACE TABLE foadatbazis.intezmeny_ids ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	intezmeny_id         INT UNSIGNED   NOT NULL   
 ) engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DELIMITER //
CREATE OR REPLACE PROCEDURE foadatbazis.detach_intezmeny(
	intezmeny_id_to_detach INT UNSIGNED
) BEGIN
	DELETE FROM foadatbazis.intezmeny_ids
	WHERE intezmeny_ids.intezmeny_id = intezmeny_id_to_detach
	;
END;
//
DELIMITER ;

CREATE OR REPLACE TABLE foadatbazis.users ( 
	id                   INT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	display_name         VARCHAR(200)    NOT NULL   ,
	email                VARCHAR(254)   UNIQUE NOT NULL   ,
	password_hash        CHAR(128)    NOT NULL   ,
	salt                 CHAR(80) NOT NULL,
	CONSTRAINT users_email UNIQUE ( email ) 
 ) engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE OR REPLACE TABLE foadatbazis.intezmeny_ids_users ( 
	intezmeny_ids_id     INT UNSIGNED   NOT NULL   ,
	users_id             INT UNSIGNED   NOT NULL   ,
	CONSTRAINT fk_intezmeny_ids_users FOREIGN KEY ( intezmeny_ids_id ) REFERENCES foadatbazis.intezmeny_ids( id ) ON DELETE CASCADE ON UPDATE NO ACTION,
	CONSTRAINT fk_intezmeny_ids_users_users FOREIGN KEY ( users_id ) REFERENCES foadatbazis.users( id ) ON DELETE CASCADE ON UPDATE NO ACTION
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
