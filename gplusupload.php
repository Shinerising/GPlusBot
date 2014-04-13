<?php
	
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

function getPhotoId($imgurl, $user, $pass, $username){
$serviceName = Zend_Gdata_Photos::AUTH_SERVICE_NAME;

$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $serviceName);

// update the second argument to be CompanyName-ProductName-Version
$gp = new Zend_Gdata_Photos($client, "Google-DevelopersGuide-1.0");

// In version 1.5+, you can enable a debug logging mode to see the
// underlying HTTP requests being made, as long as you're not using
// a proxy server
// $gp->enableRequestDebugLogging('/tmp/gp_requests.log');

$photoName = "Photo";
$photoCaption = "The photo was posted by GplusBot via PHP.";
$photoTags = "";

	set_time_limit (24 * 60 * 60);      
	$destination_folder = 'photo/';       
	$newfname = $destination_folder . basename($imgurl); 
	$m1 = md5_file($imgurl);
	$m2 = md5_file($newfname);
	if($m1 == $m2){
	}
	else if(filesize($imgurl)<1024*1024*10){
	$file = fopen ($imgurl, "rb");         
	if ($file) {         
		$newf = fopen ($newfname, "wb");         
		if ($newf)         
		while(!feof($file)) {         
			fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );         
		}         
	}         
	if ($file) {         
		fclose($file);         
	}         
	if ($newf) {         
		fclose($newf);         
	}
	}

$filename = $newfname;

// We use the albumId of 'default' to indicate that we'd like to upload
// this photo into the 'drop box'.  This drop box album is automatically 
// created if it does not already exist.
$albumId = "5916364461315830273";

$fd = $gp->newMediaFileSource($filename);
$fd->setContentType("image/".substr(strrchr($filename, '.'), 1));

// Create a PhotoEntry
$photoEntry = $gp->newPhotoEntry();

$photoEntry->setMediaSource($fd);
$photoEntry->setTitle($gp->newTitle($photoName));
$photoEntry->setSummary($gp->newSummary($photoCaption));

// add some tags
$keywords = new Zend_Gdata_Media_Extension_MediaKeywords();
$keywords->setText($photoTags);
$photoEntry->mediaGroup = new Zend_Gdata_Media_Extension_MediaGroup();
$photoEntry->mediaGroup->keywords = $keywords;

// We use the AlbumQuery class to generate the URL for the album
$albumQuery = $gp->newAlbumQuery();

$albumQuery->setUser($username);
$albumQuery->setAlbumId($albumId);

// We insert the photo, and the server returns the entry representing
// that photo after it is uploaded
$insertedEntry = $gp->insertPhotoEntry($photoEntry, $albumQuery->getQueryUrl()); 

$photoId = 0;
$photoId = $insertedEntry->getGphotoId()->getText();
return $photoId;
}
?>