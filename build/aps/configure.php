#!/usr/bin/env php
<?php
/*-----------------------------------------------------
 *
 *----------------------------------------------------- */

// This is list of predefined variables when script is ran
// We have to set them manually to run script outside context.
/*putenv('SETTINGS_admin_name=admin');
putenv('SETTINGS_admin_password=admin-ad');
putenv('BASE_URL_SCHEME=http');
putenv('BASE_URL_HOST=localhost');
putenv('BASE_URL_PORT=0');
putenv('BASE_URL_PATH=/');
//putenv('WEB___DIR=/var/wwww/dolibarr/htdocs');      // WEB___DIR is dir to htdocs
putenv('WEB___DIR=../htdocs');      // WEB___DIR is dir to htdocs
putenv('DB_main_NAME=dolibarr');
putenv('DB_main_HOST=localhost');
putenv('DB_main_PORT=3306');
putenv('DB_main_LOGIN=root');
putenv('DB_main_PASSWORD=root');
*/

// Check parameters
if(count($_SERVER['argv']) < 2)
{
    print "Usage: configure.php (install | upgrade <version> | configure | remove)\n";
    exit(1);
}
$command = $_SERVER['argv'][1]; //$command stores the argument with which the script was invoked.


if($command == "install")
{
    $db_id = 'main';

    $rootdir = getenv("WEB___DIR");
    if ($rootdir != '/') $rootdir = preg_replace('/\/$/','',$rootdir);  // Remove last /
    $datadir = $rootdir.'/dolibarr_documents';

    //List of database-related variables that are passed to the configuration
    //script. See the 6.3.1.1.1. Environment variables section of the
    //Specification for details.
    $db_address = getenv("DB_${db_id}_HOST");
    $db_port = getenv("DB_${db_id}_PORT");
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

    // Create empty config file
    $file=$rootdir.'/conf/conf.php';
    print "Create conf file ".$file."\n";
    $fp = fopen($file, 'wb');
    if ($fp)
    {
        fclose($fp);
        chmod($file,0775);
    }
    else
    {
        print "configure.php install: Unable to write file $file.\n";
        exit(1);
    }

    // Create empty directory that will be used by software to store uploaded documents
    print "Create directory ".$datadir."\n";
    @mkdir($datadir);
    chmod($datadir,0775);

    // Create install.forced.php into htdocs/install directory with value.
    // This will set parameters of install for web installer wizard.
    $file_source=$rootdir.'/../build/aps/install.forced.php.install';
    $file=$rootdir.'/install/install.forced.php';
    print "Create file ".$file.' from '.$file_source."\n";

    $modify_hash=array(
    'WEB___DIR'=>$rootdir,
    'DB_'.$db_id.'_HOST'=>$db_address,
    'DB_'.$db_id.'_PORT'=>$db_port,
    'DB_'.$db_id.'_LOGIN'=>$dblogin,
    'DB_'.$db_id.'_PASSWORD'=>$dbpassword,
    'DB_'.$db_id.'_NAME'=>$dbname
    );

    $file_content = fread(fopen($file_source, 'r'), filesize($file_source));
    foreach($modify_hash as $param => $val){
        $file_content = str_replace($param, php_quote($val), $file_content);
    }
    $fp = fopen($file, 'wb');
    if ($fp)
    {
        fputs($fp, $file_content);
        fputs($fp, "\n");
        fclose($fp);
        chmod($file,0775);
    }
    else
    {
        print "configure.php install: Unable to write file $file.\n";
        exit(2);
    }

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

print "configure.php: Error: unknown command $command.\n";
exit(1);



// Content of file-util.php we need

function php_quote($val)
{
    $res_val = str_replace("\\", "\\\\", $val);
    $res_val = str_replace("'", "\\'", $res_val);
    return $res_val;
}

