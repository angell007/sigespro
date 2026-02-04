<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.consulta.php';

$query = "SELECT Id_Plan_Cuentas AS value,
                 CONCAT(Codigo,' - ',Nombre) AS label
          FROM Plan_Cuentas
          WHERE Estado = 'Activo' AND Movimiento = 'S'
          ORDER BY Codigo";

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);

echo json_encode($data);
