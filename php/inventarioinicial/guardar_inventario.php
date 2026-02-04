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
require('../../class/class.guardar_archivos.php');

//Objeto de la clase que almacena los archivos    
$storer = new FileStorer();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$lotes = ( isset( $_REQUEST['lotes'] ) ? $_REQUEST['lotes'] : '' );


$lotes = (array) json_decode($lotes, true);
$datos = (array) json_decode($datos);
if (!empty($_FILES['Foto']['name'])){
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/PRODUCTOS/');
    $datos["Imagen"] = $nombre_archivo[0];
    
	// $posicion1 = strrpos($_FILES['Foto']['name'],'.')+1;
	// $extension1 =  substr($_FILES['Foto']['name'],$posicion1);
	// $extension1 =  strtolower($extension1);
	// $_filename1 = uniqid() . "." . $extension1;
	// $_file1 = $MY_FILE . "IMAGENES/PRODUCTOS/" . $_filename1;
	
	// $ancho="800";
	// $alto="800";	
	// $subido1 = move_uploaded_file($_FILES['Foto']['tmp_name'], $_file1);
	// 	if ($subido1){
	// 		list($width, $height, $type, $attr) = getimagesize($_file1);		
	// 		@chmod ( $_file1, 0777 );
	// 		$datos["Imagen"] = $_filename1;
	// 	} 
}
$oItem=new complex('Producto', 'Id_Producto', $datos['Id_Producto']);
$oItem->Cantidad_Presentacion=$datos['Cantidad_Presentacion'];
$oItem->Peso_Presentacion_Minima=$datos['Peso_Minimo'];
$oItem->Peso_Presentacion_Regular=$datos['Peso_Regular'];
$oItem->Peso_Presentacion_Maxima=$datos['Peso_Maximo'];
$oItem->Tolerancia=$datos['Tolerancia'];
$oItem->Id_Categoria=$datos['Categoria'];
$oItem->Imagen=$datos['Imagen'];
$oItem->save();
unset($oItem);
unset($lotes[count($lotes)-1]);

foreach($lotes as $lote){

    $oItem=new complex('Inventario_Inicial', 'Id_Inventario_Inicial');
    $oItem->Id_Producto=$datos['Id_Producto'];
$oItem->Codigo = substr(hexdec(uniqid()),2,12);
    $oItem->Codigo_Cum=$datos['Codigo_Cum'];
    $oItem->Lote=$lote['Lote'];
    $oItem->Fecha_Vencimiento=$lote['Fecha_Vencimiento'];
    $oItem->Cantidad=$lote['Cantidad'];
    $oItem->Id_Bodega=$datos['Id_Bodega'];    
    $oItem->save();
unset($oItem);

}
$resultado['mensaje'] = "¡Producto Guardado Exitosamente!";
$resultado['tipo'] = "success";





echo json_encode($resultado);

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");
?>