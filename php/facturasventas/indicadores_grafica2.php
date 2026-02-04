<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$condicion = '';
$condicion_sc2 = '';

if ($funcionario != '') {
	$condicion = ' AND Id_Funcionario ='.$funcionario;
	$condicion_sc2 = ' AND Id_Funcionario ='.$funcionario;
}

$query1 = 'SELECT count(*) as conteoNoPagas , 
                IFNULL((SELECT count(*) FROM `Factura_Venta` WHERE Estado = "Cancelada"'.$condicion.' ),0) as conteoPagas , 
                (SELECT count(*) FROM `Factura_Venta` WHERE Estado = "Pendiente" '.$condicion_sc2.') as conteo 
           FROM `Factura_Venta` WHERE Estado = "Pendiente"'
           .$condicion ;

       

$oCon= new consulta();
$oCon->setQuery($query1);
$lista = $oCon->getData();
unset($oCon);

echo json_encode($lista);
?>