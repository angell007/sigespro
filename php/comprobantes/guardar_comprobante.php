<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('funciones.php');
require('../contabilidad/funciones.php');


$contabilizar = new Contabilizar();

$datos = isset($_REQUEST['Datos']) ? $_REQUEST['Datos'] : false;
$facturas = isset($_REQUEST['Facturas']) ? $_REQUEST['Facturas'] : false;
$categorias = isset($_REQUEST['Categorias']) ? $_REQUEST['Categorias'] : false;
$retenciones = isset($_REQUEST['Retenciones']) ? $_REQUEST['Retenciones'] : false;

$datos = json_decode($datos, true);
$facturas = json_decode($facturas, true);
$categorias = json_decode($categorias, true);
$retenciones = json_decode($retenciones, true);

$datos_movimiento_contable = array();

$mes = isset($datos['Fecha_Comprobante']) ? date('m', strtotime($datos['Fecha_Comprobante'])) : date('m');
$anio = isset($datos['Fecha_Comprobante']) ? date('Y', strtotime($datos['Fecha_Comprobante'])) : date('Y');

$contabilizar = new Contabilizar();
if ($contabilizar->validarMesOrAnioCerrado("$anio-$mes-01")) {
    $cod = generarConsecutivo('Ingreso', $mes, $anio);
    $datos['Codigo'] = $cod;

    if (count($facturas) > 0) {
        $datos['Tipo_Movimiento'] = 2;
    }

    $oItem = new complex("Comprobante", "Id_Comprobante");

    $datos['Id_Cuenta'] = $datos['Id_Banco'];

    foreach ($datos as $index => $value) {
        $oItem->$index = $value;
    }

    $oItem->save();
    $id_comprobante = $oItem->getId();
    unset($oItem);

    /* AQUI GENERA QR */
    $qr = generarqr('comprobante', $id_comprobante, '/IMAGENES/QR/');
    $oItem = new complex("Comprobante", "Id_Comprobante", $id_comprobante);
    $oItem->Codigo_Qr = $qr;
    $oItem->save();
    unset($oItem);
    /* HASTA AQUI GENERA QR */

    if (count($facturas) > 0) {

        foreach ($facturas as $fact) {

            $oItem = new complex('Factura_Comprobante', 'Id_Factura_Comprobante');
            $oItem->Id_Comprobante = $id_comprobante;
            $oItem->Factura = $fact['Codigo'];
            $oItem->Excenta = $fact['Exenta'] != '' ? $fact['Exenta'] : '0';
            $oItem->Gravada = $fact['Gravada'] != '' ? $fact['Gravada'] : '0';
            $oItem->Iva = $fact['Iva'] != '' ? $fact['Iva'] : '0';
            $oItem->Total = $fact['Total_Compra'] != '' ? $fact['Total_Compra'] : '0';
            $oItem->Neto_Factura = $fact['Neto_Factura'] != '' ? $fact['Neto_Factura'] : '0';
            $valor = number_format((float)$fact['ValorIngresado'], 2, ".", "");
            $oItem->Valor = $valor;
            $oItem->Id_Factura = $fact['Id_Factura'] != '' ? $fact['Id_Factura'] : '0';
            $oItem->ValorDescuento = isset($fact['ValorDescuento']) ? number_format($fact['ValorDescuento'], 2, ".", "") : '0';
            $oItem->ValorMayorPagar = isset($fact['ValorMayorPagar']) ? number_format($fact['ValorMayorPagar'], 2, ".", "") : '0';

            if (isset($fact['Id_Cuenta_Descuento'])) {
                $oItem->Id_Cuenta_Descuento = $fact['Id_Cuenta_Descuento'];
            }
            if (isset($fact['Id_Cuenta_MayorPagar'])) {
                $oItem->Id_Cuenta_MayorPagar = $fact['Id_Cuenta_MayorPagar'];
            }
            $oItem->save();
            $id_factura_comprobante = $oItem->getId();

            unset($oItem);

            if (count($fact['RetencionesFacturas']) > 0) {

                foreach ($fact['RetencionesFacturas'] as $ret) {
                    if ($ret['Id_Retencion'] != '') {
                        $oItem = new complex('Retencion_Comprobante', 'Id_Retencion_Comprobante');
                        $oItem->Id_Factura = $fact['Id_Factura'];
                        $oItem->Id_Comprobante = $id_comprobante;
                        $oItem->Id_Retencion = $ret['Id_Retencion'];
                        $valor = number_format((float)$ret['Valor'], 2, ".", "");
                        $oItem->Valor = $valor;
                        $oItem->save();

                        unset($oItem);
                    }
                }
            }

            if (count($fact['DescuentosFactura']) > 0) {

                foreach ($fact['DescuentosFactura'] as $ret) {
                    $oItem = new complex('Descuento_Comprobante', 'Id_Descuento_Comprobante');
                    $oItem->Id_Factura = $fact['Id_Factura'];
                    $oItem->Id_Comprobante = $id_comprobante;
                    $oItem->Id_Plan_Cuenta_Descuento = $ret['Id_Cuenta_Descuento'];
                    $valor = number_format((float)$ret['ValorDescuento'], 2, ".", "");
                    $oItem->Valor = $valor;
                    $oItem->save();

                    unset($oItem);
                }
            }

            // cambiarEstadoFactura($datos['Id_Cliente'],$fact['Codigo'],57); // 57 es plan de cuenta 130505 que es para las facturas.

            /* $query = "SELECT 
        FC.Factura AS Cod_Factura, 
        FC.Id_Factura, 
        ROUND(FC.Neto_Factura) AS Neto_Factura, 
        ROUND(FC.Valor+IFNULL(RC.Valor,0)+IFNULL(FC.ValorDescuento,0)-IFNULL(FC.ValorMayorPagar,0)) AS Pagado,
        C.Tipo AS Tipo_Factura 
        FROM Factura_Comprobante FC 
        INNER JOIN Comprobante C ON C.Id_Comprobante = FC.Id_Comprobante 
        LEFT JOIN (SELECT Id_Factura, Id_Comprobante, SUM(Valor) AS Valor FROM Retencion_Comprobante GROUP BY Id_Factura, Id_Comprobante) RC ON FC.Id_Factura = RC.Id_Factura AND C.Id_Comprobante = RC.Id_Comprobante
        WHERE FC.Factura = '$fact[Codigo]' AND FC.Id_Factura = $fact[Id_Factura] 
        GROUP BY FC.Id_Factura 
        HAVING Neto_Factura = Pagado";

        $oCon = new Consulta();
        $oCon->setQuery($query);
        // $oCon->setTipo('Multiple');
        $res = $oCon->getData();
        unset($oCon);

        if ($res) {
            if ($res['Tipo_Factura'] == "Ingreso") {

                $is_nota_credito = strpos($fact['Codigo'],'NC');

                if ($is_nota_credito !== false) {
                    $query = "UPDATE Nota_Credito SET Estado = 'Pagada' WHERE Codigo = '$fact[Codigo]' AND Id_Nota_Credito = $fact[Id_Factura]";
                } else {
                    $query = "UPDATE Factura_Venta SET Estado = 'Pagada' WHERE Codigo = '$fact[Codigo]' AND Id_Factura_Venta = $fact[Id_Factura]";
                
                    $oCon = new Consulta();
                    $oCon->setQuery($query);
                    $oCon->createData();
                    unset($oCon);
                    
                    $query = "UPDATE Factura SET Estado_Factura = 'Pagada' WHERE Codigo = '$fact[Codigo]' AND Id_Factura = $fact[Id_Factura]";
                    
                    $oCon = new Consulta();
                    $oCon->setQuery($query);
                    $oCon->createData();
                    unset($oCon);
                }
                

            } else {
                $query = "UPDATE Factura_Acta_Recepcion SET Estado = 'Pagada' WHERE Factura = '$fact[Codigo]' AND Id_Factura_Acta_Recepcion = $fact[Id_Factura]";
                
                $oCon = new Consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);
                
                $query = "UPDATE Facturas_Proveedor_Mantis SET Estado = 'Pagada' WHERE Factura = '$fact[Codigo]' AND Id_Facturas_Proveedor_Mantis = $fact[Id_Factura]";
                
                $oCon = new Consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);
                
                $query = "UPDATE Nota_Contable SET Egreso = 'Si' WHERE Documento = '$fact[Codigo]' AND Id_Nota_Contable = $fact[Id_Factura]";
                
                $oCon = new Consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);
            }
        } */

            //registrarContabilidad($fact['Id_Factura'], $fact['Codigo'], $id_comprobante, $id_factura_comprobante, $datos['Tipo'], $fact['RetencionesFacturas'], number_format((FLOAT)$fact['ValorIngresado'],2,".",""));


        }
    } else {
        unset($categorias[count($categorias) - 1]);
        foreach ($categorias as $cat) {
            $oItem = new complex('Cuenta_Contable_Comprobante', 'Id_Cuenta_Contable_Comprobante');
            $oItem->Id_Plan_Cuenta = $cat['Id_Plan_Cuentas'];
            $oItem->Valor = $cat['Valor'];
            $oItem->Impuesto = $cat['Impuesto'];
            $oItem->Cantidad = $cat['Cantidad'];
            $oItem->Observaciones = $cat['Observaciones'];
            $oItem->Subtotal = $cat['Subtotal'];
            $oItem->Id_Comprobante = $id_comprobante;
            $oItem->save();

            unset($oItem);
        }

        if (count($retenciones) > 0) {
            foreach ($retenciones as $ret) {
                $oItem = new complex('Retencion_Comprobante', 'Id_Retencion_Comprobante');
                $oItem->Id_Comprobante = $id_comprobante;
                $oItem->Id_Retencion = $ret['Id_Retencion'];
                $oItem->Valor = $ret['Valor'];
                $oItem->save();

                unset($oItem);
            }
        }
    }
    if ($id_comprobante) {

        $datos_movimiento_contable['Id_Registro'] = $id_comprobante;
        $datos_movimiento_contable['Nit'] = $datos['Tipo'] == 'Ingreso' ? $datos['Id_Cliente'] : $datos['Id_Proveedor'];
        $datos_movimiento_contable['Id_Cuenta'] = $datos['Id_Banco'];
        $datos_movimiento_contable['Valor_Banco'] = $datos['Valor_Banco'];
        $datos_movimiento_contable['Tipo_Comprobante'] = $datos['Tipo'];
        $datos_movimiento_contable['Fecha_Comprobante'] = $datos['Fecha_Comprobante'];
        $datos_movimiento_contable['Facturas'] = $facturas;
        $datos_movimiento_contable['Valores_Comprobante'] = $categorias;
        $datos_movimiento_contable['Retenciones'] = $retenciones;


        $contabilizar->CrearMovimientoContable('Comprobante', $datos_movimiento_contable);

        pagarFacturas($facturas, $datos['Id_Cliente']); // Llamando la función que va a cambiar el estado de la facturas a "pagada" si se da el caso.

        $resultado['mensaje'] = "Se ha registrado un comprobante de " . $datos['Tipo'] . " satisfactoriamente";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Operación Exitosa!";
        $resultado['id'] = $id_comprobante;
    } else {
        $resultado['mensaje'] = "Ha ocurrido un error de conexión, comunicarse con el soporte técnico.";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error!";
    }
}
else{
    $resultado['mensaje'] = "El documento no se puede guardar porque existe un cierre para la fecha del documento";
    $resultado['titulo'] = "Error!";
    $resultado['tipo'] = "error";
}
echo json_encode($resultado);


function pagarFacturas($facturas, $id_cliente)
{

    foreach ($facturas as $i => $fact) {
        cambiarEstadoFactura($id_cliente, $fact['Codigo'], 57); // 57 es plan de cuenta 130505 que es para las facturas.
    }
}
