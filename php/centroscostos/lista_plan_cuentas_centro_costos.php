<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.consulta.php';

$id_centro = isset($_REQUEST['Id_Centro_Costo']) ? $_REQUEST['Id_Centro_Costo'] : '';

$condicion = '';
if ($id_centro !== '') {
    $condicion = ' AND PCCC.Id_Centro_Costo = ' . $id_centro . ' ';
}

$query = 'SELECT PCCC.Id_Plan_Cuentas_Centro_Costos,
                 PCCC.Id_Plan_Cuentas,
                 PCCC.Id_Centro_Costo,
                 PCCC.Estado,
                 DATE_FORMAT(PCCC.Fecha_Registro, "%Y-%m-%d %H:%i:%s") AS Fecha_Registro,
                 PC.Codigo,
                 PC.Nombre AS Cuenta
          FROM Plan_Cuentas_Centro_Costos PCCC
          INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = PCCC.Id_Plan_Cuentas
          WHERE 1 = 1 ' . $condicion . '
          ORDER BY PC.Codigo';

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);

echo json_encode($data);
