<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$destino = ( isset( $_REQUEST['destino'] ) ? $_REQUEST['destino'] : '' );
 

$query='SELECT B.Codigo as Codigo, B.Id_Borrador as Id_Borrador
FROM Borrador B
WHERE B.Codigo="'.$codigo.'"';
$oCon= new consulta();
$oCon->setQuery($query);
$respuesta = $oCon->getData();
unset($oCon);


if($respuesta!=''|| $respuesta!=null){
     $oItem = new complex("Borrador","Id_Borrador",$respuesta['Id_Borrador']);
}else{
     $oItem = new complex("Borrador","Id_Borrador");
}
$oItem->Tipo="Remision";
$oItem->Codigo=$codigo;
$oItem->Texto=$datos;
$oItem->Id_Funcionario=$funcionario;
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Nombre_Destino=$destino;
$oItem->Estado = "Activo";
$oItem->save();
unset($oItem);



$http_response->SetRespuesta(0, 'Guardado Automaticamente', 'Se ha guardado correctamente!');
$response = $http_response->GetRespuesta();

echo json_encode($response);
?>





