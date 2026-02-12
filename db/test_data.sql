-- The password for this password hash is "tester_pass"
INSERT INTO ordayna_main_db.users (id, display_name, email, phone_number, password_hash) VALUE (200000, "asd", "asd@asd.com", "36301234567", "$2a$10$lHTqGEfVCdw1J22lcgeYneqZlOSNbbk6rjilNGgb1hyLzGjtCwR2y");

INSERT INTO ordayna_main_db.intezmeny (id, name) VALUE (300000, "tester_intezmeny_with_no_admin");

INSERT INTO ordayna_main_db.intezmeny_users (intezmeny_id, users_id, role_, invite_accepted) VALUE (300000, 200000, "student", TRUE);

INSERT INTO ordayna_main_db.intezmeny (id, name) VALUE (400000, "tester_intezmeny_with_admin");

INSERT INTO ordayna_main_db.intezmeny_users (intezmeny_id, users_id, role_, invite_accepted) VALUE (400000, 200000, "admin", TRUE);

INSERT INTO ordayna_main_db.revoked_refresh_tokens (uuid, duration) VALUE (UUID_v4(), '0 0:0:30');
