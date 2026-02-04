<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$remision = ( isset( $_REQUEST['id_remision'] ) ? $_REQUEST['id_remision'] : '' );

$contabilizar = new Contabilizar();
$datos = (array) json_decode($datos, true);

$productos = (array) json_decode($productos , true);
$remision = (array) json_decode($remision , true);

// $datos_movimiento_contable = array();

// $datos_movimiento_contable['Id_Registro'] = "2";
// $datos_movimiento_contable['Nit'] = $datos['Cliente']['Id_Cliente'];
// $datos_movimiento_contable['Productos'] = $productos;

// $contabilizar->CrearMovimientoContable('Factura Venta', $datos_movimiento_contable);

// var_dump($productos);
// var_dump($datos);
// var_dump($remision);
// echo "llego";
// exit;

// $configuracion = new Configuracion();

// $cod = $configuracion->Consecutivo('Factura_Venta');
//$cod='';  
//$datos['Codigo']=$cod;
    
$oItem = new complex($mod,"Id_".$mod);

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
//$oItem->save();
//$id_factura = $oItem->getId();
$resultado = array();
unset($oItem);

/* AQUI GENERA QR */
// $qr = generarqr('facturaventa',$id_factura,'/IMAGENES/QR/');
// $oItem = new complex("Factura_Venta","Id_Factura_Venta",$id_factura);
// $oItem->Codigo_Qr=$qr;
//$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */

// unset($productos[count($productos)-1]);
foreach($productos as $producto){
    $oItem = new complex('Producto_'.$mod,"Id_Producto_".$mod);
    $producto["Id_".$mod]=$id_factura;
    foreach($producto as $index=>$value) {
        $oItem->$index=$value;
    }
    //$oItem->save();
    unset($oItem);
}

if(isset($remision)&&count($remision)>0){ 
	$in = InCondition($remision);
	//$query_update = "UPDATE Remision SET Id_Factura = ".$id_factura.", Estado = 'Facturada' WHERE Id_Remision IN (".$in.")";
	$query_update = "UPDATE Remision SET Id_Factura = 1, Estado = 'Facturada' WHERE Codigo IN (".$in.")";
	var_dump($query_update);
	$oCon = new consulta();
	$oCon->setQuery($query_update);
	$oCon->createData();
	unset($oCon);

    foreach($remision as $remisiones){
        // $oItem = new complex('Remision',"Id_Remision",$remisiones['id']);
        // $codigo=$oItem->Codigo;
        // $oItem->Id_Factura = number_format($id_factura,0,"","");
        // $oItem->Estado = "Facturada";
        // var_dump($remisiones);
        // $oItem->save();
        // unset($oItem);

        $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
        $oItem->Id_Remision=$remisiones['id'];
        $oItem->Identificacion_Funcionario=$datos["Id_Funcionario"];
        $oItem->Detalles="Se facturo la remision con codigo ".$remisiones['Codigo'];
        $oItem->Estado="Facturada";
        $oItem->Fecha=date("Y-m-d H:i:s");
        $oItem->save();
        var_dump($oItem);
        unset($oItem);
    }
}

if($id_factura != ""){
    $resultado['mensaje'] = "Se ha guardado Correctamente la Factura de Venta con Codigo: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "Ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

function InCondition($remisiones){
	$in = '';
	foreach ($remisiones as $r) {
		$in .= '"'.$r['Codigo'].'",';
	}

	return trim($in, ",");
}

?>		