<?php
include("config/setup.inc");
include("include/amazon.s3.inc");
$s3 = new S3(SETTINGS_AWS_ACCESS_KEY,SETTINGS_AWS_SECRET_KEY);

$file = $_GET['path'];

parse_url($file);
$path = parse_url($file, PHP_URL_PATH);
$name = basename($path);
$bucket = parse_url($file, PHP_URL_HOST);

$info = $s3->getObject($bucket, $path);

$hd = $info->headers;
$data = $info->body;
$type = $hd['type'];
$size = $hd['size'];


header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Expires: 0");
header("Cache-Control: private",false); // required for certain browsers 
header('Content-Disposition: attachment; filename="'.$name.'"');
header("Content-Type: $type");
header("Content-Length: ".$size);

echo $data;