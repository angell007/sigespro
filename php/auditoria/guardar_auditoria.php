<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
// require('../../class/class.guardar_archivos.php');

// //Objeto de la clase que almacena los archivos    
// $storer = new FileStorer();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
//$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );


$datos = (array) json_decode($datos , true);
//$productos=(array) json_decode($productos , true);
$soportes=(array) json_decode($soportes , true);


$datos["Funcionario_Auditoria"]=$id_funcionario;
$datos["Fecha_Auditoria"]=date("Y-m-d H:i:s");
if(!isset($datos["Id_Dispensacion_Mipres"])||$datos["Id_Dispensacion_Mipres"]==''){
    unset($datos["Id_Dispensacion_Mipres"]);
}
$oItem = new complex("Auditoria","Id_Auditoria",$datos["Id_Auditoria"]);

foreach($datos as $index=>$value) {
    
    $oItem->$index=$value;
}
$oItem->Estado="Auditado";
$oItem->Estado_Turno="Espera";
$oItem->save();
unset($oItem);

$i=-1;
foreach($soportes as $soporte){ $i++;

$id_auditoria=$datos["Id_Auditoria"];

    if (!empty($_FILES['Archivo'.$i]['name'])){
        //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
        // $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
        // $soporte["Archivo"] = $nombre_archivo[0];

    	$posicion1 = strrpos($_FILES['Archivo'.$i]['name'],'.')+1;
    	$extension1 =  substr($_FILES['Archivo'.$i]['name'],$posicion1);
    	$extension1 =  strtolower($extension1);
    	$_filename1 = uniqid() . "." . $extension1;
    	$_file1 = $MY_FILE . "IMAGENES/AUDITORIAS/".$id_auditoria."/" . $_filename1;
    	
    	$subido1 = move_uploaded_file($_FILES['Archivo'.$i]['tmp_name'], $_file1);
    		if ($subido1){		
    			@chmod ( $_file1, 0777 );
    			$soporte["Archivo"] = $_filename1;
    		} 
    }

    $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria",$soporte["Id_Soporte_Auditoria"]);
    foreach($soporte as $index=>$value){
        $oItem->$index=$value;
    }
   $oItem->save();
    unset($oItem);
}


/*unset($productos[count($productos)-1]);

foreach($productos as $producto){$i++;
    $oItem = new complex('Producto_Auditoria',"Id_Producto_Auditoria");
    $oItem->Id_Producto = $producto["Id_Producto"];
    $oItem->Id_Inventario = $producto["Id_Inventario"];
    $oItem->Cantidad_Formulada = $producto["Cantidad_Formulada"];
     $oItem->Cum = $producto["Cum"];
     $oItem->Id_Auditoria=$datos["Id_Auditoria"];
    $oItem->save();
    unset($oItem);
}*/

$resultado['mensaje'] = "¡Guardado Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>