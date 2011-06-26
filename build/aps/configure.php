<?php
/*-----------------------------------------------------
 *
 *----------------------------------------------------- */

// This is list of predefined languages when script is ran:
// getenv("SETTINGS_admin_name")
// getenv("SETTINGS_admin_password")
// getenv("BASE_URL_SCHEME")
// getenv("BASE_URL_HOST")
// getenv("BASE_URL_PORT")
// getenv("BASE_URL_PATH")
// getenv("DB_main_NAME")
// getenv("DB_main_LOGIN")
// getenv("DB_main_PASSWORD")
// getenv("DB_main_HOST")
// getenv("DB_main_PORT")
// getenv("DB_main_HOST")


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


//The database identifier value is to be substituted by the value
//defined in the application requirements section of the
//metadata file.For details, see the 6.3.1.1. Database requirement
//type section of the Specification.

$query_file = 'schema.sql'; //File containing list of SQL queries.

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
mysql_connect($dbaddress, $dblogin, $dbpassword);
mysql_select_db($dbname);

/*
$sql_queries = file($query_file);
foreach ($sql_queries as $query) mysql_query($query);
*/


//Other code to be executed on invoking configure with
//the install argument.

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