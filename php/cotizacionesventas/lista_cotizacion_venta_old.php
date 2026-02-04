<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fechaInicial = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fechaFinal = ( isset( $_REQUEST['final'] ) ? $_REQUEST['final'] : '' );

if((isset($fechaInicial) && $fechaInicial != "") && (isset($fechaFinal) && $fechaFinal != "") ){
    
$query = 'SELECT 
            CV.Fecha_Documento as Fecha , CV.Codigo as Codigo, CV.Id_Cotizacion_Venta as IdCV,Estado_Cotizacion_Venta as Estado, CV.Observacion_Cotizacion_Venta as Observacion,
            C.Nombre as NombreCliente
          FROM Cotizacion_Venta CV, Cliente C
          WHERE CV.Id_Cliente = C.Id_Cliente
          AND CV.Fecha_Documento BETWEEN '.$fechaInicial.' AND '.$fechaFinal.'
          ORDER BY CV.Fecha_Documento DESC ';
}else{
    
$query = 'SELECT 
            CV.Fecha_Documento as Fecha , CV.Codigo as Codigo, CV.Id_Cotizacion_Venta as IdCV,Estado_Cotizacion_Venta as Estado, CV.Observacion_Cotizacion_Venta as Observacion,
            C.Nombre as NombreCliente
          FROM Cotizacion_Venta CV, Cliente C
          WHERE CV.Id_Cliente = C.Id_Cliente
          ORDER BY CV.Fecha_Documento DESC ';
}


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>