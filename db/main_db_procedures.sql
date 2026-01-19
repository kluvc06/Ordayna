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

DELIMITER //
CREATE OR REPLACE PROCEDURE ordayna_main_db.getOrphanedIntezmenys()
BEGIN
	SELECT intezmeny_id
	FROM intezmeny_ids
	LEFT JOIN intezmeny_ids_users
	ON intezmeny_id=intezmeny_ids_id
	GROUP BY intezmeny_id
	HAVING COUNT(IF(is_admin=1,1,NULL))=0;
END;
//
DELIMITER ;
