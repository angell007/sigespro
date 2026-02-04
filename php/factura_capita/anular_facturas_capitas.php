<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT * FROM Factura_Capita WHERE Codigo IN ('BO3729','CL98','CL99','BG193','CU440','QU50','BO3730','CL100','BG194','CU441','QU51')";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();

foreach ($facturas as $i => $value) {
    anularFacturaCapita($value['Id_Factura_Capita']);
    echo "<br>Factura Capita $value[Codigo] ANULADA<br>";
    removerFacturaDispensacionCapita($value);
    echo "<br>Dispensacion capitas asociadas al $value[Codigo] removidas<br>";
    anularMovimientoContableCapita($value['Id_Factura_Capita']);
    echo "<br>Contabilización de Factura Capita $value[Codigo] ANULADA<br>";
}

echo "<br>Terminó completamente...<br>";

function anularFacturaCapita($id) {
    $oItem = new complex('Factura_Capita','Id_Factura_Capita',$id);
    $oItem->Estado_Factura = 'Anulada';
    $oItem->save();
    unset($oItem);
}

function removerFacturaDispensacionCapita($datos) {
    $cond_punto = '';

    if ($datos['Id_Punto_Dispensacion'] != 0) {
        $cond_punto .= " AND D.Id_Punto_Dispensacion = $datos[Id_Punto_Dispensacion]";
    }
    // ID SERVICIO 7 ES CAPITA.
    $query = "UPDATE Dispensacion D INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion INNER JOIN Paciente P ON D.Numero_Documento=P.Id_Paciente SET D.Id_Factura = NULL, D.Estado_Facturacion = 'Sin Facturar', D.Fecha_Facturado = NULL, Identificacion_Facturador = 0 WHERE D.Id_Tipo_Servicio=7 AND D.Pendientes=0 AND D.Estado_Facturacion = 'Sin Facturar' AND D.Estado_Dispensacion != 'Anulada' AND (DATE_FORMAT(D.Fecha_Actual,'%Y-%m') = '$datos[Mes]' OR D.Fecha_Actual < '$datos[Mes]-01 00:00:00') AND P.Nit=$datos[Id_Cliente] AND PD.Departamento=$datos[Id_Departamento] AND P.Id_Regimen = $datos[Id_Regimen] $cond_punto";

    $con = new consulta();
    $con->setQuery($query);
    $con->createData();
    unset($con);
}

function anularMovimientoContableCapita($id) {
    $query = "UPDATE Movimiento_Contable SET Estado = 'Anulado' WHERE Id_Modulo = 3 AND Id_Registro_Modulo = $id";

    $con = new consulta();
    $con->setQuery($query);
    $con->createData();
    unset($con);
}
?>	