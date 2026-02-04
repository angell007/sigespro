<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

ini_set('memory_limit', '512M');

function sendPush($to,$title,$message){
	define( 'API_ACCESS_KEY', 'AIzaSyC5BGfHtl4lj2fDGUdbRwmCCxW2MKkZ8Hw');
	$registrationIds = array($to);
	$msg = array(
			'message' => $message,
			'title' => $title,
			'vibrate' => 1,
			'sound' => 1
			);
	$fields = array(
				'registration_ids' => $registrationIds,
				'data' => $msg
				);
	$headers = array(
				'Authorization: key=' . API_ACCESS_KEY,
				'Content-Type: application/json'
				);
	$ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
	curl_setopt( $ch,CURLOPT_POST, true );
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
	$result = curl_exec($ch );
	curl_close( $ch );
	//echo $result;
}

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );


$datos = (array) json_decode($datos, true);

$oLista= new lista('Funcionario');
if($datos["Grupo"]!="Todas"){
	$oLista->setRestrict("Id_Grupo","=",$datos["Grupo"]);
	
}
if($datos["Dependencia"]!="Todas"){
	$oLista->setRestrict("Id_Dependencia","=",$datos["Dependencia"]);
}
if($datos["Funcionario"]!="Todas"){
	$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Funcionario"]);
}
$oLista->setRestrict("Fecha_Ingreso","<=",date("Y-m-d"));
$oLista->setRestrict("Fecha_Retiro",">=",date("Y-m-d"));
$funcionarios = $oLista->getList();
unset($oLista);

if ($funcionarios) {
  foreach($funcionarios as $fun){   
	$datos['Fecha']=date("Y-m-d H:i:s");
	$datos['Tipo']="Alerta";
	$datos['Identificacion_Funcionario']=$fun["Identificacion_Funcionario"];
	$oItem = new complex('Alerta','Id_Alerta');
	foreach($datos as $index=>$value) {
	    $oItem->$index=$value;
	}
	$oItem->save();
	$notificacion = $oItem->getId();
	unset($oItem);
	 
	/*if($fun["Gcm_Id"]!=""){
		sendPush($fun["Gcm_Id"],'Nueva Alertas',$datos['Detalles']); 
	} */
	
    }
    
    if($notificacion != ""){
        $resultado['estatus'] = 2;
        $resultado['mensaje'] = "Se ha enviado correctamente la notificacion";
        $resultado['tipo'] = "success";
    }else{
        $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
        $resultado['tipo'] = "error";
        $resultado['estatus'] = 3;
    }
} else {
    $resultado["estatus"] = 1;
}







echo json_encode($resultado);

?>