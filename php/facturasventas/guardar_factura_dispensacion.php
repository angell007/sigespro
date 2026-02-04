<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$dispensaciones = ( isset( $_REQUEST['dispensaciones'] ) ? $_REQUEST['dispensaciones'] : '' );

$datos = (array) json_decode($datos);
$dispensaciones = (array) json_decode($dispensaciones,true);

foreach($dispensaciones as $dispensacion){
    $oItem = new complex($mod,"Id_".$mod,$dispensacion);
    foreach($datos as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->Fecha_Asignado_Auditor = date('Y-m-d H:i:s');
$oItem->save();
unset($oItem);
}

$query = 'SELECT D.*, DATE_FORMAT(D.Fecha_Actual, "%d/%m/%Y") as Fecha_Dis, 
IFNULL(CONCAT(F.Nombres, " ", F.Apellidos),"No Asignado") as Funcionario, 
P.Nombre as Punto_Dispensacion, L.Nombre as Departamento 
FROM Dispensacion D
LEFT JOIN Funcionario F
on D.Facturador_Asignado=F.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion P
on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
INNER JOIN Departamento L
on P.Departamento=L.Id_Departamento
WHERE D.Tipo != "Capita" AND D.Estado_Facturacion = "Sin Facturar" 
ORDER BY D.Fecha_Actual DESC LIMIT 0, 10';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones = $oCon->getData();
unset($oCon);

echo json_encode($dispensaciones);
?>