<?php
/*-----------------------------------------------------
 *
 *----------------------------------------------------- */

// This is list of predefined languages when script is ran.
// We set them manually for test purpose
putenv("SETTINGS_admin_name")='admin';
putenv("SETTINGS_admin_password")='admin-ad';
putenv("BASE_URL_SCHEME")='http';
putenv("BASE_URL_HOST")='localhost';
putenv("BASE_URL_PORT")=80;
putenv("BASE_URL_PATH")='/';
putenv("WEB___DIR")='/var/wwww/dolibarr';
putenv("DB_main_NAME")='dolibarr';
putenv("DB_main_HOST")='localhost';
putenv("DB_main_PORT")='3306';
putenv("DB_main_LOGIN")='root';
putenv("DB_main_PASSWORD")='root';



if(count($_SERVER['argv']) < 2)
{
    print "Usage: configure (install | upgrade <version> | configure | remove)\n";
    exit(1);
}

$command = $_SERVER['argv'][1];

//$command stores the argument with which the script was invoked.

if($command == "install")
{
    $db_id = 'main';

    $rootdir = getenv("WEB___DIR");
    $datadir = getenv("WEB___DIR").'/dolibarr_documents';

    //List of database-related variables that are passed to the configuration
    //script. See the 6.3.1.1.1. Environment variables section of the
    //Specification for details.
    $db_address = getenv("DB_${db_id}_HOST");

    /*if (fetch_env_var("DB_${db_id}_PORT") !== False)
     $db_address .= ':' . fetch_env_var("DB_${db_id}_PORT");
     */

    $dblogin = getenv("DB_${db_id}_LOGIN");
    $dbpassword = getenv("DB_${db_id}_PASSWORD");
    $dbname = getenv("DB_${db_id}_NAME");

    //PHP functions for connecting to the mysql server and
    //executing SQL queries.
    //mysql_connect($dbaddress, $dblogin, $dbpassword);
    //mysql_select_db($dbname);

    /*
     $sql_queries = file($query_file);
     foreach ($sql_queries as $query) mysql_query($query);
     */


    //Other code to be executed on invoking configure with
    //the install argument.

    // Create document directories
    print "Create directory ".$datadir.'/dolibarr_documents'."\n";
    mkdir($datadir.'/dolibarr_documents');

    // Create install.forced.php
    print "Create file ".$rootdir.'/install/install.forced.php'."\n";
    $file_source='build/aps/install.forced.php.install';
    $file=$rootdir.'/install/install.forced.php';

    $modify_hash=array();   // TODO Add substitution here

    $file_content = read_file($file_source);
    foreach($modify_hash as $param => $val){
        $file_content = str_replace($param, php_quote($val), $file_content);
    }
    $fp = fopen($file, 'wb');
    if (!$fp)
    {
        print "Unable to write file $file.\n";
        exit(1);
    }
    fputs($fp, $content, strlen($content));
    fclose($fp);

    exit(0);
}

if($command == "remove")
{
    //Code to be executed on invoking configure with the remove argument
    exit(0);
}

if($command == "upgrade")
{
    //Code to be executed on invoking configure with the upgrade argument
    exit(0);
}

if($command == "configure")
{
    //Code to be executed on invoking configure with the configure argument
    exit(0);
}

print "Error: unknown command $command.\n";
exit(1);
?>