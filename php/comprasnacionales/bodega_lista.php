<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT IM.Valor 
            FROM Impuesto IM';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$impuesto = $oCon->getData();
unset($oCon);


$query1 = 'SELECT B.Id_Bodega,B.Nombre  
           FROM Bodega B';

$oCon= new consulta();
$oCon->setQuery($query1);
$oCon->setTipo('Multiple');
$bodegalist = $oCon->getData();
unset($oCon);

$query1 = 'SELECT PD.Id_Punto_Dispensacion as Id_Bodega, PD.Nombre  
           FROM Punto_Dispensacion PD';

$oCon= new consulta();
$oCon->setQuery($query1);
$oCon->setTipo('Multiple');
$punto = $oCon->getData();
unset($oCon);

$resultado["impuestoli"]=$impuesto;
$resultado["bodegali"]=$bodegalist;
$resultado["Punto"]=$punto;

echo json_encode($resultado);
          
?>