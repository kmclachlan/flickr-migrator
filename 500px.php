<?php

require_once 'lib/init.php';
//require_once LIB . '/TwitterOAuth/TwitterOAuth.php';
use Abraham\TwitterOAuth\TwitterOAuth;

switch ($_REQUEST['do']) {
	case 'upload':
		if ($_SESSION['500px_access_token']) {
			$access_token = $_SESSION['500px_access_token'];
			$connection = new TwitterOAuth('ix8NQZKQntGHt20OcX2ehtBwXcYl1kIHPc3nSgv8', 'z1nOcyFgNFgQk3da1Gz6OHSK7gXS4tGPcOAFrxNl', $access_token['oauth_token'], $access_token['oauth_token_secret']);
			
			$photos = $connection->get("photos", array('feature' => 'popular'));
			
			$key_response = $connection->post('photos', array(
				'name' => $_REQUEST['title'],
				'description' => $_REQUEST['description'],
				'category' => 0,
				'tags' => str_replace(' ', ',', $_REQUEST['tags'])
			));

			//error_Log(print_r($key_response, 1));
			


			$upload_key = $key_response->upload_key;
			$photo_id = $key_response->photo->id;
			$access_key = $_SESSION['500px_access_token']['oauth_token'];
			
			$upload_result = $connection->post('upload', array(
				'photo_id' => $photo_id,
				'consumer_key' => 'ix8NQZKQntGHt20OcX2ehtBwXcYl1kIHPc3nSgv8',
				'upload_key' => $upload_key,
				'access_key' => $access_key,
				'remote_url' => $_REQUEST['url_o'],
			));

			//error_Log(print_r($upload_result, 1));

			if ($upload_result->error == 'None.') {
				echo 'ok';
			} else {
				echo $result->error . ' - ' . $result->status;
			}
		} else {
			echo 'No access token';
		}
	break;
	
	default:
		if (!empty($_REQUEST['oauth_token'])) {
			$request_token = [];
			$request_token['oauth_token'] = $_SESSION['500px_oauth_token'];
			$request_token['oauth_token_secret'] = $_SESSION['500px_oauth_token_secret'];
			
			unset($_SESSION['500px_oauth_token']);
			unset($_SESSION['500px_oauth_token_secret']);

			if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
			    // Abort! Something is wrong.
			    echo 'Invalid validation!';
			    die();
			}
			
			$connection = new TwitterOAuth('ix8NQZKQntGHt20OcX2ehtBwXcYl1kIHPc3nSgv8', 'z1nOcyFgNFgQk3da1Gz6OHSK7gXS4tGPcOAFrxNl', $request_token['oauth_token'], $request_token['oauth_token_secret']);
			$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
			$_SESSION['500px_access_token'] = $access_token;
			
			header('Location: index.php');
			
			die();
		}
		
		if ($_REQUEST['do'] == 'authorize' || empty($_SESSION['500px_oauth_token'])) {
			$connection = new TwitterOAuth('ix8NQZKQntGHt20OcX2ehtBwXcYl1kIHPc3nSgv8', 'z1nOcyFgNFgQk3da1Gz6OHSK7gXS4tGPcOAFrxNl');
			$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => 'http://dev.migrate.500px.com/500px.php'));
			$_SESSION['500px_oauth_token'] = $request_token['oauth_token'];
			$_SESSION['500px_oauth_token_secret'] = $request_token['oauth_token_secret'];
			
			$url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
			
			header('Location: ' . $url);
		}
	break;
}

?>
