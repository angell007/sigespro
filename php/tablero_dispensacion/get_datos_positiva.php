<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();
$http_response = new HttpResponse();

$autorizacion= ( isset( $_REQUEST['autorizacion'] ) ? $_REQUEST['autorizacion'] : '' );

$query=GetPositiva();
$queryObj->SetQuery($query);
$productospositiva = $queryObj->ExecuteQuery('Multiple');

$result = json_decode($productospositiva[0]['serviciosAutorizados'], true);


if ($result) {
    // $producto = GetProductos();
    $productocontrato = GetPContrato();

    echo $producto;
}else{

}

function GetProductos()
{
	global $result;

    foreach ($result as $value) {

        $query= "SELECT Id_Producto 
                    FROM Producto
	            WHERE Codigo_Cum = '".$value['codigo']."' ";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$prod = $oCon->getData();
	unset($oCon);
    }

    return $prod;
}


function GetPContrato()
{
	global $result;

    foreach ($result as $value) {

    $query ="SELECT Cum 
             FROM Tipo_Servicio TS 
             INNER JOIN Tipo_Servicio_Contrato TSC ON TS.Id_Tipo_Servicio = TSC.Id_Tipo_Servicio
             INNER JOIN Producto_Contrato PC ON TSC.Id_Contrato = PC.Id_Contrato
             WHERE TS.Nombre = 'POSITIVA' AND PC.Cum = '".$value['codigo']."' ";
	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$prod = $oCon->getData();
	unset($oCon);

    }

    return $prod;

}

function GetProductosContrato()
{
	global $result;

}

function GetPositiva(){

	global $autorizacion;

	$query='SELECT serviciosAutorizados 
            FROM Positiva_Data PD
	        WHERE numeroAutorizacion = ' . $autorizacion ;
	return $query;
}



