<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$nit = isset($_REQUEST['nit']) ? $_REQUEST['nit'] : false;

if ($nit) {
    $query = "SELECT 
    MC.Id_Plan_Cuenta,
    PC.Codigo,
    PC.Nombre,
    DATE_FORMAT(MAX(MC.Fecha_Movimiento), '%d/%m/%Y') AS Fecha,
    MC.Documento AS Factura,	
    MC.Id_Registro_Modulo AS Id_Factura,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Valor_Factura,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Debe))
        ELSE (SUM(MC.Haber))
    END) AS Valor_Abono,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
        ELSE (SUM(MC.Debe) - SUM(MC.Haber))
    END) AS Valor_Saldo,
    PC.Naturaleza AS Nat,
    SUM(MC.Debe) AS Movimiento_Debito,
    SUM(MC.Haber) AS Movimiento_Credito,
    '0' AS Seleccionado
    FROM
    Movimiento_Contable MC
        INNER JOIN
    Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
    WHERE
    MC.Nit = $nit AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 74
    GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo != 0 ORDER BY MAX(MC.Fecha_Movimiento)" ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $res = $oCon->getData();
    unset($oCon);


    echo json_encode($res);
}


          
?>