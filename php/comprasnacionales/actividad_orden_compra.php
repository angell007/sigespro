<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT AR.*, F.Imagen,
(CASE
    WHEN AR.Estado="Creacion" THEN CONCAT("1 ",AR.Estado)
    WHEN AR.Estado="Edicion" THEN CONCAT("2 ",AR.Estado)
    WHEN AR.Estado="Recepcion" THEN CONCAT("3 ",AR.Estado)
    WHEN AR.Estado="Aprobacion" THEN CONCAT("4 ",AR.Estado)
    WHEN AR.Estado="Anulada" THEN CONCAT("2 ",AR.Estado)
END) as Estado2

FROM Actividad_Orden_Compra AR
INNER JOIN Funcionario F
On AR.Identificacion_Funcionario=F.Identificacion_Funcionario
WHERE AR.Id_Orden_Compra_Nacional='.$id.'
Order BY Fecha ASC, Estado2 ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades = $oCon->getData();
unset($oCon);


echo json_encode($actividades);

?>