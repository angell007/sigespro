<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();

$fecha1 = "2019-08-01";
$fecha2 = "2019-08-31";

$query_configuracion = '
  SELECT
    Valor_Unidad_Tributaria,
    Base_Retencion_Compras_Reg_Comun,
    Base_Retencion_Compras_Reg_Simpl,
    Base_Retencion_Compras_Ica,
    Base_Retencion_Iva_Reg_Comun
  FROM Configuracion
  WHERE
      Id_Configuracion = 1';

$oItem->SetQuery($query_configuracion);
$retenciones_conf = $oItem->ExecuteQuery('simple');


$queryFactura = "SELECT * FROM Acta_Recepcion WHERE Id_Acta_Recepcion IN (7145)";
// $queryFactura = "SELECT * FROM Acta_Recepcion AR WHERE DATE(AR.Fecha_Creacion) BETWEEN '$fecha1' AND '$fecha2' AND AR.Estado = 'Aprobada'";


$oItem->SetQuery($queryFactura);
$actas = $oItem->ExecuteQuery('Multiple');

/* echo "<pre>";
var_dump($facturas);
echo "</pre>";
exit; */

foreach ($actas as $cont => $acta) {
    $contabilizacion = new Contabilizar(true);

    $query = "SELECT *, (Cantidad*Precio*(Impuesto/100)) AS Iva FROM Producto_Acta_Recepcion WHERE Id_Acta_Recepcion = $acta[Id_Acta_Recepcion]";

    // echo $query . " -- PROD <br>";
    

    $retencionesProveedor = getRetencionProveedor($acta['Id_Proveedor']);
    
    $oItem->SetQuery($query);
    $resultado[] = ["producto" => $oItem->ExecuteQuery('Multiple')];
    

    /* foreach ($resultado as $i => $value) {
        $resultado[$i]['producto'][] = $value;
    } */
    
    $query = "SELECT * FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = $acta[Id_Acta_Recepcion]";

    // echo $query . " -- FACT <br>";

    $oItem->SetQuery($query);
    $facturas = $oItem->ExecuteQuery('Multiple');

    foreach ($facturas as $i => $value) {
        $facturas[$i]['Retenciones'] = $retencionesProveedor;
        $facturas[$i]['Retenciones'] = calcularRetencionesFactura($facturas[$i],$acta['Id_Proveedor']);
    }

    foreach ($facturas as $fact) {
        if (count($fact['Retenciones']) > 0) {
                
            foreach ($fact['Retenciones'] as $rt) {

                if ($rt['Valor'] != '' && $rt['Valor'] > 0) {
                    $oItem2 = new complex("Factura_Acta_Recepcion_Retencion","Id_Factura_Acta_Recepcion_Retencion");
                    $oItem2->Id_Factura = $fact['Id_Factura_Acta_Recepcion'];
                    $oItem2->Id_Retencion =$rt['Id_Retencion'];
                    $oItem2->Id_Acta_Recepcion = $acta['Id_Acta_Recepcion'];
                    $oItem2->Valor_Retencion = $rt['Valor'] != '' ? number_format(floatval($rt['Valor']),0,".","") : '0';
                    $oItem2->save();
                    unset($oItem2);
                }
            }
        }
    }

    $datos_movimiento_contable['Id_Registro'] = $acta['Id_Acta_Recepcion'];
    $datos_movimiento_contable['Numero_Comprobante'] = $acta['Codigo'];
    $datos_movimiento_contable['Nit'] = $acta['Id_Proveedor'];
    $datos_movimiento_contable['Productos'] = $resultado;
    $datos_movimiento_contable['Facturas'] = $facturas;

    $contabilizacion->CrearMovimientoContable('Acta Recepcion',$datos_movimiento_contable);
    unset($contabilizacion);

    $resultado = [];
}

function retencionProveedor($id_proveedor){

    global $oItem;
    
    $query = 'SELECT
    P.Tipo_Retencion,
    P.Id_Plan_Cuenta_Retefuente,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "", (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = P.Id_Plan_Cuenta_Retefuente))) AS Nombre_Retefuente,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Porcentaje FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Retefuente))) AS Porcentaje_Retefuente,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Id_Retencion FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Retefuente))) AS Id_Retencion_Fte,
    P.Tipo_Reteica,
    P.Id_Plan_Cuenta_Reteica,
    (IF(P.Id_Plan_Cuenta_Reteica IS NULL OR P.Id_Plan_Cuenta_Reteica = 0, "", (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = P.Id_Plan_Cuenta_Reteica))) AS Nombre_Reteica,
    (IF(P.Id_Plan_Cuenta_Reteica IS NULL OR P.Id_Plan_Cuenta_Reteica = 0, "0", (SELECT Porcentaje FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteica))) AS Porcentaje_Reteica,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Id_Retencion FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteica))) AS Id_Retencion_Ica,
    P.Contribuyente,
    P.Id_Plan_Cuenta_Reteiva,
    (IF(P.Id_Plan_Cuenta_Reteiva IS NULL OR P.Id_Plan_Cuenta_Reteiva = 0, "", (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = P.Id_Plan_Cuenta_Reteiva))) AS Nombre_Reteiva,
    (IF(P.Id_Plan_Cuenta_Reteiva IS NULL OR P.Id_Plan_Cuenta_Reteiva = 0, "0", (SELECT Porcentaje FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteiva))) AS Porcentaje_Reteiva,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Id_Retencion FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteiva))) AS Id_Retencion_Iva,
    Regimen
  FROM Proveedor P
  WHERE
    Id_Proveedor ='. $id_proveedor;

    // echo $query . " -- RET PROV <br>";

    $oItem->SetQuery($query);
    $proveedor = $oItem->ExecuteQuery('simple');

    return $proveedor;
}

function getRetencionProveedor($id_proveedor){
    
    $proveedor = retencionProveedor($id_proveedor);

    // echo "Proveedor -- $id_proveedor <br>";
    
    $retenciones = [];

    if ($proveedor['Id_Plan_Cuenta_Retefuente'] != 0 && $proveedor['Id_Plan_Cuenta_Retefuente'] != '') {
        $r = [
            "Nombre" => $proveedor['Nombre_Retefuente'],
            "Valor" => 0,
            "Id_Retencion" => $proveedor['Id_Retencion_Fte'],
            "Porcentaje" => $proveedor['Porcentaje_Retefuente'],
            "Tipo" => "Renta",
            "Tipo_R" => $proveedor['Tipo_Retencion']
        ];

        $retenciones[] = $r;
    }
    
    if ($proveedor['Id_Plan_Cuenta_Reteica'] != 0 && $proveedor['Id_Plan_Cuenta_Reteica'] != '') {
        $r = [
            "Nombre" => $proveedor['Nombre_Reteica'],
            "Valor" => 0,
            "Id_Retencion" => $proveedor['Id_Retencion_Ica'],
            "Porcentaje" => $proveedor['Porcentaje_Reteica'],
            "Tipo" => "Ica",
            "Tipo_R" => $proveedor['Tipo_Reteica']
        ];

        $retenciones[] = $r;
    }
    
    if ($proveedor['Id_Plan_Cuenta_Reteiva'] != 0 && $proveedor['Id_Plan_Cuenta_Reteiva'] != '') {
        $r = [
            "Nombre" => $proveedor['Nombre_Reteiva'],
            "Valor" => 0,
            "Id_Retencion" => $proveedor['Id_Retencion_Iva'],
            "Porcentaje" => $proveedor['Porcentaje_Reteiva'],
            "Tipo" => "Iva",
            "Tipo_R" => $proveedor['Tipo_Reteiva']
        ];

        $retenciones[] = $r;
    }

    return $retenciones;

}

echo "Registro exitoso";

function calcularRetencionesFactura($factura,$id_proveedor){

    $ValorMinimoAplica = OperacionParaValoresMinimosRetenciones($id_proveedor);
    $total_factura = getTotalFactura($factura['Factura'], $factura['Id_Acta_Recepcion']);
    $valor_final = 0;

    if (count($factura['Retenciones'])> 0) {
        foreach ($factura['Retenciones'] as $i => $ret) {
            if ($ret['Tipo'] == 'Renta') {
                if ($ret['Tipo_R'] == 'Supera Base') {
                    // echo "ENTRÓ -- $ret[Tipo_R] <br>";
                    if ($total_factura['Total_Factura'] > $ValorMinimoAplica['retefuente']) {
                        $valor_final = $total_factura['Total_Factura'] * ($ret['Porcentaje']/100);
                        $factura['Retenciones'][$i]['Valor'] = $valor_final;
                    } else{
                        $valor_final = 0;
                        $factura['Retenciones'][$i]['Valor'] = $valor_final;
                    }
                } elseif ($ret['Tipo_R'] == 'Permanente') {
                    $valor_final = $total_factura['Total_Factura'] * ($ret['Porcentaje']/100);
                    $factura['Retenciones'][$i]['Valor'] = $valor_final;
                } else {
                    $valor_final = 0;
                    $factura['Retenciones'][$i]['Valor'] = $valor_final;
                }
            } 
            
            if ($ret['Tipo'] == 'Ica') {
                if ($ret['Tipo_R'] == 'Supera Base') {
                    if ($total_factura['Total_Factura'] > $ValorMinimoAplica['reteica']) {
                        $valor_final = $total_factura['Total_Factura'] * ($ret['Porcentaje']/100);
                        $factura['Retenciones'][$i]['Valor'] = $valor_final;
                        // echo "####################################<br>";
                        // echo $valor_final;
                        // echo "####################################<br>";
                    } else{
                        $valor_final = 0;
                        $factura['Retenciones'][$i]['Valor'] = $valor_final;
                    }
                } elseif ($ret['Tipo_R'] == 'Permanente') {
                    $valor_final = $total_factura['Total_Factura'] * ($ret['Porcentaje']/100);
                    $factura['Retenciones'][$i]['Valor'] = $valor_final;
                } else {
                    $valor_final = 0;
                    $factura['Retenciones'][$i]['Valor'] = $valor_final;
                }
            } 
            
            if ($ret['Tipo'] == 'Iva') {
                if ($ret['Tipo_R'] == 'No') {
                    if ($total_factura['Total_Iva'] > $ValorMinimoAplica['reteiva']) {
                        $valor_final = $total_factura['Total_Iva'] * ($ret['Porcentaje']/100);
                        $factura['Retenciones'][$i]['Valor'] = $valor_final;
                    } else{
                        $valor_final = 0;
                        $factura['Retenciones'][$i]['Valor'] = $valor_final;
                    }
                } else {
                    $valor_final = 0;
                    $factura['Retenciones'][$i]['Valor'] = $valor_final;
                }
            } 
        }
    }

    return $factura['Retenciones'];
    
}

function OperacionParaValoresMinimosRetenciones($id_proveedor){

    global $retenciones_conf;

    $retencionesProveedor = retencionProveedor($id_proveedor);
    $retenciones_minimo = [];

    if ($retencionesProveedor['Id_Plan_Cuenta_Retefuente'] != 0) {
        if ($retencionesProveedor['Regimen'] == 'Comun') {
            $retenciones_minimo['retefuente'] = $retenciones_conf['Valor_Unidad_Tributaria'] * $retenciones_conf['Base_Retencion_Compras_Reg_Comun'];
        } else {
            $retenciones_minimo['retefuente'] = $retenciones_conf['Valor_Unidad_Tributaria'] * $retenciones_conf['Base_Retencion_Compras_Reg_Simpl'];
        }
    }
    
    if ($retencionesProveedor['Id_Plan_Cuenta_Reteica'] != 0) {
        $retenciones_minimo['reteica'] = $retenciones_conf['Valor_Unidad_Tributaria'] * $retenciones_conf['Base_Retencion_Compras_Ica'];
    }
    
    if ($retencionesProveedor['Id_Plan_Cuenta_Reteiva'] != 0) {
        $retenciones_minimo['reteiva'] = $retenciones_conf['Valor_Unidad_Tributaria'] * $retenciones_conf['Base_Retencion_Iva_Reg_Comun'];
    }

    return $retenciones_minimo;
}

function getTotalFactura($factura, $id_acta){

    global $oItem;
    
    $query = "SELECT SUM((Cantidad*Precio)) AS Total_Factura, SUM((Cantidad*Precio)*((Impuesto/100))) AS Total_Iva FROM Producto_Acta_Recepcion WHERE Factura = '$factura' AND Id_Acta_Recepcion = $id_acta";

    // echo $query . "<br>";

    $oItem->SetQuery($query);
    $factura = $oItem->ExecuteQuery('simple');

    return $factura;
}


?>