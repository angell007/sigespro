<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('../../../class/class.contabilizar.php');
require_once('../../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('../../comprobantes/funciones.php');
require('../funciones.php');

$datos = isset($_REQUEST['Datos']) ? $_REQUEST['Datos'] : false;
$cuentas_contables = isset($_REQUEST['Cuentas_Contables']) ? $_REQUEST['Cuentas_Contables'] : false;

$datos = json_decode($datos, true);
$cuentas_contables = json_decode($cuentas_contables, true);

/* var_dump($datos);
var_dump($cuentas_contables);
exit; */
if (!validarSuma($cuentas_contables)) {
    $resultado['mensaje'] = "El documento no est¨¢ en balance, por tanto no se puede guardar";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Operaci¨®n Fallida!";
} else {
    $mes = isset($datos['Fecha_Documento']) ? date('m', strtotime($datos['Fecha_Documento'])) : date('m');
    $anio = isset($datos['Fecha_Documento']) ? date('Y', strtotime($datos['Fecha_Documento'])) : date('Y');
    $contabilizar = new Contabilizar();
    if ($contabilizar->validarMesOrAnioCerrado("$anio-$mes-01")) {
        $tipo = $datos['Tipo_Doc'];
        
        switch ($tipo) {
            case 'Debito-Cliente':
                $tipo = 'Nota Debito Cliente';
                break;
            
            case 'Credito-Cliente':
                $tipo = 'Nota Credito Cliente';
                break;
            case 'Debito-Proveedor':
                $tipo = 'Nota Debito Proveedor';
                break;
            case 'Credito-Proveedor':
                $tipo = 'Nota Credito Proveedor';
                break;
        }
        
        $cod = generarConsecutivo($tipo, $mes, $anio);
        $datos['Codigo']=$cod;
        
        $oItem = new complex("Documento_Contable","Id_Documento_Contable");
        
        if (!isset($datos['Id_Centro_Costo']) || $datos['Id_Centro_Costo'] == '') {
            $datos['Id_Centro_Costo'] = 0;
        }
        
        $datos['Tipo'] = 'Nota Cartera';
        
        foreach ($datos as $index => $value) {
            $oItem->$index = $value;
        }
        
        $oItem->save();
        $id_nota_contable = $oItem->getId();
        unset($oItem);
        
        /* AQUI GENERA QR */
        $qr = generarqr('notacontable',$id_nota_contable,'/IMAGENES/QR/');
        $oItem = new complex("Documento_Contable","Id_Documento_Contable",$id_nota_contable);
        $oItem->Codigo_Qr=$qr;
        $oItem->save();
        unset($oItem);
        /* HASTA AQUI GENERA QR */
        
        unset($cuentas_contables[count($cuentas_contables)-1]);
        
        foreach ($cuentas_contables as $cuenta) {
            $oItem = new complex('Cuenta_Documento_Contable', 'Id_Cuenta_Documento_Contable');
            $oItem->Id_Documento_Contable = $id_nota_contable;
            $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'];
            $oItem->Nit = $cuenta['Nit_Cuenta'];
            $oItem->Tipo_Nit = $cuenta['Tipo_Nit'];
            $oItem->Id_Centro_Costo = isset($cuenta['Id_Centro_Costo']) && $cuenta['Id_Centro_Costo'] != '' ? $cuenta['Id_Centro_Costo'] : '0';
            $oItem->Documento = $cuenta['Documento'];
            $oItem->Concepto = $cuenta['Concepto'];
            $oItem->Base = number_format($cuenta['Base'],2,".","");
            $oItem->Debito = number_format($cuenta['Debito'],2,".","");
            $oItem->Credito = number_format($cuenta['Credito'],2,".","");
            $oItem->Deb_Niif = number_format($cuenta['Deb_Niif'],2,".","");
            $oItem->Cred_Niif = number_format($cuenta['Cred_Niif'],2,".","");
            $oItem->save();
            unset($oItem);
        
            ## REGISTRAR MOVIMIENTO CONTABLE...
            $oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'];
            $oItem->Id_Modulo = 29;
            $oItem->Id_Registro_Modulo = $id_nota_contable;
            $oItem->Fecha_Movimiento = $datos['Fecha_Documento'];
            $oItem->Debe = number_format($cuenta['Debito'], 2, ".", "");
            $oItem->Debe_Niif = number_format($cuenta['Deb_Niif'], 2, ".", "");
            $oItem->Haber = number_format($cuenta['Credito'], 2, ".", "");
            $oItem->Haber_Niif = number_format($cuenta['Cred_Niif'], 2, ".", "");
            $oItem->Nit = $cuenta['Nit_Cuenta'];
            $oItem->Tipo_Nit = $cuenta['Tipo_Nit'];
            $oItem->Documento = $cuenta['Documento'];
            $oItem->Id_Centro_Costo = isset($cuenta['Id_Centro_Costo']) && $cuenta['Id_Centro_Costo'] != '' ? $cuenta['Id_Centro_Costo'] : '0';
            $oItem->Numero_Comprobante = $cod;
            $oItem->Detalles = $cuenta['Concepto'];
            $oItem->save();
            unset($oItem);
        
            cambiarEstadoFactura($cuenta['Nit_Cuenta'],$cuenta['Documento'],$cuenta['Id_Plan_Cuentas']); // Metodo que cambia el estado de la factura a "pagada"
        }
        
        if (isset($datos['Id_Borrador']) && $datos['Id_Borrador'] != '') {
            eliminarBorradorContable($datos['Id_Borrador']);
        }
        
        if ($id_nota_contable) {
            $resultado['mensaje'] = "Se ha registrado un comprobante de " . $datos['Tipo'] . " satisfactoriamente";
            $resultado['tipo'] = "success";
            $resultado['titulo'] = "OperaciÃ³n Exitosa!";
            $resultado['id'] = $id_nota_contable;
        } else {
            $resultado['mensaje'] = "Ha ocurrido un error de conexiÃ³n, comunicarse con el soporte tÃ©cnico.";
            $resultado['tipo'] = "error";
        }
    } else {
        $resultado['mensaje'] = "El documento no se puede guardar porque existe un cierre para la fecha del documento";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error!";
    }
}

echo json_encode($resultado);


function validarSuma($cuentas_contables)
{
    $sumDeb = 0;
    $sumCred = 0;
    $sumCredNif = 0;
    $sumDebNif = 0;
    foreach ($cuentas_contables as $cuenta) {
        $sumDeb += $cuenta['Debito'];
        $sumCred += $cuenta['Credito'];
        $sumDebNif += $cuenta['Deb_Niif'];
        $sumCredNif += $cuenta['Cred_Niif'];
    }

    return (number_format($sumDeb, 2, ".", "") == number_format($sumCred, 2, ".", "") && number_format($sumDebNif, 2, ".", "") == number_format($sumCredNif, 2, ".", ""));
}

?>