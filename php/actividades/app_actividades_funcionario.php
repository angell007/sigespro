<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fecha = ( isset( $_REQUEST['fecha'] ) ? $_REQUEST['fecha'] : date("Y-m-d") );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT A.*,DATE_FORMAT(A.Fecha_Inicio, "%H:%i") as Inicio, DATE_FORMAT(A.Fecha_Fin, "%H:%i") as Fin, C.Nombre as Cliente, TA.Nombre as Tipo_Actividad
FROM Actividad A
LEFT JOIN Cliente C ON A.Id_Cliente=C.Id_Cliente
INNER JOIN Tipo_Actividad TA ON A.Id_Tipo_Actividad=TA.Id_Tipo_Actividad
WHERE DATE(A.Fecha_Inicio)= "'.$fecha.'" AND A.Identificacion_Funcionario='.$funcionario.' 
ORDER BY A.Fecha_Inicio ASC
';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades = $oCon->getData();
unset($oCon);


$resultado["Actividades"]=$actividades;

echo json_encode($resultado);

?>