<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '' );

$oItem = new complex($mod,"Id_".$mod,$id);
$oItem->getData();
if($oItem->Estado_Cotizacion_Venta!= 'Anulada' && $oItem->Estado_Cotizacion_Venta != 'Aprobada'){
  $oItem->Estado_Cotizacion_Venta = $estado;
  $oItem->save();
  unset($oItem);
  
  $query1 = 'SELECT 
            CV.Fecha_Documento as Fecha , CV.Codigo as Codigo, CV.Id_Cotizacion_Venta as IdCV,Estado_Cotizacion_Venta as Estado,
            C.Nombre as NombreCliente
          FROM Cotizacion_Venta CV, Cliente C
          WHERE CV.Id_Cliente = C.Id_Cliente
          ORDER BY CV.Fecha_Documento DESC '  ;
          
          $oCon= new consulta();
          $oCon->setQuery($query1);
  $resultado = $oCon->getData();
  $oCon->setTipo('Multiple');
  unset($oCon);

  echo json_encode($resultado);
} else{
  $resultado['tipo']="error";
  $resultado['titulo']="Error";
  $resultado['mensaje']="No se puede cambiar el estado";
}
?>