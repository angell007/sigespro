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
require('./funciones.php');
require('../contabilidad/funciones.php');


$datos = isset($_REQUEST['Datos']) ? $_REQUEST['Datos'] : false;
$cuentas_contables = isset($_REQUEST['Cuentas_Contables']) ? $_REQUEST['Cuentas_Contables'] : false;

$datos = json_decode($datos, true);
$cuentas_contables = json_decode($cuentas_contables, true);

/* var_dump($datos);
 */
if (!validarSuma($cuentas_contables)) {
    $resultado['mensaje'] = "El documento no está en balance, por tanto no se puede guardar";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Operación Fallida!";
} else {
    $mes = isset($datos['Fecha_Documento']) ? date('m', strtotime($datos['Fecha_Documento'])) : date('m');
    $anio = isset($datos['Fecha_Documento']) ? date('Y', strtotime($datos['Fecha_Documento'])) : date('Y');

    $contabilizar = new Contabilizar();
    if ($contabilizar->validarMesOrAnioCerrado("$anio-$mes-01")) {
         $oItem = $datos['Id_Documento_Contable']? new complex("Documento_Contable", "Id_Documento_Contable", $datos['Id_Documento_Contable']) :  new complex("Documento_Contable", "Id_Documento_Contable");

        $cod = $datos['Id_Documento_Contable'] ? $oItem->Codigo : generarConsecutivo('Egreso', $mes, $anio);


        $datos['Codigo'] = $cod;
        if ($datos['Id_Documento_Contable']) {
            session_start();
            $oItem->Funcionario_Edita =  $_SESSION["user"];
            $oItem->Fecha_Edita = date('Y-m-d H:i:s');
        }

        if (!isset($datos['Id_Centro_Costo']) || $datos['Id_Centro_Costo'] == '') {
            $datos['Id_Centro_Costo'] = 0;
        }

        foreach ($datos as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->Tipo = 'Egreso';


        $oItem->save();
        $id_documento_contable = $oItem->getId();
        unset($oItem);

        /* AQUI GENERA QR */
        $qr = generarqr('egreso', $id_documento_contable, '/IMAGENES/QR/');
        $oItem = new complex("Documento_Contable", "Id_Documento_Contable", $id_documento_contable);
        $oItem->Codigo_Qr = $qr;
        $oItem->save();
        unset($oItem);
        /* HASTA AQUI GENERA QR */


        $oItem = new complex('Cuenta_Documento_Contable', 'Id_Documento_Contable',  $id_documento_contable);
        $oItem->delete();
        unset($oItem);

        $query = "DELETE FROM Movimiento_Contable WHERE Numero_Comprobante = '$cod'";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->getData();
        unset($oCon);


        unset($cuentas_contables[count($cuentas_contables) - 1]);

        foreach ($cuentas_contables as $cuenta) {
            $oItem = new complex('Cuenta_Documento_Contable', 'Id_Cuenta_Documento_Contable');
            $oItem->Id_Documento_Contable = $id_documento_contable;
            $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'];

            if ($datos['Forma_Pago'] == 'Cheque' && $cuenta['Credito'] > 0) {
                $response_cheque = generarConsecutivoCheque($cuenta['Id_Plan_Cuentas'], $cuenta['Cheque']);
                if ($response_cheque['status'] == 2) {
                    $oItem->Cheque = $response_cheque['consecutivo'];
                }
            }
            $oItem->Nit = $cuenta['Nit_Cuenta'];
            $oItem->Tipo_Nit = $cuenta['Tipo_Nit'];
            $oItem->Id_Centro_Costo = isset($cuenta['Id_Centro_Costo']) && $cuenta['Id_Centro_Costo'] != '' ? $cuenta['Id_Centro_Costo'] : '0';
            $oItem->Documento = $cuenta['Documento'];
            $oItem->Concepto = $cuenta['Concepto'];
            $oItem->Base = number_format($cuenta['Base'], 2, ".", "");
            $oItem->Debito = number_format($cuenta['Debito'], 2, ".", "");
            $oItem->Credito = number_format($cuenta['Credito'], 2, ".", "");
            $oItem->Deb_Niif = number_format($cuenta['Deb_Niif'], 2, ".", "");
            $oItem->Cred_Niif = number_format($cuenta['Cred_Niif'], 2, ".", "");
            $oItem->save();
            unset($oItem);

            // cambiarEstadoFactura($cuenta);

            ## REGISTRAR MOVIMIENTO CONTABLE...
            $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'];
            $oItem->Id_Modulo = 7;
            $oItem->Id_Registro_Modulo = $id_documento_contable;
            $oItem->Fecha_Movimiento = $datos['Fecha_Documento'];
            $oItem->Debe = number_format($cuenta['Debito'], 2, ".", "");
            $oItem->Debe_Niif = number_format($cuenta['Deb_Niif'], 2, ".", "");
            $oItem->Haber = number_format($cuenta['Credito'], 2, ".", "");
            $oItem->Haber_Niif = number_format($cuenta['Cred_Niif'], 2, ".", "");
            $oItem->Nit = $cuenta['Nit_Cuenta'];
            $oItem->Tipo_Nit = $cuenta['Tipo_Nit'];
            $oItem->Id_Centro_Costo = isset($cuenta['Id_Centro_Costo']) && $cuenta['Id_Centro_Costo'] != '' ? $cuenta['Id_Centro_Costo'] : '0';
            $oItem->Documento = $cuenta['Documento'];
            $oItem->Numero_Comprobante = $cod;
            $oItem->Detalles = $cuenta['Concepto'];
            $oItem->save();
            unset($oItem);

            cambiarEstadoFactura($cuenta['Nit_Cuenta'], $cuenta['Documento'], $cuenta['Id_Plan_Cuentas']); // Metodo que cambia el estado de la factura a "pagada"
        }

        if (!empty($_FILES)) {
            guardarArchivosEgreso($id_documento_contable, $_FILES);
        }

        if (isset($datos['Id_Borrador']) && $datos['Id_Borrador'] != '') {
            eliminarBorradorContable($datos['Id_Borrador']);
        }

        if ($id_documento_contable && !empty($_FILES)) {
            guardarArchivosEgreso($id_documento_contable, $_FILES, $datos);
        }

        if ($id_documento_contable) {
            $resultado['mensaje'] = "Se ha registrado un comprobante de egreso satisfactoriamente";
            $resultado['tipo'] = "success";
            $resultado['titulo'] = "Operación Exitosa!";
            $resultado['id'] = $id_documento_contable;
        } else {
            $resultado['mensaje'] = "Ha ocurrido un error de conexión, comunicarse con el soporte técnico.";
            $resultado['tipo'] = "error";
        }
    } else {
        $resultado['mensaje'] = "El documento no se puede guardar porque existe un cierre para la fecha del documento";
        $resultado['titulo'] = "Error!";
        $resultado['tipo'] = "error";
    }
}
echo json_encode($resultado);

function guardarArchivosEgreso($id_documento_contable, $archivos, $datos = null)
{
    if (!$id_documento_contable) {
        return;
    }

    $files = array();
    if (isset($archivos['files'])) {
        $files = normalizarArchivos($archivos['files']);
    } elseif (isset($archivos['Archivo'])) {
        $files = normalizarArchivos($archivos['Archivo']);
    }

    if (count($files) === 0) {
        return;
    }

    global $MY_FILE;
    $ruta_relativa = "ARCHIVOS/COMPROBANTES/EGRESOS/" . $id_documento_contable . "/";
    $ruta_absoluta = $MY_FILE . $ruta_relativa;

    if (!is_dir($ruta_absoluta)) {
        mkdir($ruta_absoluta, 0775, true);
    }

    $tipo_documento = isset($datos['Tipo']) && $datos['Tipo'] !== '' ? $datos['Tipo'] : 'Egreso';

    foreach ($files as $file) {
        if (!isset($file['name']) || $file['name'] === '') {
            continue;
        }

        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            continue;
        }

        $nombre_original = $file['name'];
        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
        $base = pathinfo($nombre_original, PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_-]/', '_', $base);
        $nombre_archivo = $base . '_' . uniqid('egreso_', true);
        if ($extension) {
            $nombre_archivo .= '.' . $extension;
        }

        $destino = $ruta_absoluta . $nombre_archivo;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            continue;
        }

        $oItem = new complex("Archivo_Documento", "Id_Archivos_Documentos");
        $oItem->Tipo_Documento = $tipo_documento;
        $oItem->Ruta_AMZ = '';
        $oItem->Ruta = $ruta_relativa . $nombre_archivo;
        $oItem->Id_Tipo_Documento = $id_documento_contable;
        $oItem->save();
        unset($oItem);
    }
}

function normalizarArchivos($files)
{
    $normalizados = array();

    if (!is_array($files['name'])) {
        $normalizados[] = $files;
        return $normalizados;
    }

    $cantidad = count($files['name']);
    for ($i = 0; $i < $cantidad; $i++) {
        $normalizados[] = array(
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        );
    }

    return $normalizados;
}

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



/* function cambiarEstadoFactura($factura) {
    if (isset($factura['Valor_Factura']) && isset($factura['Valor_Abono'])) {
        $valor_factura = number_format($factura['Valor_Factura'],2,".","");
        $valor_abono = number_format($factura['Valor_Abono'],2,".","");
        $por_pagar = $valor_factura - $valor_abono;

        if (($por_pagar == $factura['Debito']) || ($por_pagar == $factura['Credito'])) { // Valida si lo que falta por pagar es igual a lo que viene en el debito o el credito
        
            $query = "UPDATE Factura_Acta_Recepcion SET Estado = 'Pagada' WHERE Factura = '$factura[Documento]' AND Id_Acta_Recepcion = $factura[Id_Factura]";

            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
            
            $query = "UPDATE Facturas_Proveedor_Mantis SET Estado = 'Pagada' WHERE Factura = '$factura[Documento]' AND Id_Facturas_Proveedor_Mantis = $factura[Id_Factura]";
            
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        }
    }
} */
