<?php
include("../config/setup.inc");
include("../include/amazon.s3.inc");
$s3 = new S3(SETTINGS_AWS_ACCESS_KEY,SETTINGS_AWS_SECRET_KEY);


$path = $_GET['path'];
if($path=='/'){
	$path = '';
}

$list = $s3->getBucket(SETTINGS_AWS_BUCKET,$path,null,null,"/",true);
if(is_bool($list)){
	echo 'error';
	die();
}

$out = array();

foreach($list as $item){

	
			$link = 'http://'.SETTINGS_AWS_BUCKET.'/'.$item['name'];
			$len = strlen($item['name'])-1;
			$check = substr($item['name'],$len);
			$type = 'file';
			$thispath = $item['name'];
			$size = $item['size'];
			$time = $item['time'];
			if(!empty($item['prefix'])){
				$thispath = $item['prefix'];
				$type = 'dir';
				$link = 'http://'.SETTINGS_AWS_BUCKET.'/'.$item['prefix'];			
				$size = 0;
				$time = 0;
			}
		if(basename($thispath) != basename($path)){
		
			$n = array(
				'path'=>$thispath,
				'name'=>basename($thispath),
				'link'=>$link,
/* 				'hash'=>$item['hash'], */
				'size'=>$size,
				'type'=>$type,
				'time'=>$time
			);
	
			array_push($out, $n);
		}
}
foreach($out as $key=>$row){
	$col1[$key] = $row[0];
	$col2[$key] = $row[1];
	$col3[$key] = $row[2];
	$col4[$key] = $row[3];
	$col5[$key] = $row[4];
	$col6[$key] = $row[5];
}
array_multisort($col2, SORT_ASC, $out);



header("Content-type: application/json");
echo json_encode($out);