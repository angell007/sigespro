<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );

$datos = (array) json_decode($datos, true);
$cod = $configuracion->getConsecutivo('Diario_Cajas_Dispensacion','Cierre_Caja');
$oItem = new complex('Diario_Cajas_Dispensacion', 'Id_Diario_Cajas_Dispensacion');
$oItem->Identificacion_Funcionario = $funcionario;
$oItem->Id_Punto_Dispensacion = $punto;
$cuota_ent = number_format(str_replace('.','',$datos['Cuota']),2,".","");
$oItem->Cuota_Ingresada = $cuota_ent;
$cuota_real = number_format($datos['Cuota_Real'],2,".","");
$oItem->Cuota_Real = $cuota_real;
$gasto = number_format($datos['totalGasto'],2,".","");
$oItem->Gastos = $gasto;
$balance = $cuota_ent - $gasto;
$oItem->Balance = number_format($balance,2,".","");
$oItem->Observaciones = $datos['Observaciones'];
$oItem->Fecha_Inicio = $datos['FechaInicio'];
$oItem->Fecha_Fin = $datos['FechaFin'];
$oItem->Codigo = $cod;
$oItem->save();
$id_diario = $oItem->getId();
unset($oItem);

$qr = generarqr('cierecaja',$id_diario,'/IMAGENES/QR/');
$oItem = new complex("Diario_Cajas_Dispensacion","Id_Diario_Cajas_Dispensacion",$id_diario);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);

if(!empty($datos['Id_Dispensaciones'])){
    $dispensaciones=explode(",",$datos['Id_Dispensaciones']);    

    foreach ($dispensaciones as $dis) {
        
        $oItem = new complex('Dispensacion', 'Id_Dispensacion', $dis);
        $oItem->Id_Diario_Cajas_Dispensacion = $id_diario;
        if( $oItem->Fecha_Asignado_Auditor=='0000-00-00 00:00:00'){
            $oItem->Fecha_Asignado_Auditor=NULL;
        }
        if( $oItem->Fecha_Facturado=='0000-00-00 00:00:00'){
            $oItem->Fecha_Facturado=NULL;
        }
        if( $oItem->Fecha_Asignado_Facturador=='0000-00-00 00:00:00'){
            $oItem->Fecha_Asignado_Facturador=NULL;
        }
        if( $oItem->Fecha_Auditado=='0000-00-00 00:00:00'){
            $oItem->Fecha_Auditado=NULL;
        }
        $oItem->Id_Diario_Cajas_Dispensacion = $id_diario;
        $oItem->save();
        unset($oItem);
    }
}

if ($id_diario) {
    $resultado['titulo'] = "Operación Exitosa";
    $resultado['mensaje'] = "Se ha cerrado la caja correctamente.";
    $resultado['tipo'] = "success";
} else {
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Ha ocurrido un error inesperado, por favor vuelva a intentarlo.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>