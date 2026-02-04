<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT * FROM Impuesto ';
          
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Impuesto'] = $oCon->getData();
unset($oCon);

$query = 'SELECT Meses_Vencimiento FROM Configuracion ';
          
$oCon= new consulta();
$oCon->setQuery($query);
$resultado['Meses'] = $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Bodega_Nuevo, Nombre  FROM Bodega_Nuevo ';
          
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Bodega'] = $oCon->getData();
unset($oCon);
          
echo json_encode($resultado);


?>