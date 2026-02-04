<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_vacante = $_REQUEST['id'];

$query = 'SELECT *, DATE_FORMAT(Fecha_Inicio, "%d/%m/%Y") AS Fecha_Inicio_Format, DATE_FORMAT(Fecha_Fin, "%d/%m/%Y") AS Fecha_Fin_Format FROM Vacante WHERE Id_Vacante='.$id_vacante ;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>