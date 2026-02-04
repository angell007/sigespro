<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','510M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
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

$mes = isset($datos['Fecha_Documento']) ? date('m', strtotime($datos['Fecha_Documento'])) : date('m');

$cod = generarConsecutivo('Nota', $mes);
$datos['Codigo']=$cod;

$oItem = new complex("Documento_Contable","Id_Documento_Contable");

if (!isset($datos['Id_Centro_Costo']) || $datos['Id_Centro_Costo'] == '') {
    $datos['Id_Centro_Costo'] = 0;
}

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

$countCuentasContables = count($cuentas_contables);

if ($countCuentasContables >= 1000) { // Si hay más de mil cuentas para registrar, se registraran masivamente.
    registrarCuentasContablesMasivamente($cuentas_contables);
} else {
    registrarCuentasContables($cuentas_contables);
}

if (isset($datos['Id_Borrador']) && $datos['Id_Borrador'] != '') {
    eliminarBorradorContable($datos['Id_Borrador']);
}

if ($id_nota_contable) {
    $resultado['mensaje'] = "Se ha registrado un comprobante de " . $datos['Tipo'] . " satisfactoriamente";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Operación Exitosa!";
    $resultado['id'] = $id_nota_contable;
} else {
    $resultado['mensaje'] = "Ha ocurrido un error de conexión, comunicarse con el soporte técnico.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);


function registrarCuentasContables($cuentas_contables) {
    global $id_nota_contable, $datos, $cod;

    foreach ($cuentas_contables as $cuenta) {
        $oItem = new complex('Cuenta_Documento_Contable', 'Id_Cuenta_Documento_Contable');
        $oItem->Id_Documento_Contable = $id_nota_contable;
        $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'] != '' ? $cuenta['Id_Plan_Cuentas'] : '0';
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
        $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'] != '' ? $cuenta['Id_Plan_Cuentas'] : '0';
        $oItem->Id_Modulo = 5;
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
    
        // cambiarEstadoFactura($cuenta['Nit_Cuenta'],$cuenta['Documento'],$cuenta['Id_Plan_Cuentas']); // Metodo que cambia el estado de la factura a "pagada"
    }
}

function registrarCuentasContablesMasivamente($cuentas_contables) {
    global $id_nota_contable, $datos, $cod;

    $values1 = [];
    $values2 = [];

    foreach ($cuentas_contables as $cuenta) {
        $id_plan_cuenta = $cuenta['Id_Plan_Cuentas'] != '' ? $cuenta['Id_Plan_Cuentas'] : '0';
        $id_centro_costo = isset($cuenta['Id_Centro_Costo']) && $cuenta['Id_Centro_Costo'] != '' ? $cuenta['Id_Centro_Costo'] : '0';
        $base = number_format($cuenta['Base'],2,".","");
        $debe = number_format($cuenta['Debito'],2,".","");
        $haber = number_format($cuenta['Credito'],2,".","");
        $debe_niif = number_format($cuenta['Deb_Niif'],2,".","");
        $haber_niif = number_format($cuenta['Cred_Niif'],2,".","");

        $values1[] = "($id_nota_contable,$id_plan_cuenta,$cuenta[Nit_Cuenta],'$cuenta[Tipo_Nit]',$id_centro_costo,'$cuenta[Documento]','$cuenta[Concepto]',$base,$debe,$haber,$debe_niif,$haber_niif)";

        $values2[] = "($id_plan_cuenta,5,$id_nota_contable,'$datos[Fecha_Documento]',$debe,$debe_niif,$haber,$haber_niif,$cuenta[Nit_Cuenta],'$cuenta[Tipo_Nit]','$cuenta[Documento]',$id_centro_costo,'$cod','$cuenta[Concepto]')";

        if (count($values1) == 1000) {
            saveMovimientosMasivos($values1,$values2);
            $values1 = [];
            $values2 = [];
        }

        // cambiarEstadoFactura($cuenta['Nit_Cuenta'],$cuenta['Documento'],$cuenta['Id_Plan_Cuentas']); // Metodo que cambia el estado de la factura a "pagada"
    }

    if (count($values1) > 0) {
        saveMovimientosMasivos($values1,$values2);
    }
}

function saveMovimientosMasivos($values1, $values2) {
    $insert1 = "INSERT INTO Cuenta_Documento_Contable(Id_Documento_Contable,Id_Plan_Cuenta,Nit,Tipo_Nit,Id_Centro_Costo,Documento,Concepto,Base,Debito,Credito,Deb_Niif,Cred_Niif) VALUES " . implode(",",$values1);

    $insert2 = "INSERT INTO Movimiento_Contable(Id_Plan_Cuenta,Id_Modulo,Id_Registro_Modulo,Fecha_Movimiento,Debe,Debe_Niif,Haber,Haber_Niif,Nit,Tipo_Nit,Documento,Id_Centro_Costo,Numero_Comprobante,Detalles) VALUES " . implode(",",$values2);

    $oCon = new consulta();
    $oCon->setQuery($insert1);
    $oCon->createData();
    unset($oCon);
    
    $oCon = new consulta();
    $oCon->setQuery($insert2);
    $oCon->createData();
    unset($oCon);

    return;
}

?>