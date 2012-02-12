<?php
include("../config/setup.inc");
include("../include/amazon.s3.inc");
$s3 = new S3(SETTINGS_AWS_ACCESS_KEY,SETTINGS_AWS_SECRET_KEY);

$folder = $_POST['name'];
$path = $_POST['path'];

$remote = $path.'/'.$folder.'/';
$remote = str_replace('///','/',$remote);
$remote = str_replace('//','/',$remote);
if(substr($remote,0,1)=='/'){
	$remote = substr($remote,1);
}

$s3->putObject('', SETTINGS_AWS_BUCKET, $remote, S3::ACL_PUBLIC_READ);