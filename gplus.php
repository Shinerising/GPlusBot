<?php
/*
 * GPlus bot
 * @Version 04.13.2014
 * @Author: Apollo Wayne https://plus.google.com/u/0/+ApolloWayne
 * @Link http://wayshine.us/
 * @Modified from gplus-bot https://github.com/lukapusic/gplus-bot
 * @Original author Luka Pušić <luka@pusic.si>
 */
 
	error_reporting(0);
	
	date_default_timezone_set('Asia/Shanghai');
	header("Content-type:application/xml; charset=utf-8");
	ini_set('memory_limit', '64M');
		
	require_once 'gplusupload.php';
	
/*
 * REQUIRED PARAMETERS
 * $email is the email address you used in your Google+ Account
 * and $pass is the password
 * $oid is your user id of G+, which you can find in the URL of your G+ profile page
 */

$email = '';
$pass = '';
$oid = '';

/*
 * REQUEST PARAMETERS
 * both methods of POST and GET is supported
 * $status is the text of post you want to send
 * $imgurl is the URL address of image you want to share
 * $pid is the picture id of image that has been upload to Google Photos(or Picasa Webalbum)
 * if the value of $imgurl exists, the image will be uploaded before sending your post
 * if not, then $pid will be checked.
 */
$status = $_REQUEST['post']."\n #GPlusBot";
$imgurl=$_REQUEST['img'];
$pid=$_REQUEST['pid'];

/**
 * OPTIONAL PARAMETERS
 * $sleeptime is an optional timeout parameter which makes us look less suspicious to Google
 * the $debug is not used
 */
$pageid = false;
$cookies = 'cookie.txt';
$sleeptime = 0;
$uagent = 'Mozilla/4.0 (compatible; MSIE 5.0; S60/3.0 NokiaN73-1/2.0(2.0617.0.0.7) Profile/MIDP-2.0 Configuration/CLDC-1.1)';
$pc_uagent = 'Mozilla/5.0 (X11; Linux x86_64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1';
$debug = false;


$error="start error";

function tidy($str) {
    return rtrim($str, "&");
}

/**
 * Handle cookie file
 */
@unlink($cookies); //delete previous cookie file if exists
touch($cookies); //create a cookie file

/**
 * MAIN BLOCK
 * login_data() just collects login form info
 * login($postdata) logs you in and you can do pretty much anything you want from here on
 */
login(login_data());
sleep($sleeptime);
if ($pageid) {
    update_page_status();
} else {
    update_profile_status();
} //update status with $GLOBAL['status'];
sleep($sleeptime);
//logout(); //optional - log out

//print the response
$time = date("Y-m-d H:i:s");
echo '<?xml version="1.0" encoding="UTF-8" ?>';
echo <<< eod
		<post>
			<content>$status</content>
			<img>$imgurl</img>
			<pid>$pid</pid>
			<status>$error</status>
			<time>$time</time>
		</post>
eod;
/**
 * 1. GET: https://plus.google.com/app/basic/login
 * Parse the webpage and collect form data
 * @return array (string postdata, string postaction)
 */
function login_data() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, "https://plus.google.com/app/basic/login");
	//curl_setopt($ch, CURLOPT_URL, "https://plus.google.com/");	//the old URL, invalid now
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $buf = utf8_decode(html_entity_decode(curl_exec($ch)));
    $buf = str_replace( '&amp;', '&', $buf );	// just in case any correctly encoded
    $buf = str_replace( '&', '&amp;', $buf );	// now encode them all again
	
    curl_close($ch);

    //echo "\n[+] Sending GET request to: https://plus.google.com/app/basic/login\n\n";
	
    $toreturn = '';

    $doc = new DOMDocument;
    $doc->loadHTML($buf);
    $inputs = $doc->getElementsByTagName('input');
    foreach ($inputs as $input) {
	switch ($input->getAttribute('name')) {
	    case 'Email':
		$toreturn .= 'Email=' . urlencode($GLOBALS['email']) . '&';
		break;
	    case 'Passwd':
		$toreturn .= 'Passwd=' . urlencode($GLOBALS['pass']) . '&';
		break;
	    default:
		$toreturn .= $input->getAttribute('name') . '=' . urlencode($input->getAttribute('value')) . '&';
	}
    }
		
    return array(tidy($toreturn), $doc->getElementsByTagName('form')->item(0)->getAttribute('action'));
		
}

/**
 * 2. POST login: https://accounts.google.com/ServiceLoginAuth
 */
function login($postdata) {
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, $postdata[1]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata[0]);
    $buf = curl_exec($ch);
	
    $header = curl_getinfo($ch);
	if($header['http_code']==200)$GLOBALS['error']="succeed";
	else{exit("login error");}
	
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }

    //echo "\n[+] Sending POST request to: " . $postdata[1] . "\n\n";
}

/**
 * 3. GET status update form:
 * Parse the webpage and collect form data
 */
function update_profile_status() {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, 'https://m.google.com/app/plus/?v=compose&group=m1c&hideloc=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $buf = utf8_decode(html_entity_decode(str_replace('&', '', curl_exec($ch))));
    $header = curl_getinfo($ch);
	
    $header = curl_getinfo($ch);
	if($header['http_code']==200)$GLOBALS['error']="succeed";
	else{exit("get post page error");}
	
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }

    $params = '';
    $doc = new DOMDocument;
    $doc->loadHTML($buf);
    $inputs = $doc->getElementsByTagName('input');
    foreach ($inputs as $input) {
	if (($input->getAttribute('name') != 'editcircles') && ($input->getAttribute('name') != 'cpPhotoId') && ($input->getAttribute('name') != 'cpPhotoOwnerId')) {
	    $params .= $input->getAttribute('name') . '=' . urlencode($input->getAttribute('value')) . '&';
	}
    }
    //$params .= 'newcontent=' . urlencode($GLOBALS['status']);
	
	$params .= 'cpPostMsg=' . urlencode($GLOBALS['status']);
	$params .= '&buttonPressed=1';
	if($GLOBALS['imgurl']){
		$params .= '&cpPhotoId='.getPhotoId($GLOBALS['imgurl'], $email, $pass, $oid);	//the function getPhotoId() is used to upload new images, see it in gplusupload.php
		$params .= '&cpPhotoOwnerId='.$GLOBALS['oid'];
	}
	else if($GLOBALS['pid']){
		$params .= '&cpPhotoId='.$GLOBALS['pid'];
		$params .= '&cpPhotoOwnerId='.$GLOBALS['oid'];
	}
	
    //$baseurl = $doc->getElementsByTagName('base')->item(0)->getAttribute('href');
    //$baseurl = 'https://m.google.com' . parse_url($header['url'], PHP_URL_PATH);
	$baseurl = 'https://m.google.com' . str_replace('amp;','&',$doc->getElementsByTagName('form')->item(0)->getAttribute('action'));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    //curl_setopt($ch, CURLOPT_URL, $baseurl . '?v=compose&group=m1c&group=b0&hideloc=1&a=post');
	curl_setopt($ch, CURLOPT_URL, $baseurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //curl_setopt($ch, CURLOPT_REFERER, $baseurl . '?v=compose&group=m1c&group=b0&hideloc=1');
	//curl_setopt($ch, CURLOPT_REFERER, $baseurl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $buf = curl_exec($ch);
    $header = curl_getinfo($ch);
	if($header['http_code']==200)$GLOBALS['error']="succeed";
	else{exit("post error");}
	
    curl_close($ch);
	//echo $baseurl.'\n\n';
	//echo $params;
    if ($GLOBALS['debug']) {
	echo $buf;
    }
}

/**
 * Not implemented yet!
 * just ignore this function for now
 */
function update_page_status() {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['pc_uagent']);
    curl_setopt($ch, CURLOPT_URL, 'https://plus.google.com/u/0/b/' . $GLOBALS['pageid'] . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $buf = utf8_decode(html_entity_decode(str_replace('&', '', curl_exec($ch))));
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
}

/**
 * 3. GET logout:
 * Just logout to look more human like and reset cookie :)
 */
function logout() {
    //echo "\n[+] GET Logging out: \n\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/Logout');
    $buf = curl_exec($ch);
    $header = curl_getinfo($ch);
	if($header['http_code']==200)$GLOBALS['error']="succeed";
	else{exit("logout error");}
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
}

?>
