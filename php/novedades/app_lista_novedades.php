<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;


$query = 'SELECT N.*, 
            DATE_FORMAT(N.Fecha_Inicio, "%d/%m/%Y") AS Fecha_Inicio_Nov,
            DATE_FORMAT(N.Fecha_Fin, "%d/%m/%Y") AS Fecha_Fin_Nov,
            T.Tipo_Novedad,
            DATE_FORMAT(N.Fecha_Reporte, "%d/%m/%Y %H:%i:%s") as Fecha_Reporte,
            T.Novedad
FROM Novedad N 
INNER JOIN Tipo_Novedad T ON N.Id_Tipo_Novedad=T.Id_Tipo_Novedad 
WHERE N.Identificacion_Funcionario = '.$funcionario.'
ORDER BY N.Id_Novedad DESC';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$novedades = $oCon->getData();
unset($oCon);

echo json_encode($novedades);


?>