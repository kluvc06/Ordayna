INSERT INTO ordayna_main_db.intezmeny_ids (intezmeny_id) VALUE (1);

INSERT INTO ordayna_main_db.users (display_name, email, phone_number, password_hash) VALUE ("asd", "asd@asd.com", "36301234567", "sfdjglkesdjlgnjksfdhsfdjglkesdjlgnjksfdhsfdjglkesdjlgnjksfdh");

INSERT INTO ordayna_main_db.intezmeny_ids_users (intezmeny_ids_id, users_id) VALUE (1, 1);

INSERT INTO ordayna_main_db.revoked_refresh_tokens (uuid, duration) VALUE (UUID_v4(), '0 0:0:30');
