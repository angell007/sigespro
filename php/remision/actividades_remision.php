<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query="SELECT AR.*, F.Imagen,CONCAT_WS(' ',F.Nombres, F.Apellidos) as Funcionario,
(CASE
    WHEN AR.Estado='Creacion' THEN CONCAT('1 ',AR.Estado)
    WHEN AR.Estado='Alistamiento' THEN CONCAT('2 ',AR.Estado)
    WHEN AR.Estado='Edicion' THEN CONCAT('2 ',AR.Estado)
    WHEN AR.Estado='Fase 1' THEN CONCAT('2 ',AR.Estado)
    WHEN AR.Estado='Fase 2' THEN CONCAT('3 ',AR.Estado)
    WHEN AR.Estado='Enviada' THEN CONCAT('4 ',AR.Estado)
    WHEN AR.Estado='Facturada' THEN CONCAT('5 ',AR.Estado)
    WHEN AR.Estado='Recibida' THEN CONCAT('5 ',AR.Estado)
    WHEN AR.Estado='Anulada' THEN CONCAT('6 ',AR.Estado)
END) as Estado2, (CASE 
    WHEN AR.Estado='Anulada' THEN CONCAT(' ', AR.Detalles,'. Con la suguiente Observacion: ', IFNULL(R.Observacion_Anulacion, ''))
    WHEN AR.Estado!='Anulada' THEN AR.Detalles
END ) as Detalles
FROM Actividad_Remision AR
INNER JOIN Funcionario F On AR.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Remision R ON AR.Id_Remision=R.Id_Remision
WHERE AR.Id_Remision=$id
UNION ALL(
    SELECT ANC.*, F.Imagen,CONCAT_WS(' ',F.Nombres, F.Apellidos) as Funcionario, 
    '' as Estado2, ANC.Detalles
    From Actividad_No_Conforme_Remision ANC
    INNER JOIN Funcionario F On ANC.Identificacion_Funcionario=F.Identificacion_Funcionario
    Inner Join No_Conforme NC on NC.Id_No_Conforme = ANC.Id_No_Conforme
    INNER JOIN Remision R ON NC.Id_Remision=R.Id_Remision
    WHERE R.Id_Remision=$id
)



Order BY Fecha ASC, Id_Actividad_Remision ASC";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades = $oCon->getData();
unset($oCon);


echo json_encode($actividades);

?>