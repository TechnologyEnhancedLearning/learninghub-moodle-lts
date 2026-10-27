<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'sqlsrv';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'DB-HOST-NAME';
$CFG->dbname    = 'DB-NAME';
$CFG->dbuser    = 'DB-USER-NAME';
$CFG->dbpass    = 'DB-USER-PASSWORD';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => true,
  'dbport' => 1433,
  'dbsocket' => false,
  'dbcollation' => 'SQL_Latin1_General_CP1_CI_AS'
);

$CFG->wwwroot   = 'WWW-ROOT';
$CFG->dataroot  = 'DATA-ROOT';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;
$logouturl = $CFG->logouturl = 'index.php';

$CFG->tool_generator_users_password = 'TOOL-GENERATOR-PASSWORD';

$CFG->sslproxy = true;

$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = 'phpu_moodledata';

$CFG->recordrtc_audio = false;
$CFG->recordrtc_video = false;

$CFG->disableupdateautodeploy = true;

$CFG->bigbluebuttonbn_server_url = 'BBB-SERVER-URL';
$CFG->bigbluebuttonbn_shared_secret = 'BBB-SHARED-SECRET';

//$CFG->debug = (E_ALL | E_STRICT);     // Full Moodle developer debugging
//$CFG->debugdisplay = true;            // Display debug messages

// Disable DB sessions to let Redis handle them
$CFG->dbsessions = false;

// Configure Redis Session Driver
$CFG->session_handler_class = '\core\session\redis';
$CFG->session_redis_host = 'REDIS_NAME.redis.cache.windows.net';
$CFG->session_redis_port = 6380; // Or 6380 for SSL

// Tell Moodle to use TLS encryption explicitly
$CFG->session_redis_encrypt = ['verify_peer' => true]; 

$CFG->session_redis_auth = 'REDIS_PRIMARY_ACCESS_KEY'; // Leave blank if unauthenticated in dev
$CFG->session_redis_database = 0; // Default database index
$CFG->session_redis_prefix = 'moodle_sess_'; // Unique prefix for your cluster

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
