<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT AR.*, F.Imagen,CONCAT_WS(" ",F.Nombres, F.Apellidos) as Funcionario,
(CASE
    WHEN AR.Estado="Creacion" THEN CONCAT("1 ",AR.Estado)
    WHEN AR.Estado="Fase 1" THEN CONCAT("2 ",AR.Estado)
    WHEN AR.Estado="Fase 2" THEN CONCAT("3 ",AR.Estado)
    WHEN AR.Estado="Enviada" THEN CONCAT("4 ",AR.Estado)
    WHEN AR.Estado="Anulada" THEN CONCAT("5 ",AR.Estado)
END) as Estado2,
(CASE 
    WHEN AR.Estado="Anulada" THEN CONCAT("Anulada - 5" , AR.Detalles)
    WHEN AR.Estado!="Anulda" THEN AR.Detalles
END ) as Detalles
FROM Actividad_Devolucion_Compra AR
INNER JOIN Funcionario F
On AR.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Devolucion_Compra R ON AR.Id_Devolucion_Compra=R.Id_Devolucion_Compra
WHERE AR.Id_Devolucion_Compra='.$id.'
Order BY Estado2 ASC, Fecha ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades = $oCon->getData();
unset($oCon);


echo json_encode($actividades);

?>