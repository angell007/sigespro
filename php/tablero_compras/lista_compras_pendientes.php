<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT OCN.*, P.Nombre as Proveedor, F.Imagen FROM Orden_Compra_Nacional OCN
INNER JOIN Proveedor P ON OCN.Id_Proveedor=P.Id_Proveedor INNER JOIN Funcionario F ON OCN.Identificacion_Funcionario=F.Identificacion_Funcionario WHERE OCN.Aprobacion="Pendiente" and OCN.Estado !="Anulada"  ORDER BY OCN.Fecha DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
// $resultado = [];
// echo json_encode($resultado); exit;
echo json_encode($resultado);

?>