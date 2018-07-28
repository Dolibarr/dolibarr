<?php
/**
 * If Ececution was stopped by calling exit();
 * php does not append append.php, so no code coverage date is collected
 * We have to add shutdown handler to append this file manualy.
 * @author Arbuzov <info@whitediver.com>
 *
 */
class PHPUnit_Extensions_SeleniumCommon_ExitHandler
{
    /**
     * Register handler.
     * If project have own shutdown hanldler user have to add function to handler
     *
     */
    public static function init()
    {
        register_shutdown_function( array( 'PHPUnit_Extensions_SeleniumCommon_ExitHandler', 'handle' ) );
    }

    /**
     * Manual include apendable files
     */
    public static function handle()
    {
        $execFile = ini_get('auto_append_file');
        if ($execFile!=='') {
            include_once ($execFile);
        }
    }
}
