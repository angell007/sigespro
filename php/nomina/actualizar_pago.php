<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
date_default_timezone_set('America/Bogota');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

//para cambiar la config de la nomina se debe verificar que no existan pagos en ese periodo.
if($datos == 'Mensual'){
    $query='SELECT * FROM Nomina WHERE Nomina LIKE "%'.date('Y-m').'%" ' ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $config = $oCon->getData();
    unset($oCon);
   
    if(empty($config)){
        EditarConfiguracion($id, $datos);
    }else{
        $resultado["Mensaje"]="No es posible cambiar la configuración para este periodo";      
        $resultado["Titulo"]="Error de Configuración";      
        $resultado["Tipo"]="error"; 

        echo json_encode($resultado);

    return;
    }

}else{
    $query='SELECT * FROM Nomina WHERE Nomina = "'.date('Y-m').'" ' ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $config = $oCon->getData();
    unset($oCon);

    if(empty($config)){
        EditarConfiguracion($id, $datos);
    }else{
        $resultado["Mensaje"]="No es posible cambiar la configuración para este periodo";      
        $resultado["Titulo"]="Error de Configuración";      
        $resultado["Tipo"]="error"; 

        echo json_encode($resultado);

    return;
    }
}

function EditarConfiguracion($id,$datos){
    $oItem = new complex('Configuracion','Id_Configuracion', $id);
    $oItem->PagoNomina = $datos;
    $oItem->save();
    unset($oItem);
}
    $resultado["Mensaje"]="Configuracion de Nomina Guardado Exitosamente";      
    $resultado["Titulo"]="Operacion Exitosa";      
    $resultado["Tipo"]="success"; 
   
    

echo json_encode($resultado);
