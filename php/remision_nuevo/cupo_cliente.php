<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.consulta.php';

$id_destino = (isset($_REQUEST['id_destino']) ? $_REQUEST['id_destino'] : '');

$respuesta = getCupoCliente($id_destino);

echo json_encode($respuesta);
exit;
function getCupoCliente($cliente)
{
      $query = "SELECT 
            C.Cupo,
            R.Id_Cliente,
            R.Nombre,
            MAX(R.Dias_Mora) AS Dias_Mora,
            SUM(R.TOTAL) AS CupoUsado,
            IFNULL(RE.Costo_Remision, 0) AS Cupo_Remisiones,
            RE.Cods
            FROM
            (SELECT 
                  MC.Id_PLan_Cuenta,
                        C.Id_Cliente,
                        C.Nombre,
                        MC.Fecha_Movimiento,
                        IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago, 0), 0) AS Dias_Mora,
                        (CASE PC.Naturaleza
                        WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                        ELSE (SUM(MC.Debe) - SUM(MC.Haber))
                        END) AS TOTAL
            FROM
                  Movimiento_Contable MC
            INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
            INNER JOIN Cliente C ON C.Id_Cliente = MC.Nit
            WHERE
                  MC.Estado != 'Anulado'
                        AND C.Id_Cliente = $cliente
                        AND Id_Plan_Cuenta = 57
            GROUP BY MC.Documento , C.Id_Cliente , MC.Id_Plan_Cuenta
            HAVING TOTAL != 0) R
                  INNER JOIN
            Cliente C ON C.Id_Cliente = R.Id_Cliente
                  LEFT JOIN
            (SELECT 
                  RE.Id_Destino,
                        SUM(PR.Cantidad* PR.Precio * (1-PR.Descuento/100) * (1+ PR.Impuesto/100)) AS Costo_Remision,
                        GROUP_CONCAT(RE.Codigo) AS Cods
            FROM
                  Remision RE
            Inner Join Producto_Remision PR on PR.Id_Remision = RE.Id_Remision
            WHERE
                  RE.Estado NOT IN ('Facturada' , 'Anulada')
                        AND RE.Tipo_Destino = 'Cliente'
            GROUP BY RE.Id_Destino) RE ON RE.Id_Destino = C.Id_Cliente
            WHERE
            C.Cupo > 0";

      $oCon = new consulta();
      $oCon->setQuery($query);
      $cupo = $oCon->getData();
      return $cupo;
}
