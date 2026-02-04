<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT PR.Id_Proveedor,PR.Nombre  
           FROM Proveedor PR' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$proveedorlist = $oCon->getData();
unset($oCon);


$query2 = 'SELECT B.Id_Bodega,B.Nombre  
           FROM Bodega B';

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$bodegalist = $oCon->getData();
unset($oCon);



$resultado["proveedorli"]=$proveedorlist;
$resultado["bodegali"]=$bodegalist;

echo json_encode($resultado);
          
?>