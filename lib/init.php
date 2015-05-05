<?php

ini_set('date.timezone', 'America/Toronto');

define('CONFIG_DIR', dirname(__FILE__) . '/config/');
define('LIB', dirname(__FILE__));
define('WEB_DOC_ROOT', dirname(dirname(__FILE__)));

require_once LIB . '/config.inc.php';
require_once LIB . '/Smarty/Smarty.class.php';
require_once LIB . '/phpFlickr/phpFlickr.php';
//require_once 'Cache/Cache.class.php';
//require_once 'Cache/Cache_Filecache.class.php';

// Smarty has it's own autoloader.
spl_autoload_register(function($class) {
    // project-specific namespace prefix
    $prefix = 'Abraham\\TwitterOAuth\\';

    // base directory for the namespace prefix
    $base_dir = LIB . '/TwitterOAuth/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        
        //include LIB . '/' . $class . '.class.php';
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

spl_autoload_register(function($class) {
	include LIB . '/' . $class . '.class.php';
});

$TPL = new Template();
$TPL->setupWebEnvironment();

$page = (!empty($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
$script = basename($_SERVER['SCRIPT_FILENAME']);

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	define('IS_AJAX_REQUEST', true);
} else {
	define('IS_AJAX_REQUEST', false);
}

if (!isset($_REQUEST['do'])) { $_REQUEST['do'] = ''; }

$TPL->assign('script', $script); // Used to determine which section we're in

?>