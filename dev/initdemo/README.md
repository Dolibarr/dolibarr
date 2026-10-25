README
======

The scripts in this directory can be used to reload or save a demo database.
The package `dialog` is required.


Init demo
-------------

The script `initdemo.sh` will erase the current database with data from `dev/initdemo/mysqldump_dolibarr_x.y.z.sql` and copy files from `documents_demo` to the official document directory.

You may need to execute `chmod 700 initdemo.sh`
then run `./initdemo.sh` to launch the Graphical User Interface.

After loading the demo files, the admin login may be one of the following:
- admin / admin
or
- admin / adminadmin


Update demo
-------------

The goal of the script `dev/initdemo/updatedemo.php` is to update the dates in the demo data so that samples are up to date.


Save demo
-------------

The script `dev/initdemo.savedemo.sh` will save the current database into a database dump file.
