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

DELIMITER //
CREATE OR REPLACE PROCEDURE ordayna_main_db.isTeacher(IN in_users_id INT UNSIGNED, IN in_intezmeny_id INT UNSIGNED)
BEGIN
	SELECT COUNT(*) FROM intezmeny_users WHERE intezmeny_id=in_intezmeny_id AND users_id=in_users_id AND (role_="admin" OR role_="teacher");
END;
//
DELIMITER ;

DELIMITER //
CREATE OR REPLACE PROCEDURE ordayna_main_db.isAdmin(IN in_users_id INT UNSIGNED, IN in_intezmeny_id INT UNSIGNED)
BEGIN
	SELECT COUNT(*) FROM intezmeny_users WHERE intezmeny_id=in_intezmeny_id AND users_id=in_users_id AND role_="admin";
END;
//
DELIMITER ;
