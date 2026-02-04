<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$resultado = [];

if ($id) {
    $query = "SELECT A.Fecha, A.Detalles, A.Estado, F.* FROM Actividad_Gasto_Punto A INNER JOIN (SELECT Identificacion_Funcionario, CONCAT_WS(' ', Nombres, Apellidos) AS Funcionario, Imagen FROM Funcionario) F ON F.Identificacion_Funcionario = A.Identificacion_Funcionario WHERE A.Id_Gasto_Punto = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

}

echo json_encode($resultado);

?>