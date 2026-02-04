<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT DISTINCT 
            ( SELECT count(`Tipo`) FROM `Orden_Compra_Nacional` WHERE `Tipo` = "Cancelada" ) as "Pagadas" , 
            ( SELECT count(`Tipo`) FROM `Orden_Compra_Nacional` WHERE `Tipo` <> "Cancelada" ) as "No_Pagadas" 
          FROM `Orden_Compra_Nacional` ' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
unset($oCon);

echo json_encode($lista);

?>