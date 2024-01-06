README
======

Scripts in this directory can be used to reload or save a demo database.
Install of package "dialog" is required.


Init demo
-------------

The script initdemo.sh will erase current database with data intodev/initdemo/mysqldump_dolibarr_x.y.z.sql and copy files into documents_demo into officiel document directory.

Do a chmod 700 initdemo.sh
then run ./initdemo.sh to launch Graphic User Interface.

After loading the demo files, admin login may be:
- admin / admin
or
- admin / adminadmin


Update demo
-------------

The goal of script dev/initdemo/updatedemo.php is to update dates into the demo data so samples are up to date.


Save demo
-------------

The script dev/initdemo.savedemo.sh will save current database into a database dump file.

