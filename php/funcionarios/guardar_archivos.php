<?php
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: cache-control,x-requested-with');
// header('Content-Type: application/json');



require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


/* if (!empty($_FILES['file']['name'])){
	$posicion1 = strrpos($_FILES['file']['name'],'.')+1;
	$extension1 =  substr($_FILES['file']['name'],$posicion1);
	$extension1 =  strtolower($extension1);
	$_filename1 = uniqid() . "." . $extension1;
	$_file1 = $MY_FILE . "IMAGENES/FUNCIONARIOS/" . $_filename1;
	
	$ancho="800";
	$alto="800";	
	$subido1 = move_uploaded_file($_FILES['file']['tmp_name'], $_file1);
		if ($subido1){
			list($width, $height, $type, $attr) = getimagesize($_file1);		
			@chmod ( $_file1, 0777 );
			$funcionario["Imagen"] = $_filename1;
		} 
} */



?>