To test events the following command might have to be run:
SET GLOBAL event_scheduler = ON;

To make this option persist even when mariadb is restarted add the lines below to the end of /etc/mysql/mariadb.cnf
On windows add it to my.cnf or my.ini in the root folder of mariadb (Where mysqld.exe is located)
More info at: https://mariadb.com/docs/server/server-management/install-and-upgrade-mariadb/configuring-mariadb/configuring-mariadb-with-option-files
[mysqld]
event_scheduler = on
