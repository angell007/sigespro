<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT FP.*, PD.Nombre as Nombre_Punto, PD.Cajas, PD.Turnero, PD.NO_POS, PD.Estado, (SELECT PT.Id_Turneros FROM Punto_Turnero PT WHERE PT.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion LIMIT 1 ) as Turneros, PD.Nombre as label, PD.Id_Punto_Dispensacion as value
          FROM Funcionario_Punto FP
          INNER JOIN Punto_Dispensacion PD
          on FP.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
          WHERE FP.Identificacion_Funcionario ='. $id .' AND PD.Estado = "Activo"';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$puntos_propios = $oCon->getData();
unset($oCon);

echo json_encode($puntos_propios);
?>