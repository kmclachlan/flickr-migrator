<?php

require_once("lib/phpFlickr/phpFlickr.php");

$f = new phpFlickr('007bf5fb0ca012fa78d4f573790ebc9e', 'a889316be3115624', true);

$options = array(
	'content_type' => 1,
	'extras' => 'url_sq,url_t,url_s,url_o'
);
$user_photos = $f->people_getPhotos('me', $options);

echo '<pre>';
print_r($user_photos);

foreach ($user_photos['photos']['photo'] as $photo) {
	$photo_id = $photo['id'];
	$photo_info = $f->photos_getInfo($photo_id);
	$photo_info = $f->clean_text_nodes($photo_info);
	print_r($photo_info);
}

die();


$recent = $f->photos_getRecent();
echo '<pre>';
print_r($recent);
foreach ($recent['photo'] as $photo) {
    $owner = $f->people_getInfo($photo['owner']);
    echo "<a href='http://www.flickr.com/photos/" . $photo['owner'] . "/" . $photo['id'] . "/'>";
    echo $photo['title'];
    echo "</a> Owner: ";
    echo "<a href='http://www.flickr.com/people/" . $photo['owner'] . "/'>";
    echo $owner['username'];
    echo "</a><br>";
}
  
?>
