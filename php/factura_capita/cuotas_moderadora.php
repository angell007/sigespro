<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_cliente = isset($_REQUEST['client']) ? $_REQUEST['client'] : false;
$id_dep = isset($_REQUEST['dep']) ? $_REQUEST['dep'] : false;
$mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : false;
$punto = isset($_REQUEST['pto']) ? $_REQUEST['pto'] : false;

$cond_punto = '';

if ($punto && $punto != "0") { // "0" Significa que seleccionó "TODOS" en el campo de puntos.
    $cond_punto .= " AND D.Id_Punto_Dispensacion = $punto";
}

$id_tipo_servicio=GetServicioCapita();

$query = "SELECT IFNULL(SUM(D.Cuota),0) AS Cuotas
FROM Dispensacion D 
INNER JOIN Punto_Dispensacion PD ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion 
LEFT JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente
WHERE D.Pendientes = 0
AND D.Estado_Facturacion = 'Sin Facturar' 
AND D.Estado_Dispensacion != 'Anulada' 
AND D.Id_Tipo_Servicio = $id_tipo_servicio
AND D.Fecha_Actual LIKE '$mes%' 
AND P.Nit = $id_cliente
AND PD.Departamento = $id_dep 
AND P.Id_Regimen = 1 $cond_punto";

$con = new consulta();
$con->setQuery($query);
$resultado = $con->getData();
unset($con);

echo json_encode($resultado);

function GetServicioCapita(){
    $query="SELECT Id_Tipo_Servicio FROM Tipo_Servicio WHERE Nombre LIKE '%Capita%' ";
    $con = new consulta();
    $con->setQuery($query);
    $capita = $con->getData();
    unset($con);

    return $capita['Id_Tipo_Servicio'];
}

?>	