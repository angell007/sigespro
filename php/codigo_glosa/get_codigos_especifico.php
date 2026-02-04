<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = ' SELECT CONCAT(Codigo," - ",Concepto) as label,Codigo,Id_Codigo_Especifico_Glosa, Id_Codigo_Especifico_Glosa as value  FROM  Codigo_Especifico_Glosa WHERE Id_Codigo_General_Glosa='.$id.'
 ORDER BY Codigo ASC ';

 $oCon= new consulta();
 $oCon->setTipo('Multiple');
 $oCon->setQuery($query);
 $codigos = $oCon->getData();
 unset($oCon);
 
 echo json_encode($codigos);




?>