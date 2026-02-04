<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');


$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
// $sedes = ( isset( $_REQUEST['sedes'] ) ? $_REQUEST['sedes'] : '' );

$datos = (array) json_decode($datos);
// $sedes = (array) json_decode($sedes,true);

/* $datos['Id_Cliente'] = (INT) $datos['Id_Cliente'];

var_dump($datos);
var_dump($_FILES);
exit; */

//Objeto de la clase que almacena los archivos    
$storer = new FileStorer();

$datos['Ciudad'] = $datos['Id_Municipio'];
$datos['Id_Plan_Cuenta_Reteica'] = $datos['Id_Plan_Cuenta_Reteica'] == '' ? '0' : $datos['Id_Plan_Cuenta_Reteica'];
$datos['Id_Plan_Cuenta_Retefuente'] = $datos['Id_Plan_Cuenta_Retefuente'] == '' ? '0' : $datos['Id_Plan_Cuenta_Retefuente'];
$datos['Id_Plan_Cuenta_Reteiva'] = $datos['Id_Plan_Cuenta_Reteiva'] == '' ? '0' : $datos['Id_Plan_Cuenta_Reteiva'];

if(isset($datos["Id_Cliente"])&&$datos["Id_Cliente"] != ""){
   
    $oItem = new complex($mod,"Id_".$mod,$datos["Id_Cliente"]);
    $id_cliente = $oItem->Id_Cliente;
	
}else{
	$oItem = new complex($mod,"Id_".$mod);
}

if($datos['cupo']==0){
    
    $datos['cupo']='0';
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
    // print_r([$index=>$value]);
}

$oItem->save();
$id_cliente = $oItem->getData()['Id_Cliente'];

if (!isset($datos["id"])) {
	

	$oItem2 = new complex('Cliente','Id_Cliente', $id_cliente);

    if (!empty($_FILES["Rut"]['name'])){

        //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
        $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/CONTABILIDAD/RUTS/');
        $oItem2->Rut = $nombre_archivo[0];
    
        // $posicion1 = strrpos($_FILES["Rut"]['name'],'.')+1;
        // $extension1 =  substr($_FILES["Rut"]['name'],$posicion1);
        // $extension1 =  strtolower($extension1);
        // $_filename1 = uniqid() . "." . $extension1;
        // $_file1 = $MY_FILE . "ARCHIVOS/CONTABILIDAD/RUTS/" . $_filename1;
        
        // $subido1 = move_uploaded_file($_FILES["Rut"]['tmp_name'], $_file1);
        // if ($subido1){
        //     @chmod ( $_file1, 0777 );
        //     $oItem2->Rut = $_filename1;
        // } 
    }
    $oItem2->save();
    unset($oItem2);
}


unset($oItem);

/* unset($sedes[count($sedes)-1]);

foreach($sedes as $sede){
    if(isset($sede["Id_Cliente_Sede"])&&$sede["Id_Cliente_Sede"] != ""){
        $oItem = new complex('Cliente_Sede',"Id_Cliente_Sede",$sede["Id_Cliente_Sede"]);
    }else{
        $oItem = new complex('Cliente_Sede',"Id_Cliente_Sede");
    }
    $sede["Id_Cliente"]=$id_cliente;
    foreach($sede as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
} */

if($id_cliente){
    $resultado['mensaje'] = "Se ha guardado correctamente el Cliente";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "Error en el proceso de registro del nuevo Cliente.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
?>