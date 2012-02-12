<?php
include("../config/setup.inc");
include("../include/amazon.s3.inc");
$s3 = new S3(SETTINGS_AWS_ACCESS_KEY,SETTINGS_AWS_SECRET_KEY);


$from = $_POST['from'];
$to = $_POST['to'];


if ($s3->copyObject(SETTINGS_AWS_BUCKET, $from, SETTINGS_AWS_BUCKET, $to, S3::ACL_PUBLIC_READ)) {
    $s3->deleteObject(SETTINGS_AWS_BUCKET, $from);
}