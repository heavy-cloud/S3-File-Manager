<?php
include("../config/setup.inc");
include("../include/amazon.s3.inc");
$s3 = new S3(SETTINGS_AWS_ACCESS_KEY,SETTINGS_AWS_SECRET_KEY);

$path = $_GET['path'];

if(substr($path,-1)){
	//ok guys, we've got a directory on our hands
	$list = $s3->getBucket(SETTINGS_AWS_BUCKET,$path);
	$arr = array_reverse($list);
	foreach($arr as $item){
		$s3->deleteObject(SETTINGS_AWS_BUCKET,$item['name']);
	}
}else{
	$s3->deleteObject(SETTINGS_AWS_BUCKET,$path);
}

