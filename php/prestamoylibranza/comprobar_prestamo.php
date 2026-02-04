<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id  = (isset($_REQUEST['empleado'] ) ? $_REQUEST['empleado'] : '' );
$tipo  = (isset($_REQUEST['tipo'] ) ? $_REQUEST['tipo'] :  '' );

$query = 'SELECT DISTINCT P.Tipo, P.Estado, P.Id_Prestamo
            FROM Prestamo P 
            INNER JOIN Prestamo_Cuota PC ON P.Id_Prestamo = PC.Id_Prestamo
            WHERE Identificacion_Funcionario = '.$id.' AND P.Tipo = "'.$tipo.'" ';            
$oCon= new consulta();
$oCon->setTipo('Simple');
$oCon->setQuery($query);
$response = $oCon->getData();
unset($oCon);

echo json_encode($response);
