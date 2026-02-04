<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
	$datos = (array) json_decode($datos);

	$oItem = new complex("Producto_Tipo_Tecnologia_Mipres","Id_Producto_Tipo_Tecnologia_Mipres", $datos["Id_Producto_Tipo_Tecnologia_Mipres"]);
    
    $oItem->Id_Tipo_Tecnologia_Mipres=trim($datos["Id_Tipo_Tecnologia_Mipres"]);
	$oItem->Codigo_Anterior=trim($datos["Codigo_Anterior"]);
	$oItem->Codigo_Actual=trim($datos["Codigo_Actual"]);
	$oItem->Descripcion=trim($datos["Descripcion"]);
	$oItem->Id_Producto=trim($datos["Id_Producto"]);
    
    // $oItem->save();
    // unset($oItem);
    
    $query1 = 'UPDATE Producto_Tipo_Tecnologia_Mipres
	SET Id_Tipo_Tecnologia_Mipres = '.$oItem->Id_Tipo_Tecnologia_Mipres.' ,
	Codigo_Anterior = "'.$oItem->Codigo_Anterior.'",
	Codigo_Actual = "'.$oItem->Codigo_Actual.'",
	Descripcion = "'.$oItem->Descripcion.'",
	Id_Producto = '.$oItem->Id_Producto.'	
    WHERE Id_Producto_Tipo_Tecnologia_Mipres = '.$datos["Id_Producto_Tipo_Tecnologia_Mipres"];

    $oCon= new consulta();
	$resp=$oCon->setQuery($query1);
    $bod = $oCon->createData();
    unset($oCon);

    $http_response->SetRespuesta(0, 'Actualizacion Exitosa!', 'Se han actualizado los datos correctamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>