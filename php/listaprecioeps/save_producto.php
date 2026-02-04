<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

    $modelo = (array) json_decode($modelo, true);
    
    if($modelo['Id_Producto_'.$modelo['Tipo']]){
        $oItem = new complex("Producto_".$modelo['Tipo'],"Id_Producto_".$modelo['Tipo'],$modelo['Id_Producto_'.$modelo['Tipo']] );
        unset($modelo['Id_Producto_'.$modelo['Tipo']]);
    }else{
        $oItem = new complex("Producto_".$modelo['Tipo'],"Id_Producto_".$modelo['Tipo'] );

    }
    foreach($modelo as $index=>$value) {
        if($value!=''){
             if($index=='Precio'){
                  $oItem->$index=number_format($value,2,".","");
             }else{
                  $oItem->$index=$value;
             }
            
        }
       
    }

    $oItem->save();
    unset($oItem);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado el producto exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	


?>