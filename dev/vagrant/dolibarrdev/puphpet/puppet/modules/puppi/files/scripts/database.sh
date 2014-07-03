#!/bin/bash
# database.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script executes database queries and dumps"
    echo "It integrates and uses variables provided by other core Puppi scripts"
    echo "It has the following options:"
    echo "-t <database_type> The database type. Currently only mysql is suppoerted"
    echo "-a <action> The action to perform:"
    echo "            run - Run a mysql command based on a given .sql file"
    echo "            dump - Dump the specified database in the archive dir"
    echo "            restore - Restore the specified database from the archive dir"
    echo "-d <database_name> The database name to manage"
    echo "-u <database_user> The database user used to run the queries"
    echo "-p <database_password> The user password"
    echo "-h <host> The database server hostname"
}

# Arguments defaults
db_type=mysql
db_action=run
db_user=root
db_host=localhost
db_password=""

# Check Arguments
while [ $# -gt 0 ]; do
  case "$1" in
    -t)
      db_type=$2
      shift 2 ;;
    -a)
      db_action=$2
      shift 2 ;;
    -d)
      db_name=$2
      shift 2 ;;
    -u)
      db_user=$2
      shift 2 ;;
    -p)
      db_password=$2
      shift 2 ;;
    -h)
      db_host=$2
      shift 2 ;;
  esac
done



mysql_run () {
    case "$db_action" in
      run)
        file $downloadedfile | grep gzip &>/dev/null 2>&1 && sqlfile_type="gzip"
        file $downloadedfile | grep Zip &>/dev/null 2>&1 && sqlfile_type="zip"
        case "$sqlfile_type" in
          gzip)
            zcat $downloadedfile | mysql -u $db_user -p$db_password -h $db_host $db_name
            check_retcode ;;
          zip)
            unzip -p $downloadedfile | mysql -u $db_user -p$db_password -h $db_host $db_name
            check_retcode ;;
          *)
            mysql -u $db_user -p$db_password -h $db_host $db_name < $downloadedfile
            check_retcode ;;
        esac
        ;;
      dump)
        mkdir -p $archivedir/$project/$tag
        if [ $archivedir/$project/latest ] ; then
            rm -f $archivedir/$project/latest
        fi
        ln -sf $archivedir/$project/$tag $archivedir/$project/latest

        mysqldump -u $db_user -p$db_password -h $db_host --add-drop-table --databases $db_name | gzip > $archivedir/$project/$tag/$db_name.sql.gz
        check_retcode ;;
      restore)
        zcat $archivedir/$project/$rollbackversion/$db_name.sql.gz | mysql -u $db_user -p$db_password -h $db_host $db_name
        check_retcode ;;
    esac
}

case "$db_type" in
      mysql)
        mysql_run
        ;;
      *)
        showhelp
        ;;
esac

