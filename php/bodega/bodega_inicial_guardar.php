<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');



$bodegainicio = ( isset( $_REQUEST['bodegainicio'] ) ? $_REQUEST['bodegainicio'] : '' );
$fotos = ( isset( $_REQUEST['fotos'] ) ? $_REQUEST['fotos'] : '' );

$bodegainicio = (array) json_decode($bodegainicio , true);

unset($bodegainicio[count($bodegainicio)-1]);

$i=-1;
foreach($bodegainicio as $bodega){ $i++;
    if (!empty($_FILES['Foto'.$i]['name'])){
    	$posicion1 = strrpos($_FILES['Foto'.$i]['name'],'.')+1;
    	$extension1 =  substr($_FILES['Foto'.$i]['name'],$posicion1);
    	$extension1 =  strtolower($extension1);
    	$_filename1 = uniqid() . "." . $extension1;
    	$_file1 = $MY_FILE . "IMAGENES/BODEGAINICIAL/" . $_filename1;
    	
    	$subido1 = move_uploaded_file($_FILES['Foto'.$i]['tmp_name'], $_file1);
    		if ($subido1){		
    			@chmod ( $_file1, 0777 );
    			$bodega["Foto"] = $_filename1;
    		} 
    }
    $oItem = new complex('Bodega_Inicial',"Id_Bodega_Inicial");
    foreach($bodega as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
}


    $resultado['mensaje'] = "Se ha guardado correctamente  ";
    $resultado['tipo'] = "success";
/*}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}*/

echo json_encode($resultado);
?>	