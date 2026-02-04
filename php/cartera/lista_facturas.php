<?php
ini_set('memory_limit', '-1');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "SELECT 
    DATE(MC.Fecha_Movimiento) AS Fecha_Documento,
    MC.Documento AS Codigo,
    0 AS Exenta,
    0 AS Gravada,
    0 AS Iva,
    (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber))
            ELSE (SUM(MC.Debe))
        END) AS Total_Compra,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
        ELSE (SUM(MC.Debe) - SUM(MC.Haber))
    END) AS Neto_Factura,
    PC.Naturaleza AS Nat,
    DATE_ADD(DATE(MC.Fecha_Movimiento), INTERVAL IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) DAY) AS Fecha_Vencimiento,
    IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) AS Condicion_Pago,

    IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago, 0), 0) AS Dias_Mora
        
    FROM
    Movimiento_Contable MC
        INNER JOIN
    Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        INNER JOIN
    Cliente C ON C.Id_Cliente = MC.Nit
    WHERE
    MC.Estado != 'Anulado'
        AND Id_Plan_Cuenta = 57
        AND MC.Nit = $id
    GROUP BY MC.Id_Plan_Cuenta , MC.Documento
    HAVING Neto_Factura != 0
    ORDER BY MC.Fecha_Movimiento DESC";

    


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
$i=-1;
foreach ($resultado as $value) {$i++;
    $resultado[$i]['RetencionesFacturas']=[];
    $resultado[$i]['Condicion_Pago']= (INT) $value['Condicion_Pago'];
    $resultado[$i]['Dias_Mora']= (INT) $value['Dias_Mora'];
}

$query='SELECT * FROM Cliente WHERE Id_Cliente='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);

$datos['Facturas']=$resultado;
$datos['Cliente']=$cliente;


echo json_encode($datos);


?>