<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

    require_once('./helper_tipo/funciones_tipo.php');
    include_once('../../../class/class.consulta.php');
 
    $NombreTip = ( isset( $_REQUEST['NombreTip'] ) ? $_REQUEST['NombreTip'] : '' );
    $ColorTipo = ( isset( $_REQUEST['ColorTipo'] ) ? $_REQUEST['ColorTipo'] : '' );
    
    $color= $ColorTipo ? $ColorTipo : "#090707";

    $Id_Tipo = ( isset( $_REQUEST['Id_Tipo'] ) ? $_REQUEST['Id_Tipo'] : '' );
    $Estado = ( isset( $_REQUEST['Estado'] ) ? $_REQUEST['Estado'] : 'Activado');
    
    if($NombreTip != '' ){
      
        guardarProductoNoPos($NombreTip, $color, $Estado);
    }else{
        $estado = $Estado == 'Desactivado' ? 'Activado' : 'Desactivado';
        cambiarEstado($Id_Tipo, $estado);
    }
    echo json_encode([$message => 'ok'] );

?>
