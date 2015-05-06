<?php

require_once 'lib/init.php';
require_once 'lib/phpFlickr/phpFlickr.php';

ob_start();

$f = new phpFlickr(FLICKR_API_KEY, FLICKR_API_SECRET);

switch ($_REQUEST['do']) {
	case 'get_photo_metadata':
		$data = $f->clean_text_nodes($f->photos_getInfo($_REQUEST['id']));
		$data =  $data['photo'];
		
		$photo = array(
			'title' => $data['title'],
			'description' => $data['description'],
			'taken' => strtotime($data['dates']['taken']),
			'posted' => $data['dates']['posted'],
			'tags' => array()
		);
		
		foreach ($data['tags']['tag'] as $tag) {
			$photo['tags'][] = $tag['raw'];
		}
		
		// @todo get the EXIF too
		// $exif = $f->clean_text_nodes($f->photos_getExif($_REQUEST['id']));
		
		echo json_encode($photo);
		die();
	break;
	
	case 'get_photostream':
		// @todo including tags here gives us the "normalized" tags and not the raw as-entered tags
		$options = array(
			'content_type' => 1,
			'extras' => 'url_sq,url_z,url_o,description,date_upload,date_taken,tags',
			'per_page' => 500
		);
		$photos = $f->clean_text_nodes($f->people_getPhotos('me', $options));
		
		$photos = $photos['photos'];
		
		$response = array(
			'page' => $photos['page'],
			'pages' => $photos['pages'],
			'perpage' => $photos['perpage'],
			'total' => $photos['total'],
			'photos' => array()
		);

		foreach ($photos['photo'] as $i => $photo) {
			$response['photos'][$photo['id']] = array(
				'url_t' => $photo['url_sq'],
				'url_m' => $photo['url_z'],
				'url_o' => $photo['url_o'],
				'title' => $photo['title'],
				'description' => $photo['description'],
				'date_uploaded' => $photo['dateupload'],
				'date_taken' => strtotime($photo['datetaken']),
				'tags' => $photo['tags']
			);
		}
		
		echo json_encode($response);
		die();
	break;
	
	default:
		if (empty($_GET['frob'])) {
		    $f->auth('read', false);
		} else {
		    $token = $f->auth_getToken($_GET['frob']);
		}

		if (is_array($_SESSION['phpFlickr_auth_token'])) {
			$_SESSION['phpFlickr_auth_token'] = $_SESSION['phpFlickr_auth_token']['_content'];
		}

		if ($f->test_login()) {
		    $_SESSION['flickr_loggedin'] = true;
		    header('Location: index.php');
		} else {
			die('Not logged in for some reason!');
		}
	break;
}

?>