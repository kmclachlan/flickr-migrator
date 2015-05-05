<?php

require_once 'lib/init.php';

if (!empty($_SESSION['flickr_loggedin']) && $_SESSION['flickr_loggedin'] == true) {
	$TPL->assign('FLICKR_LOGGEDIN', true);
} else {
	$TPL->assign('FLICKR_LOGGEDIN', false);
}

if (!empty($_SESSION['500px_access_token']) && is_array($_SESSION['500px_access_token'])) {
	$TPL->assign('500PX_LOGGEDIN', true);
} else {
	$TPL->assign('500PX_LOGGEDIN', false);
}

$TPL->display('index.tpl');

echo '<pre>';
var_dump($_SESSION);

?>