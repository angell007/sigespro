<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$contabilizar = new Contabilizar();
$configuracion = new Configuracion();
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$descripcion = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$descripciones = (array) json_decode($descripcion , true);

/*$oItem = new complex('Resolucion','Id_Departamento',$datos['Id_Departamento']);
$row = $oItem->getData();*/

// var_dump($descripciones);
// var_dump($datos);
// exit;

$datos_movimiento_contable = array();

$datos_movimiento_contable['Id_Registro'] = "154";
$datos_movimiento_contable['Id_Departamento'] = $datos['Cliente']->Id_Departamento;
$datos_movimiento_contable['Cuota_Moderadora'] = $datos['Cuota'];
$datos_movimiento_contable['Subtotal'] = GetTotalSubtotal($descripciones);
//$datos_movimiento_contable['Subtotal'] = $datos['SubtotalFactura0'];
$datos_movimiento_contable['Nit'] = $datos['Id_Cliente'];

$contabilizar->CrearMovimientoContable('Factura Capita', $datos_movimiento_contable);

var_dump($datos);
echo "llego";
exit;


function GetTotalSubtotal($datos){
    $subtotal = 0;

    foreach ($datos as $value) {
        
        $subtotal += floatval($value['Subtotal']);
    }

    return $subtotal;
}
// $query='SELECT * FROM Resolucion WHERE Id_Departamento='.$datos['Id_Departamento'].' AND Modulo="Capita"';

// $oCon= new consulta();
// $oCon->setQuery($query);
// $row = $oCon->getData();
// unset($oCon); 

// if ($row) {

// $oItem = new complex('Resolucion','Id_Resolucion',$row['Id_Resolucion']);
// $oItem->Consecutivo += 1;
// $oItem->save();
// unset($oItem);

// $cod = $row['Codigo'].$row['Consecutivo'];

// $datos['Codigo']=$cod;

// $oItem = new complex($mod,"Id_".$mod);
// foreach($datos as $index=>$value) {
//     $oItem->$index=$value;
// }
// $oItem->save();
// $id_factura_capita = $oItem->getId();
// unset($oItem);

// /* AQUI GENERA QR */
// $qr = generarqr('facturacapita',$id_factura_capita,'IMAGENES/QR/');
// $oItem = new complex("Factura_Capita","Id_Factura_Capita",$id_factura_capita);
// $oItem->Codigo_Qr=$qr;
// $oItem->save();
// unset($oItem);
// /* HASTA AQUI GENERA QR */

// unset($descripciones[count($descripciones)-1]);

// foreach($descripciones as $descripcion){$i++;
//     $oItem = new complex('Descripcion_'.$mod,"Id_Descripcion_".$mod);
//     $oItem->Id_Factura_Capita=$id_factura_capita;
//     $oItem->Descripcion = $descripcion["Descripcion"];
//     $cantidad = number_format($descripcion["Cantidad"],0,"","");
//     $oItem->Cantidad=$cantidad;
//     $precio = number_format($descripcion["Precio"],2,".","");
//     $oItem->Precio=$precio;
//     $descuento = number_format($descripcion["Descuento"],0,"","");
//     $oItem->Descuento=$descuento;
//     $iva = number_format($descripcion["Iva"],2,".","");
//     $oItem->Impuesto=$iva;
//     $total = number_format($descripcion["Subtotal"],2,".","");
//     $oItem->Total=$total;
//     $oItem->save();
//     unset($oItem);
// }


// $query = "SELECT D.* FROM Dispensacion D INNER JOIN Paciente P ON D.Numero_Documento=P.Id_Paciente WHERE D.Tipo='Capita' AND D.Pendientes=0 AND D.Estado_Facturacion = 'Sin Facturar' AND D.Estado_Dispensacion != 'Anulada' AND (D.Fecha_Actual LIKE '$datos[Mes]-%' OR D.Fecha_Actual < '$datos[Mes]-01 00:00:00') AND P.Nit=$datos[Id_Cliente] AND P.Id_Departamento=$datos[Id_Departamento] AND P.Id_Regimen = $datos[Id_Regimen]";

// $con = new consulta();
// $con->setQuery($query);
// $con->setTipo('Multiple');
// $dispensaciones = $con->getData();
// unset($con);

// foreach($dispensaciones as $dispensacion){
//     $oItem = new complex('Dispensacion',"Id_Dispensacion",$dispensacion['Id_Dispensacion']);
//     $oItem->Estado_Facturacion='Facturada';
//     $oItem->Id_Factura= $id_factura_capita;
//     $oItem->Fecha_Facturado= date('Y-m-d H:i:s');
//     $oItem->Facturador_Asignado = $datos['Identificacion_Funcionario'];
//     $oItem->save();
//     unset($oItem);
// }

// if($id_factura_capita != ""){
//     $resultado['mensaje'] = "Se ha guardado correctamente la Factura Capita";
//     $resultado['tipo'] = "success";
// }else{
//     $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
//     $resultado['tipo'] = "error";
// }
// } else {
//     $resultado['mensaje'] = "Lo sentimos, este departamento no esta asociado a ninguna Resolución tipo Capita";
//     $resultado['tipo'] = "error";
// }

// $resultado['id'] = $id_factura_capita;

// echo json_encode($resultado);

?>	