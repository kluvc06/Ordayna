DELIMITER //
CREATE OR REPLACE PROCEDURE ordayna_main_db.detach_intezmeny(
	intezmeny_to_detach INT UNSIGNED
) BEGIN
	DELETE FROM ordayna_main_db.intezmeny
	WHERE intezmeny.id = intezmeny_to_detach
	;
END;
//
DELIMITER ;

DELIMITER //
CREATE OR REPLACE PROCEDURE ordayna_main_db.getOrphanedIntezmenys()
BEGIN
	SELECT intezmeny.id
	FROM intezmeny
	LEFT JOIN intezmeny_users
	ON intezmeny.id = intezmeny_users.intezmeny_id
	GROUP BY intezmeny.id
	HAVING COUNT(IF(role_="admin",1,NULL))=0;
END;
//
DELIMITER ;
