<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

$query = 'SELECT TP.Nombre as NombreBono, TP.Id_Tipo_Ingreso
            FROM Tipo_Ingreso TP
            WHERE TP.Tipo = "'.$tipo.'"';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Listado"] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
