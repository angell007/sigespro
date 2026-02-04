<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$modulos = ( isset( $_REQUEST['modulos'] ) ? $_REQUEST['modulos'] : '' );


$datos = (array) json_decode($datos);
$modulos=(array) json_decode($modulos,true);

if(isset($datos["id"])&&$datos["id"] != ""){
	$oItem = new complex($mod,"Id_".$mod,$datos["id"]);
}else{
	$oItem = new complex($mod,"Id_".$mod);
}

foreach($datos as $index=>$value) {
     $oItem->$index=$value;
}

$oItem->save();
$id_perfil= $oItem->getId();
unset($oItem);

foreach($modulos as $modulo){
    if(isset($modulo["Id_Perfil_Permiso"])&&$modulo["Id_Perfil_Permiso"] != ""){
	$oItem = new complex("Perfil_Permiso","Id_Perfil_Permiso",$modulo["Id_Perfil_Permiso"]);
    }else{
    	 $oItem = new complex("Perfil_Permiso","Id_Perfil_Permiso");
    }

   
    $oItem->Id_Perfil=$id_perfil;
    $oItem->Titulo_Modulo=$modulo["Titulo_Modulo"];
    $oItem->Modulo = $modulo["Modulo"];
    if($modulo["Ver"] != ""){
         $oItem->Ver = $modulo["Ver"]; 
    }else{
        $oItem->Ver = "0";
    }
    if($modulo["Crear"] != ""){
     $oItem->Crear = $modulo["Crear"];   
    }else{
        $oItem->Crear = "0";
    }
     if($modulo["Editar"] != ""){
     $oItem->Editar = $modulo["Editar"];   
    }else{
        $oItem->Editar = "0";
    }
     if($modulo["Eliminar"] != ""){
     $oItem->Eliminar = $modulo["Eliminar"];   
    }else{
        $oItem->Eliminar = "0";
    }
    $oItem->save();
    unset($oItem);
}

$oLista = new lista($mod);
$lista= $oLista->getlist();
unset($oLista);

if(isset($datos["id"])&&$datos["id"] != ""){
    $resultado['mensaje'] = "Se ha Actualizado existosamente el Perfil";
    $resultado['tipo'] = "success";
}elseif($lista!=='') {
      $resultado['mensaje'] = "Se ha Creado existosamente el Perfil";
    $resultado['tipo'] = "success";
}
else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
?>