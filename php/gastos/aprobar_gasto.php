<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.contabilizar.php');
require_once('../comprobantes/funciones.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$gastos = isset($_REQUEST['gastos']) ? $_REQUEST['gastos'] : false;
$centro_costo = isset($_REQUEST['centro_costo']) ? $_REQUEST['centro_costo'] : false;
$anticipos = isset($_REQUEST['anticipos']) ? $_REQUEST['anticipos'] : false;
$cuentas_adicionales = isset($_REQUEST['cuentas_adicionales']) ? $_REQUEST['cuentas_adicionales'] : false;
$diferencia_total = isset($_REQUEST['diferencia_total']) ? $_REQUEST['diferencia_total'] : false;

$contabilizacion = new Contabilizar();
$response = [];

if ($datos && $gastos && $centro_costo) {
    $datos = json_decode($datos, true);
    $gastos = json_decode($gastos, true);
    $centro_costo = json_decode($centro_costo, true);
    $centro_costo = implode(",", $centro_costo);
    $ant = json_decode($datos['Anticipos'],true)[0];

    $datos['Anticipos'] = $anticipos;
   
    if ($datos['Observaciones'] != '') {
        $datos['Cuentas_Adicionales'] = $cuentas_adicionales;
    }
    $datos['Centro_Costo'] = $centro_costo;
    $datos['Fecha_Aprobacion'] = date('Y-m-d H:i:s');
    $datos['Codigo_Legalizacion'] = generarConsecutivo('Legalizacion_Gasto');
    $datos['Estado'] = 'Aprobado';

   
     $id_gasto = guardarCentroCostoGasto($datos);
   

    
    
    //$contabilizacion->CrearMovimientoContable('Legalizacion_Gastos', $datos_contabilizar);
    $totalGastos = 0;
    if ($id_gasto) {
        $totalGastos = guardarGastoItems($id_gasto, $gastos);
        guardarActividadGasto($id_gasto, $datos);

        if (isset($datos['Observacion_Aprobacion']) && $datos['Observacion_Aprobacion'] != '') {
            crearAlerta($datos);
        }

        $response['mensaje'] = "Se ha aprobado y legalizado satisfactoriamente el gasto.";
        $response['titulo'] = "Exito!";
        $response['tipo'] = "success";
    } else {
        $response['mensaje'] = "Ha ocurrido un error inesperado al intentar guardar.";
        $response['titulo'] = "Oops!";
        $response['tipo'] = "error";
    }



    $datos_contabilizar["Id_Registro"] = $id_gasto;
    $datos_contabilizar["Numero_Comprobante"] = $datos['Codigo_Legalizacion'];
    $datos_contabilizar["datos"] = $datos;
    $datos_contabilizar["gastos"] = $gastos;
    
    $datos_contabilizar["diferencia_total"] = $diferencia_total;
    
      $ant = json_decode($datos['Anticipos'],true)[0];

    $datos_contabilizar["anticipo"] = $ant['Factura'];
    
     $id_gasto = guardarCentroCostoGasto($datos);
     $adicionales =  json_decode($cuentas_adicionales, true);
    if ($datos['Observaciones'] != '' && $adicionales['Id_Plan_Cuenta']) {
        $datos_contabilizar['cuentas_adicionales'] = $adicionales;
        
        foreach($datos_contabilizar['cuentas_adicionales'] as $cuent){
           
            $totalGastos += $cuent['Debito'];
        }
        
        
    }
   
    $datos_contabilizar["total_gasto"] =  $totalGastos;
    $datos_contabilizar["nit_responsable"] =  $datos['Identificacion_Funcionario'];
    $datos_contabilizar["tipo_nit_responsable"] =  'Funcionario';
    $contabilizacion->CrearMovimientoContable('Legalizacion_Gastos', $datos_contabilizar);
    /*var_dump(['datos'=>$datos,'$gastos'=>$gastos,'$centro_costo'=>$centro_costo,
    '$anticipos'=>$anticipos,'$cuentas_adicionales'=>$cuentas_adicionales]);*/
    
} else {
    $response['mensaje'] = "Ha ocurrido un error inesperado al intentar guardar.";
    $response['titulo'] = "Oops!";
    $response['tipo'] = "error";
}

echo json_encode($response);

function guardarCentroCostoGasto($datos) {
    $oItem = new complex('Gasto_Punto','Id_Gasto_Punto',$datos['Id_Gasto_Punto']);
    foreach ($datos as $index => $value) {
        if($index != 'Id_Gasto_Punto')
            $oItem->$index = $value;
    }
    $oItem->save();
    $id_gasto = $datos['Id_Gasto_Punto'];
    unset($oItem);

    return $id_gasto;
}

function guardarGastoItems($id_gasto, $gastos) {
    eliminarItemsGasto($id_gasto);
    $totalGasto = 0;
    foreach ($gastos as $i => $gasto) {
        $totalGasto += $gasto['Total'];
        $oItem = new complex('Item_Gasto_Punto', 'Id_Item_Gasto_Punto');
        foreach ($gasto as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->Id_Gasto_Punto = $id_gasto;
        $oItem->save();
    }
    unset($oItem);

    return $totalGasto;
}

function guardarActividadGasto($id_gasto, $datos) {
    $oItem = new complex('Actividad_Gasto_Punto','Id_Actividad_Gasto_Punto');
    $oItem->Id_Gasto_Punto = $id_gasto;
    $oItem->Identificacion_Funcionario = $datos['Funcionario_Aprobacion'];
    $oItem->Detalles = "El gasto ha sido aprobado y legalizado con codigo: $datos[Codigo_Legalizacion]";
    $oItem->Estado = "Aprobacion";
    $oItem->save();

    return;
}

function eliminarItemsGasto($id_gasto) {
    $query = "DELETE FROM Item_Gasto_Punto WHERE Id_Gasto_Punto = $id_gasto";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}

function crearAlerta($datos) {
    $oItem = new complex('Alerta','Id_Alerta');
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
    $oItem->Tipo = 'Legalizacion_Gastos';
    $oItem->Detalles = $datos['Observacion_Aprobacion'];
    $oItem->save();
    unset($oItem);
    return;
}
?>