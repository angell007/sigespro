<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
    
$contabilizar = new Contabilizar();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );


$datos = (array) json_decode($datos, true);
    
$oItem = new complex('Nota_Credito',"Id_Nota_Credito", $datos['Id_Nota_Credito']);
$data = $oItem->getData();
$fecha = date('Y-m-d',strtotime($data['Fecha']));
if ($contabilizar->validarMesOrAnioCerrado($fecha)) {
    
    $observaciones=$oItem->Observacion;
    $oItem->Estado="Anulada";
    $oItem->Observacion=$observaciones.' NOTA-ANULACION: '.$datos['Observaciones'];
    $oItem->Fecha_Anulacion=date("Y-m-d H:i:s");
    $oItem->Funcionario_Anula=$datos['Funcionario_Anula'];
    $oItem->save();
    unset($oItem);

    $query="SELECT Cantidad, Id_Inventario  FROM Producto_Nota_Credito WHERE Id_Nota_Credito=".$datos['Id_Nota_Credito'];
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    foreach ($productos as  $value) {
        $query="SELECT Cantidad, Id_Inventario  FROM Inventario WHERE Id_Inventario=".$value['Id_Inventario'];
        $oCon= new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);

        $cantidad_final=$inventario['Cantidad']-$value['Cantidad'];
        if($cantidad_final<0){
            $cantidad_final=0;
        }
        if($inventario){
            $oItem = new complex('Inventario',"Id_Inventario", $inventario['Id_Inventario']);
            $oItem->Cantidad=$cantidad_final;
            $oItem->save();
            unset($oItem);
        }
    
    }

    AnularMovimientoContable($datos['Id_Nota_Credito']);

    $resultado['mensaje'] = "Se anulado la Nota credito correctamente";
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "No es posible anular esta nota debido a que el mes o el año del documento ha sido cerrado contablemente. Si tienes alguna duda por favor comunicarse al Dpto. Contabilidad.";
    $resultado['tipo'] = "info";
}



echo json_encode($resultado);

function AnularMovimientoContable($idRegistroModulo){
    global $contabilizar;

    $contabilizar->AnularMovimientoContable($idRegistroModulo, 4);
}

?>		