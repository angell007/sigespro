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
include_once('../../../class/class.contabilizar.php');
//require_once('../../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('../../comprobantes/funciones.php');
require('../funciones.php');

$datos = isset($_REQUEST['Datos']) ? $_REQUEST['Datos'] : false;
$cuentas_contables = isset($_REQUEST['Cuentas_Contables']) ? $_REQUEST['Cuentas_Contables'] : false;

$datos = json_decode($datos, true);
$cuentas_contables = json_decode($cuentas_contables, true);

/* var_dump($datos);NOS202012293
var_dump($cuentas_contables);
exit; */

if (!validarSuma($cuentas_contables)) {
    $resultado['mensaje'] = "El documento no est�� en balance, por tanto no se puede guardar";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Operaci��n Fallida!";
} else {
    $mes = isset($datos['Fecha_Documento']) ? date('m', strtotime($datos['Fecha_Documento'])) : date('m');
    $anio = isset($datos['Fecha_Documento']) ? date('Y', strtotime($datos['Fecha_Documento'])) : date('Y');

    $contabilizar = new Contabilizar();
    if ($contabilizar->validarMesOrAnioCerrado("$anio-$mes-01")) {
        
        
        

        $oItem = new complex("Documento_Contable", "Id_Documento_Contable" , $datos['Id_Documento_Contable']);
        
        $cod = $datos['Id_Documento_Contable'] ? $oItem->Codigo: generarConsecutivo('Nota', $mes, $anio);
        
        $datos['Codigo'] = $cod;
        
        if($datos['Id_Documento_Contable']){
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
        
        $oItem->save();
        $id_nota_contable = $oItem->getId();
        unset($oItem);
        
        /* AQUI GENERA QR */
        /* $qr = generarqr('notacontable',$id_nota_contable,'/IMAGENES/QR/');
        $oItem = new complex("Documento_Contable","Id_Documento_Contable",$id_nota_contable);
        $oItem->Codigo_Qr=$qr;
        $oItem->save();
        unset($oItem); */
        /* HASTA AQUI GENERA QR */
        
        
        $oItem = new complex('Cuenta_Documento_Contable', 'Id_Documento_Contable',  $id_nota_contable);
        $oItem->delete();
        unset($oItem);
    
        $query = "DELETE FROM Movimiento_Contable WHERE Numero_Comprobante = '$cod'"; 
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->getData();
        unset($oCon);
    
        
        unset($cuentas_contables[count($cuentas_contables)-1]);
        $x = 0;
        $y=0;
        $docs = '';
        foreach ($cuentas_contables as $cuenta) {
            
        
            $oItem = new complex('Cuenta_Documento_Contable', 'Id_Cuenta_Documento_Contable');
            $oItem->Id_Documento_Contable = $id_nota_contable;
            $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuentas'] != '' ? $cuenta['Id_Plan_Cuentas'] : '0';
            $oItem->Nit = $cuenta['Nit_Cuenta'];
            $oItem->Tipo_Nit = $cuenta['Tipo_Nit'];
            $oItem->Id_Centro_Costo = isset($cuenta['Id_Centro_Costo']) && $cuenta['Id_Centro_Costo'] != '' ? $cuenta['Id_Centro_Costo'] : '0';
            $oItem->Documento = $cuenta['Documento'];
            $oItem->Concepto = $cuenta['Concepto'];
            $base = $cuenta['Base'] != "" ? $cuenta['Base'] : 0;
            $oItem->Base = number_format($base,2,".","");
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
        
           
           // if (count($cuentas_contables) < 50) { /** SE QUITA RESTRICCION DE LOS 50 PARA QUE MARQUE TODAS LAS FACTURAS  */
               // cambiarEstadoFactura($cuenta['Nit_Cuenta'],$cuenta['Documento'],$cuenta['Id_Plan_Cuentas']); // Metodo que cambia el estado de la factura a "pagada"
           // }
        
            
           if ($cuenta['Id_Plan_Cuentas'] == 85 || $cuenta['Id_Plan_Cuentas'] == 272) {
        
                $docs .='"'.$cuenta['Documento'].'",'; 
            }
        
           if ($x==200 && getenv("DEBUG_NOTA_CONTABLE") === "1") {
            
                $logFile = fopen('prueba.txt','w') or die("Error creando archivo");;
                fwrite($logFile,$docs);
                $y++;
                sleep(5);
                $x=0;
            }
        
            $x++;
        }
        //cambiarEstadoFactura("",$docs,1); // Metodo que cambia el estado de la factura a "pagada"
        if (isset($datos['Id_Borrador']) && $datos['Id_Borrador'] != '') {
            eliminarBorradorContable($datos['Id_Borrador']);
        }

        if ($id_nota_contable && !empty($_FILES)) {
            guardarArchivosNotaContable($id_nota_contable, $_FILES, $datos);
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
    } else {
        $resultado['mensaje'] = "El documento no se puede guardar porque existe un cierre para la fecha del documento";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error!";
    }
}

$flags = defined("JSON_INVALID_UTF8_SUBSTITUTE") ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
echo json_encode($resultado, $flags);

function guardarArchivosNotaContable($id_documento_contable, $archivos, $datos)
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
    $ruta_relativa = "ARCHIVOS/COMPROBANTES/NOTASCONTABLES/" . $id_documento_contable . "/";
    $ruta_absoluta = $MY_FILE . $ruta_relativa;

    if (!is_dir($ruta_absoluta)) {
        mkdir($ruta_absoluta, 0775, true);
    }

    $tipo_documento = isset($datos['Tipo']) && $datos['Tipo'] !== '' ? $datos['Tipo'] : 'Nota Contable';

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
        $nombre_archivo = $base . '_' . uniqid('nota_', true);
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

?>