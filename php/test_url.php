<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include_once('../class/class.awsS3.php');

$s3 = new AwsS3();

$value['Archivo']= $_POST['url'];

// $existObject = $s3->doesObjectExist($value['Archivo']);
            
//       		if($existObject){
                echo "ok"; 
               $uri = $s3->deleteObject($value['Archivo']);
            // }

echo "$uri";