<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');

//Objeto de la clase que almacena los archivos    
$storer = new FileStorer();

$datos=(isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$modulos = ( isset( $_REQUEST['modulos'] ) ? $_REQUEST['modulos'] : '' );
$bodegas = ( isset( $_REQUEST['bodegas'] ) ? $_REQUEST['bodegas'] : '' );
$puntos = ( isset( $_REQUEST['puntos'] ) ? $_REQUEST['puntos'] : '' );
$perfil_id = ( isset( $_REQUEST['id_perfil'] ) ? $_REQUEST['id_perfil'] : '' );
$auth = ( isset( $_REQUEST['autoriza'] ) ? $_REQUEST['autoriza'] : '' );


$funcionario = (array) json_decode($funcionario , true);
$modulos=(array) json_decode($modulos , true);
$bodegas=(array) json_decode($bodegas , true);
$puntos=(array) json_decode($puntos , true);
$perfil_id = json_decode($perfil_id);
$auth = json_decode($auth);


$funcionario['Username'] = md5($funcionario['Identificacion_Funcionario']);
$funcionario['Password'] = md5($funcionario['Identificacion_Funcionario']);
$funcionario['Autorizado'] = $auth;


/*var_dump($perfil_id);
var_dump($auth);
var_dump($funcionario);
exit;*/

/* GUARDA FUNCIONARIO */	
if (!empty($_FILES['Foto']['name'])){
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/FUNCIONARIOS/');
    $funcionario["Imagen"] = $nombre_archivo[0];
    
	// $posicion1 = strrpos($_FILES['Foto']['name'],'.')+1;
	// $extension1 =  substr($_FILES['Foto']['name'],$posicion1);
	// $extension1 =  strtolower($extension1);
	// $_filename1 = uniqid() . "." . $extension1;
	// $_file1 = $MY_FILE . "IMAGENES/FUNCIONARIOS/" . $_filename1;
	
	// $ancho="800";
	// $alto="800";	
	// $subido1 = move_uploaded_file($_FILES['Foto']['tmp_name'], $_file1);
	// 	if ($subido1){
	// 		list($width, $height, $type, $attr) = getimagesize($_file1);		
	// 		@chmod ( $_file1, 0777 );
	// 		$funcionario["Imagen"] = $_filename1;
	// 	} 
}
		
$oItem = new complex('Funcionario','Identificacion_Funcionario');
foreach($funcionario as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);

$id_funcionario=$funcionario["Identificacion_Funcionario"];
foreach($modulos as $modulo){
    if(isset($modulo["Id_Perfil_Funcionario"])&&$modulo["Id_Perfil_Funcionario"] != ""){
	$oItem = new complex("Perfil_Funcionario","Id_Perfil_Funcionario",$modulo["Id_Perfil_Funcionario"]);
    }else{
    	 $oItem = new complex("Perfil_Funcionario","Id_Perfil_Funcionario");
    }

    $oItem->Id_Perfil=$perfil_id;
    $oItem->Identificacion_Funcionario=$id_funcionario;
    $oItem->Titulo_Modulo=$modulo["Titulo_Modulo"];
    $oItem->Modulo = $modulo["Modulo"];
    if($modulo["Ver"] != ""){
         $oItem->Ver = $modulo["Ver"]; 
    }else{
        $oItem->Ver = "0";
    }
    if($modulo["Crear"] != ""){
     $oItem->Crear = $modulo["Crear"];   
    }else{
        $oItem->Crear = "0";
    }
     if($modulo["Editar"] != ""){
     $oItem->Editar = $modulo["Editar"];   
    }else{
        $oItem->Editar = "0";
    }
     if($modulo["Eliminar"] != ""){
     $oItem->Eliminar = $modulo["Eliminar"];   
    }else{
        $oItem->Eliminar = "0";
    }
    $oItem->save();
    unset($oItem);
}
foreach($bodegas as $bodega){
    if(isset($bodega["Id_Funcionario_Bodega"])&&$bodega["Id_Funcionario_Bodega"] != ""){
	$oItem = new complex("Funcionario_Bodega","Id_Funcionario_Bodega",$bodega["Id_Funcionario_Bodega"]);
    }else{
    	 $oItem = new complex("Funcionario_Bodega","Id_Funcionario_Bodega");
    }
    $oItem->Identificacion_Funcionario=$id_funcionario;
    $oItem->Id_Bodega=$bodega;
    $oItem->save();
    unset($oItem);
}
foreach($puntos as $punto){
    if(isset($punto["Id_Funcionario_Punto"])&&$punto["Id_Funcionario_Punto"] != ""){
	$oItem = new complex("Funcionario_Punto","Id_Funcionario_Punto",$punto["Id_Funcionario_Punto"]);
    }else{
    	 $oItem = new complex("Funcionario_Punto","Id_Funcionario_Punto");
    }
    $oItem->Identificacion_Funcionario=$id_funcionario;
    $oItem->Id_Punto_Dispensacion=$punto;
    $oItem->save();
    unset($oItem);
}



/* GUARDA PERSONA EN MICROSOFT */
/*$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons');
$url = $request->getUrl();
$headers = array(
    'Content-Type' => 'application/json',
    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
);
$request->setConfig(array(
    'ssl_verify_peer'   => FALSE,
    'ssl_verify_host'   => FALSE
));
$request->setHeader($headers);
$parameters = array(  
);
$body = array(
	"name"=>$funcionario["Nombres"]." ".$funcionario["Apellidos"],
    "userData"=>$funcionario["Identificacion_Funcionario"]
);
$url->setQueryVariables($parameters);
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setBody(json_encode($body));
try{
    $response = $request->send();
    $resp=$response->getBody();
	$resp=json_decode($resp);
	$person_id=$resp->personId;
	
	$oItem = new complex('Funcionario','Identificacion_Funcionario',$funcionario["Identificacion_Funcionario"]);
	$oItem->personId=$person_id;
	$func=$oItem->getData();
	$oItem->save();
	unset($oItem);	
	
}catch (HttpException $ex){
    echo "error: ".$ex;
}

/* GUARDA FOTO DE PERSONA */
/*if($func["Imagen"]!=""){
	$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons/'.$person_id.'/persistedFaces');
	$url = $request->getUrl();
	$request->setConfig(array(
	    'ssl_verify_peer'   => FALSE,
	    'ssl_verify_host'   => FALSE
	));
	$headers = array(
	    'Content-Type' => 'application/json',
	    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
	);
	
	$request->setHeader($headers);
	$parameters = array(
	);
	$body=array(
		"url"=>$URL."IMAGENES/FUNCIONARIOS/" . $_filename1
	);
	$url->setQueryVariables($parameters);
	$request->setMethod(HTTP_Request2::METHOD_POST);
	$request->setBody(json_encode($body));
	try{
	    $response = $request->send();
	    $resp=$response->getBody();
		$resp=json_decode($resp);
		$persistedFaceId=$resp->persistedFaceId;
		$oItem = new complex('Funcionario','Identificacion_Funcionario',$funcionario["Identificacion_Funcionario"]);
		$oItem->persistedFaceId=$persistedFaceId;
		$oItem->save();
	}catch (HttpException $ex){
	    echo $ex;
	}	
}


/*PERMITE QUE SE PUEDAN REVISAR LAS FOTOS NUEVAS */
/*$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/train');
$url = $request->getUrl();
$request->setConfig(array(
    'ssl_verify_peer'   => FALSE,
    'ssl_verify_host'   => FALSE
));
$headers = array(
    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
);
$request->setHeader($headers);
$parameters = array(

);
$url->setQueryVariables($parameters);
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setBody("");
try{
    $response = $request->send();
    echo $response->getBody();
}catch (HttpException $ex){
    echo $ex;
}*/



$resultado['mensaje'] = "¡Funcionario Guardado Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>