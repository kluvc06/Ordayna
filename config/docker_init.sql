SOURCE /db/main_db.sql;
USE ordayna_main_db;
SOURCE /db/main_db_procedures.sql;
SOURCE /db/test_data.sql;

CREATE OR REPLACE USER ordayna_main IDENTIFIED BY "very secret";
GRANT ALL ON *.* TO ordayna_main;
