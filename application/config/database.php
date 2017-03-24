<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

/** switch database  */
/* switch ($_SERVER['SERVER_NAME']) {
    case 'm.pizzaboy.bywmds.us':
            $active_group = 'default';
            $active_record = TRUE;

            $db['default']['hostname'] = 'localhost';
            $db['default']['username'] = 'pizzaboy_app';
            $db['default']['password'] = '123qwe';
            $db['default']['database'] = 'pizzaboy_app';
            $db['default']['dbdriver'] = 'mysql';
            $db['default']['dbprefix'] = '';
        break;

    case 'm.bluestarpizza.com.au':

        $active_group = 'default';
        $active_record = TRUE;

        $db['default']['hostname'] = '27.50.86.214';
        $db['default']['username'] = 'radu2';
        $db['default']['password'] = 'pizzaboy321';
        $db['default']['database'] = 'pizzaboy_dev';
        $db['default']['dbdriver'] = 'mysql';
        $db['default']['dbprefix'] = '';
        break;

    case 'exemple.com':

        $active_group = 'default';
        $active_record = TRUE;

        $db['default']['hostname'] = 'localhost';
        $db['default']['username'] = 'demo_username';
        $db['default']['password'] = 'demo_pass';
        $db['default']['database'] = 'demo_database';
        $db['default']['dbdriver'] = 'demo_mysql';
        $db['default']['dbprefix'] = 'demo_prefix';
        break;

} */

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'zuzdb11';
$db['default']['username'] = 'user_for_work';
$db['default']['password'] = 'password';
$db['default']['database'] = 'bluestar';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';

$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;




/* End of file database.php */
/* Location: ./application/config/database.php */