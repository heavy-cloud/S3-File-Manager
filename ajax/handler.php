<?php
include("../config/setup.inc");
include("../include/amazon.s3.inc");
$s3 = new S3(SETTINGS_AWS_ACCESS_KEY,SETTINGS_AWS_SECRET_KEY);

$hd = getallheaders();
$data = file_get_contents('php://input');
$name = $hd["X-File-Name"];
$type = $hd["X-File-Type"];
$path = $hd["X-File-Path"];

$remote = $path.'/'.$name;
$remote = str_replace('///','/',$remote);
$remote = str_replace('//','/',$remote);
$s3->putObject(array('data'=>$data,'type'=>$type), SETTINGS_AWS_BUCKET, $remote, S3::ACL_PUBLIC_READ);

//$temp = 'tmp/'.$name;
//file_put_contents($temp,$data);


header("Content-type: text/plain");
$out['path'] = $remote;
$out['name'] = $name;
$out['type'] = $type;
echo json_encode($out);
