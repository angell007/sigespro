<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$response = [];

if ($id) {
    $query = "SELECT GP.Id_Gasto_Punto, GP.Fecha, GP.Identificacion_Funcionario, F.Nombre_Funcionario, PD.Nombre AS Punto_Dispensacion, GP.Codigo, GP.Fecha_Aprobacion, GP.Estado, GP.Codigo_Qr, GP.Anticipos, GP.Observaciones, GP.Observacion_Aprobacion FROM Gasto_Punto GP INNER JOIN (SELECT Identificacion_Funcionario, CONCAT_WS(' ',Nombres,Apellidos) AS Nombre_Funcionario FROM Funcionario) F ON (GP.Identificacion_Funcionario = F.Identificacion_Funcionario) INNER JOIN Punto_Dispensacion PD ON GP.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion WHERE GP.Id_Gasto_Punto = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $gasto = $oCon->getData();
    unset($oCon);

    // $anticipos = json_decode($gasto['Anticipos'], true);
    // unset($gasto['Anticipos']);

    $gasto['Centro_Costo'] = [];
    $response['datos'] = $gasto;

    // $response['total_saldo'] = totalSaldo($anticipos);

    $query = "SELECT IGP.* , GP.Nombre AS Tipo_Gasto , GP.Plan_Cuenta_Asociada AS Id_Plan_Cuenta_Gasto
               
                FROM Item_Gasto_Punto IGP
                INNER JOIN Tipo_Gasto GP ON IGP.Id_Tipo_Gasto = GP.Id_Tipo_Gasto
                WHERE Id_Gasto_Punto = $id";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $gastos = $oCon->getData();
    unset($oCon);

    $gastos = addNitObject($gastos);

    $response['gastos'] = $gastos;


}

echo json_encode($response);

function totalSaldo($anticipos) {
    $valor_saldo = array_column($anticipos, 'Valor_Saldo');
    $total_saldo = array_sum($valor_saldo);

    return $total_saldo;
}

function addNitObject($gastos) {
    foreach ($gastos as $i => $value) {
        $tercero = buscarNit($value['Nit']);
        if ($tercero) {
            $gastos[$i]['Nit_Nombre'] = $tercero;
            $gastos[$i]['Tipo_Nit'] = $tercero['Tipo'];
            $gastos[$i]['Nit_Encontrado'] = "Si"; 
        } else {
            $gastos[$i]['Nit_Nombre'] = $value['Nit'];
            $gastos[$i]['Tipo_Nit'] = '';
            $gastos[$i]['Nit_Encontrado'] = "No";
        }
    }

    return $gastos;
}

function buscarNit($nit) {
    $query = 'SELECT
    r.*
    FROM
    (
    SELECT C.Id_Cliente AS ID, IF(Nombre IS NULL OR Nombre = "", CONCAT_WS(" ", C.Id_Cliente,"-",Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), CONCAT(C.Id_Cliente, " - ", C.Nombre)) AS Nombre, "Cliente" AS Tipo FROM Cliente C WHERE C.Estado != "Inactivo" 
            UNION (SELECT P.Id_Proveedor AS ID, IF(P.Nombre = "" OR P.Nombre IS NULL, CONCAT_WS(" ",P.Id_Proveedor,"-",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido),CONCAT(P.Id_Proveedor, " - ", P.Nombre)) AS Nombre, "Proveedor" AS Tipo FROM Proveedor P) 
            UNION (SELECT F.Identificacion_Funcionario AS ID, CONCAT(F.Identificacion_Funcionario, " - ", F.Nombres," ", F.Apellidos) AS Nombre, "Funcionario" AS Tipo FROM Funcionario F) 
            UNION (SELECT CC.Nit AS ID, CONCAT(CC.Nit, " - ", CC.Nombre) AS Nombre, "Caja_Compensacion" AS Tipo FROM Caja_Compensacion CC WHERE CC.Nit IS NOT NULL)
    ) r WHERE r.ID = '. $nit ;
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $tercero = $oCon->getData();
    unset($oCon);

    return $tercero;
}
?>