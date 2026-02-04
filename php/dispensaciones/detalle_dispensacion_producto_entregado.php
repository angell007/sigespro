<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_producto = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
$id_paciente = ( isset( $_REQUEST['Id_Paciente'] ) ? $_REQUEST['Id_Paciente'] : '' );

$query = 'SELECT D.Codigo, DATE_FORMAT(D.Fecha_Actual, "%d/%m/%Y") AS Fecha FROM Dispensacion D INNER JOIN Producto_Dispensacion PD ON D.Id_Dispensacion=PD.Id_Dispensacion WHERE MONTH(D.Fecha_Actual)=MONTH(NOW()) AND D.Numero_Documento='.$id_paciente.' AND PD.Id_Producto='.$id_producto.' AND D.Estado_Dispensacion!="Anulada" ';

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>