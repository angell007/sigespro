<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../../class/class.contabilizar.php');
    
$contabilizar = new Contabilizar();

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;

$datos = (array) json_decode($datos, true);

$oItem = new complex('Ajuste_Individual','Id_Ajuste_Individual',$id);
$data = $oItem->getData();
$fecha = date('Y-m-d',strtotime($data['Fecha']));
if ($contabilizar->validarMesOrAnioCerrado($fecha)) {
    
  $tipo = $oItem->Tipo;
  $codigo = $oItem->Codigo;
  $oItem->Estado = 'Anulada';
  $oItem->Observacion_Anulacion = 'NOTA DE ANULACION: ' . $datos['Observacion_Anulacion'];
  $oItem->Funcionario_Anula = $datos['Funcionario_Anula'];
  $oItem->Fecha_Anulacion = date('Y-m-d H:i:s');
  $oItem->save();
  unset($oItem);

  $oCon = new consulta();
  $oCon->setQuery("SELECT * FROM Producto_Ajuste_Individual WHERE Id_Ajuste_Individual = $id");
  $oCon->setTipo('Multiple');
  $productos = $oCon->getData();
  unset($oCon);

  foreach ($productos as $i => $prod) {
    $oItem = new complex('Inventario','Id_Inventario',$prod['Id_Inventario']);
    $cantidad_ajuste = $prod['Cantidad'];
    $cantidad_inventario = $oItem->Cantidad;

    if ($tipo == 'Entrada') {
      $cantidad_final = $cantidad_inventario - $cantidad_ajuste;
      $oItem->Cantidad = number_format($cantidad_final,0,"",""); // Revirtiendo cantidades.
    } else {
      $cantidad_final = $cantidad_inventario + $cantidad_ajuste;
      $oItem->Cantidad = number_format($cantidad_final,0,"",""); // Revirtiendo cantidades.
    }
    $oItem->save();
    unset($oItem);
  }

  $resultado['mensaje'] = "Ajuste Individual $codigo anulado correctamente";
  $resultado['tipo'] = "success";
  $resultado['titulo'] = "Operación Exitosa!";

  AnularMovimientoContable($id);
} else {
    $resultado['mensaje'] = "No es posible anular este ajuste debido a que el mes o el año del documento ha sido cerrado contablemente. Si tienes alguna duda por favor comunicarse al Dpto. Contabilidad.";
    $resultado['tipo'] = "info";
    $resultado['titulo'] = "No es posible!";
}


echo json_encode($resultado);

function AnularMovimientoContable($idRegistroModulo){
    global $contabilizar;

    $contabilizar->AnularMovimientoContable($idRegistroModulo, 8);
}

?>