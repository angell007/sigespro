<?php
error_reporting(0);

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once 'HTTP/Request2.php';
require('elibom/elibom.php');
											    
date_default_timezone_set('America/Bogota');

function RestarHoras($horaini,$horafin)
{
	$horai=substr($horaini,0,2);
	$mini=substr($horaini,3,2);
	$segi=substr($horaini,6,2);
 
	$horaf=substr($horafin,0,2);
	$minf=substr($horafin,3,2);
	$segf=substr($horafin,6,2);
 
	$ini=((($horai*60)*60)+($mini*60)+$segi);
	$fin=((($horaf*60)*60)+($minf*60)+$segf);
 
	$dif=$fin-$ini;
	$band=0;
	if($dif<0){
		$dif=$dif*(-1);
		$band=1;
	}
 
	$difh=floor($dif/3600);
	$difm=floor(($dif-($difh*3600))/60);
	$difs=$dif-($difm*60)-($difh*3600);
	if($band==0){
		return "-".date("H:i:s",mktime($difh,$difm,$difs));
	}else{
		return date("H:i:s",mktime($difh,$difm,$difs));
	}
	
}

$dias = array(
	0=> "Domingo",
	1=> "Lunes",
	2=> "Martes",
	3=> "Miercoles",
	4=> "Jueves",
	5=> "Viernes",
	6=> "Sabado"
);
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


$imagen = ( isset( $_REQUEST['imagen'] ) ? $_REQUEST['imagen'] : '' );
$latitud = ( isset( $_REQUEST['latitud'] ) ? $_REQUEST['latitud'] : '' );
$longitud = ( isset( $_REQUEST['longitud'] ) ? $_REQUEST['longitud'] : '' );

list($type, $imagen) = explode(';', $imagen);
list(, $imagen)      = explode(',', $imagen);
$imagen = base64_decode($imagen);

$fot="foto".uniqid().".jpg";
$archi=$MY_FILE . "IMAGENES/TEMPORALES/".$fot;
file_put_contents($archi, $imagen);
chmod($archi, 0644);

/* INICIO DETECCION DE CARA*/
$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/detect');
$url = $request->getUrl();
$headers = array(
    'Content-Type' => 'application/json',
    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
);
$request->setConfig(array(
    'ssl_verify_peer'   => FALSE,
    'ssl_verify_host'   => FALSE
));
$request->setHeader($headers);
$parameters = array(
    'returnFaceId' => 'true',
    'returnFaceLandmarks' => 'true',
    'returnFaceAttributes' => 'age,gender',
);
$url->setQueryVariables($parameters);
$request->setMethod(HTTP_Request2::METHOD_POST);
$body = array(
    'url'   => $URL.'IMAGENES/TEMPORALES/'.$fot
);
$request->setBody(json_encode($body));

try{
    $response = $request->send();
    $resp=$response->getBody();
	$resp=json_decode($resp);
	if(is_array($resp)){
	   $face_id=$resp[0]->faceId; 
	}
	
}catch (HttpException $ex){
    echo $ex;
}
/* FIN DETECCION DE CARA */
if($face_id!=""){
/* INICIO DE IDENTIFICACIÓN DE ROSTRO */
$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/identify');
$url = $request->getUrl();
$headers = array(
    'Content-Type' => 'application/json',
    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
);
$request->setConfig(array(
    'ssl_verify_peer'   => FALSE,
    'ssl_verify_host'   => FALSE
));
$request->setHeader($headers);
$parameters = array(
);
$url->setQueryVariables($parameters);
$request->setMethod(HTTP_Request2::METHOD_POST);
$body = array(
    'personGroupId' => $AZURE_GRUPO,
    'faceIds' => [
        $face_id
    ],
    "maxNumOfCandidatesReturned"=>1
);
$request->setBody(json_encode($body));

try{
    $response = $request->send();
    $resp=$response->getBody();
	$resp=json_decode($resp);
	$candidatos=$resp[0]->candidates[0];
	
	if(!is_null($candidatos)){
		$candidato=$candidatos->personId;
		if($candidato!=""){
			$oItem = new complex('Funcionario','personId',$candidato);
			$funcionario=$oItem->getData();
			unset($oItem);
			$hactual=date("H:i:s");
			$hoy=date('Y-m-d');
			$ayer=date("Y-m-d", strtotime(date("Y-m-d").' - 1 day'));
			
			
			//$hactual="18:10:50";
			//$hoy="2017-06-22";
			//$ayer="2017-06-21";
			if($funcionario["Tipo_Turno"]=="Rotativo"){
				$oLista= new lista('Horario');
				$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
				$oLista->setRestrict("Fecha","=",$ayer);
				$horario_ayer=$oLista->getList();
				
				$oLista= new lista('Horario');
				$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
				$oLista->setRestrict("Fecha","=",$hoy);
				$horario_hoy=$oLista->getList();
				
				$salida_ayer=1;
				if(isset($horario_ayer[0]["Id_Horario"])){
					if($horario_ayer[0]["Id_Turno"]!=0){
						$oItem = new complex('Turno','Id_Turno',$horario_ayer[0]["Id_Turno"]);
						$turno=$oItem->getData();
						unset($oItem);
						
						if(strtotime($turno["Hora_Inicio1"])>=strtotime("18:00:00")){
							$oLista= new lista('Diario');
							$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
							$oLista->setRestrict("Fecha","=",$ayer);
							$diario=$oLista->getList();
							
							if(isset($diario[0]["Id_Diario"])){
								if($diario[0]["Hora_Salida"]=="00:00:00"){
									$respuesta["Icono"]="success";
									$respuesta["Titulo"]="Gracias por Trabajar con Nosotros";
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos Vemos Mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
									$oItem = new complex('Diario','Id_Diario',$diario[0]["Id_Diario"]);
									$oItem->Fecha_Salida=$hoy;
									$oItem->Hora_Salida=$hactual;
									$oItem->Img2=$fot;
									$oItem->save();
									unset($oItem);
									$salida_ayer=0; 
								}
							}
						}
					}
				}
				if($salida_ayer==1&&isset($horario_hoy[0]["Id_Horario"])){
					if($horario_hoy[0]["Id_Turno"]!=0){
						$oItem = new complex('Turno','Id_Turno',$horario_hoy[0]["Id_Turno"]);
						$turno=$oItem->getData();
						unset($oItem);
						
						$oItem = new complex('Proceso','Id_Proceso',$horario_hoy[0]["Id_Proceso"]);
						$proceso=$oItem->getData();
						unset($oItem);
							
						$oLista= new lista('Diario');
						$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
						$oLista->setRestrict("Fecha","=",$hoy);
						$diario=$oLista->getList();
								
						if(!isset($diario[0]["Id_Diario"])){
									$diferencia=RestarHoras($hactual,$turno["Hora_Inicio1"]);
									$diferencia=explode(":",$diferencia);
									
									if($diferencia[0]<0){
										$respuesta["Icono"]="success";
										$respuesta["Titulo"]="Acceso Autorizado";
										$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado temprano</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));
										
										$oItem = new complex('Diario','Id_Diario');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Id_Turno=$turno["Id_Turno"];
										$oItem->Proceso=$proceso["Codigo"];
										$oItem->Hora_Entrada=$hactual;
										$oItem->Img1=$fot;
										$oItem->save(); 
										unset($oItem);
									
									}elseif($diferencia[0]>0){
										
										
										
										$respuesta["Icono"]="success";
										$respuesta["Titulo"]="Acceso Autorizado";
										$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado tarde</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));;
									
										$oItem = new complex('Diario','Id_Diario');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Id_Turno=$turno["Id_Turno"];
										$oItem->Proceso=$proceso["Codigo"];
										$oItem->Hora_Entrada=$hactual;
										$oItem->Img1=$fot;
										$oItem->save();
										unset($oItem);
										
										$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
										$tol_ent=($turno["Tolerancia_Entrada"]*60);
										
										if($diff>$tol_ent){
											$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
											$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
											$oItem->Fecha=$hoy;
											$oItem->Tiempo=$diff;
											$oItem->Id_Dependencia=$funcionario["Id_Dependencia"];
											$oItem->Id_Grupo=$funcionario["Id_Grupo"];
											$oItem->Entrada_Turno=$turno["Hora_Inicio1"];
											$oItem->Entrada_Real=$hactual;
											$oItem->save();
											unset($oItem);
											
											$oItem = new complex('Alerta','Id_Alerta');
											$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
											$oItem->Fecha=$hoy." ".$hactual;
											$oItem->Tiempo=$diff;
											$oItem->Tipo="Llegada Tarde";
											$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
											$oItem->save();
											unset($oItem);
											
											
										}
									}else{
										$respuesta["Icono"]="success";
										$respuesta["Titulo"]="Acceso Autorizado";
										$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
										$oItem = new complex('Diario','Id_Diario');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Id_Turno=$turno["Id_Turno"];
										$oItem->Proceso=$proceso["Codigo"];
										$oItem->Hora_Entrada=$hactual;
										$oItem->Img1=$fot;
										$oItem->save();
										unset($oItem);
										
										/* if(strpos($diferencia[0],"-")===false){
											$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
											$tol_ent=($turno["Tolerancia_Entrada"]*60);
											
											if($diff>$tol_ent){
												$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
												$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
												$oItem->Fecha=$hoy;
												$oItem->Tiempo=$diff;
												$oItem->save();
												unset($oItem);
												
												$oItem = new complex('Alerta','Id_Alerta');
												$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
												$oItem->Fecha=$hoy." ".$hactual;
												$oItem->Tiempo=$diff;
												$oItem->Tipo="Llegada Tarde";
												$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
												$oItem->save();
												unset($oItem);
												
											}
										} */
									}
								}else{
										if($diario[0]["Hora_Salida"]=="00:00:00"){
											$respuesta["Icono"]="success";
											$respuesta["Titulo"]="Hasta Mañana";
											$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos Vemos Mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
												
											$oItem = new complex('Diario','Id_Diario',$diario[0]["Id_Diario"]);
											$oItem->Hora_Salida=$hactual;
											$oItem->Fecha_Salida=$hoy;
											$oItem->Img2=$fot;
											$oItem->save();
											unset($oItem);
										}else{
											$respuesta["Icono"]="warning";
											$respuesta["Titulo"]="Ya ha reportado turno hoy";
											$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya reportaste entrada y salida de turno hoy <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
										}
								}
						}else{
								$respuesta["Icono"]="error";
								$respuesta["Titulo"]="Acceso Denegado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>De acuerdo a la programación, hoy es su día libre</strong>";					
						}
				}elseif($salida_ayer==1&&!isset($horario_hoy[0]["Id_Horario"])){
					$respuesta["Icono"]="error";
					$respuesta["Titulo"]="Acceso Denegado";
					$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>No tiene un turno asignado para este día, por favor comuníquese con su supervisor.</strong>";  
					
				}
				
			}elseif($funcionario["Tipo_Turno"]=="Fijo"){
				
				if($funcionario["Id_Turno"]!=0){
						$oItem = new complex('Turno','Id_Turno',$funcionario["Id_Turno"]);
						$turno=$oItem->getData();
						unset($oItem);
						
						$oItem = new complex('Proceso','Id_Proceso',$funcionario["Id_Proceso"]);
						$proceso=$oItem->getData();
						unset($oItem);
						
						$oLista= new lista('Hora_Turno');
						$oLista->setRestrict("Id_Turno","=",$funcionario["Id_Turno"]);
						$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($hoy))]);
						$horas=$oLista->getList();
						
						$oLista= new lista('Diario_Fijo');
						$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
						$oLista->setRestrict("Fecha","=",$hoy);
						$diario=$oLista->getList();
						
						$oLista= new lista('Compensatorio');
						$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
						$oLista->setRestrict("Fecha","=",$hoy);
						$oLista->setRestrict("Hora_Inicio","<=",$hactual); 
						$compensatorio=$oLista->getList();
								
						if(!isset($diario[0]["Id_Diario_Fijo"])){
									if($hactual<='12:00:00'){
										if(isset($compensatorio[0]["Hora_Inicio"])){
											$diferencia=RestarHoras($hactual,$compensatorio[0]["Hora_Inicio"]);
											$h_inicio=$compensatorio[0]["Hora_Inicio"];
										}else{
											$diferencia=RestarHoras($hactual,$horas[0]["Hora_Inicio1"]);
											$h_inicio=$horas[0]["Hora_Inicio1"];
										}
										
									}else{
										if(isset($compensatorio[0]["Hora_Inicio"])){
											$diferencia=RestarHoras($hactual,$compensatorio[0]["Hora_Inicio"]);
											$h_inicio=$compensatorio[0]["Hora_Inicio"];
										}else{
											$diferencia=RestarHoras($hactual,$horas[0]["Hora_Inicio2"]);
											$h_inicio=$horas[0]["Hora_Inicio2"];
										}
									}
							
									
									$dife=$diferencia;
									$diferencia=explode(":",$diferencia);
									
									$sig=1;
									if(strpos($diferencia[0],"-")!==false){
										$sig=-1;
										$diferencia[0]=str_replace("-", "", $diferencia[0]);
									}
									$diff_a=$diferencia[0]*60;
									$diff_b=($diff_a+$diferencia[1])*$sig;
									
									$diff=(($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]))*$sig;
									$tol_ent=($turno["Tolerancia_Entrada"]*60);
									
									if($diff<$tol_ent){
										$respuesta["Icono"]="success";
										$respuesta["Titulo"]="Acceso Autorizado";
										$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado temprano</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));
										
										$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Id_Turno=$turno["Id_Turno"];
										$oItem->Proceso=$proceso["Codigo"];
										$oItem->Hora_Entrada1=$hactual;
										$oItem->Img1=$fot;
										if($latitud!=""){
											$oItem->Latitud=$latitud;
										}
										if($longitud!=""){
											$oItem->Longitud=$longitud;
										}
										$oItem->save();
										unset($oItem);
									
									}elseif($diff>=$tol_ent){
										$fechames=date("Y-m-d", strtotime(date("Y-m-d").' - 40 days'));
										$oLista= new lista("Llegada_Tarde");
										$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
										$oLista->setRestrict("Fecha","<=",$hoy);
										$oLista->setRestrict("Fecha",">=",$fechames);
										$oLista->setRestrict("Estado","=","Nueva");
										$oLista->setRestrict("Cuenta","=","Si");
										$tardias=$oLista->getList(); 
										
										$num_tarde=count($tardias);
										$num_tarde++;
										
										if($num_tarde==1){
											$lleg="Hoy ha llegado tarde.";
											$sms_pi='';
											$sms_jd='';
											$sms_th='';
										}elseif($num_tarde==2){
											$lleg="Es su segunda llegada tarde en 40 días.";
											$sms_pi='Hoy es su segunda llegada tarde en 40 días, por favor, intenta mejorar para la próxima. Feliz día. Control Acceso Sevicol';
											$sms_jd='';
											$sms_th='';
										}elseif($num_tarde==3){
											$lleg="Es su tercera llegada tarde en 40 días, se le ha enviado un memorando con su jefe directo.";
											$sms_pi='Es su tercera llegada tarde en 40 días, se le ha enviado un memorando con su jefe directo. Es importante mejorar. Feliz día. \nControl Acceso Sevicol';
											$sms_jd=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado tarde por tercera vez en 40 días, en el sistema esta cargado el memorando para que tome acciones. \nControl Acceso Sevicol";
											$sms_th='';
										}elseif($num_tarde>=4){
											$lleg="Ha llegado tarde por ".$num_tarde." vez en 40 días, por favor diríjase a la Oficina de Talento Humano.";
											$sms_pi="Ha llegado tarde por ".$num_tarde." vez en 40 días, por favor diríjase a la Oficina de Talento Humano. \nControl Acceso Sevicol";
											$sms_jd=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado tarde por ".$num_tarde." vez en 40 días, en este momento se deben dirigir a Oficina de Talento Humano. \nControl Acceso Sevicol";
											$sms_th=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado tarde por ".$num_tarde." vez en 40 días, en este momento se deben dirigir a su Oficina. \nControl Acceso Sevicol";
										}
										
										$respuesta["Icono"]="success";
										$respuesta["Titulo"]="Acceso Autorizado";
										$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br><strong style='color:red;'>".$lleg."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));;
										
										

										$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Id_Turno=$turno["Id_Turno"];
										$oItem->Proceso=$proceso["Codigo"];
										$oItem->Hora_Entrada1=$hactual;
										$oItem->Img1=$fot;
										if($latitud!=""){
											$oItem->Latitud=$latitud;
										}
										if($longitud!=""){
											$oItem->Longitud=$longitud;
										}
										$oItem->save();
										unset($oItem);
										
										$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Tiempo=$diff;
										$oItem->Id_Dependencia=$funcionario["Id_Dependencia"];
										$oItem->Id_Grupo=$funcionario["Id_Grupo"];
										$oItem->Entrada_Turno=$h_inicio;
										$oItem->Entrada_Real=$hactual;
										$oItem->save();
										unset($oItem);
										$oItem = new complex('Alerta','Id_Alerta');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy." ".$hactual;
										$oItem->Tiempo=$diff;
										$oItem->Tipo="Llegada Tarde";
										$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
										$oItem->save();
										unset($oItem);
										
										if($sms_pi!=""){
											
											if($funcionario["Gcm_Id"]!=""){
												sendPush($funcionario["Gcm_Id"],'Usted ha llegado Tarde',$sms_pi); 
											}
											/*$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
										    $tele = '57'.$funcionario["Celular"];
										    $deliveryId = $elibom->sendMessage($tele,$sms_pi);
										    $info= $elibom->getDelivery($deliveryId);*/
										}
										if($sms_jd!=""){
											$oItem = new complex('Funcionario','Identificacion_Funcionario',$funcionario["Jefe"]);
											$jd=$oItem->getData();
											unset($oItem);
											
											if($jd["Gcm_Id"]!=""){
												sendPush($jd["Gcm_Id"],'Alguien ha llegado Tarde',$sms_jd); 
											}
										
											/*$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
										    $tele = '57'.$jd["Celular"];
										    $deliveryId = $elibom->sendMessage($tele,$sms_jd);
										    $info= $elibom->getDelivery($deliveryId);*/
										}
										if($sms_th!=""){
											$oItem = new complex('Funcionario','Identificacion_Funcionario',63321784);
											$jth=$oItem->getData();
											unset($oItem);
											
											if($jth["Gcm_Id"]!=""){
												sendPush($jth["Gcm_Id"],'Alguien ha llegado Tarde',$sms_th); 
											}
											
											/*
											$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
										    $tele = '573156249459';
										    $deliveryId = $elibom->sendMessage($tele,$sms_th);
										    $info= $elibom->getDelivery($deliveryId);*/
										}
									}else{
										$respuesta["Icono"]="success";
										$respuesta["Titulo"]="Acceso Autorizado";
										$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
										$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Id_Turno=$turno["Id_Turno"];
										$oItem->Proceso=$proceso["Codigo"];
										$oItem->Hora_Entrada1=$hactual;
										$oItem->Img1=$fot;
										if($latitud!=""){
											$oItem->Latitud=$latitud;
										}
										if($longitud!=""){
											$oItem->Longitud=$longitud;
										}
										$oItem->save();
										unset($oItem);
										
										/*if(strpos($diferencia[0],"-")===false){
											$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
											$tol_ent=($turno["Tolerancia_Entrada"]*60);
											
											if($diff>$tol_ent){
												$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
												$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
												$oItem->Fecha=$hoy;
												$oItem->Tiempo=$diff;
												$oItem->save();
												unset($oItem);
												
												$oItem = new complex('Alerta','Id_Alerta');
												$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
												$oItem->Fecha=$hoy." ".$hactual;
												$oItem->Tiempo=$diff;
												$oItem->Tipo="Llegada Tarde";
												$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
												$oItem->save();
												unset($oItem);
											}
										}*/
									}
								}else{
										if($diario[0]["Hora_Salida1"]=="00:00:00"){
											$respuesta["Icono"]="success";
											$respuesta["Titulo"]="Hasta Luego";
											$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Hasta Luego, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
												
											$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
											$oItem->Hora_Salida1=$hactual;
											$oItem->Img2=$fot;
											if($latitud!=""){
												$oItem->Latitud2=$latitud;
											}
											if($longitud!=""){
												$oItem->Longitud2=$longitud;
											}
											$oItem->save();
											unset($oItem);
										}elseif($diario[0]["Hora_Entrada2"]=="00:00:00"){
											
											$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
											$oItem->Hora_Entrada2=$hactual;
											$oItem->Img3=$fot;
											if($latitud!=""){
												$oItem->Latitud3=$latitud;
											}
											if($longitud!=""){
												$oItem->Longitud3=$longitud;
											}
											$oItem->save();
											unset($oItem);
											if(isset($compensatorio[0]["Hora_Inicio"])){
												$diferencia=RestarHoras($hactual,$compensatorio[0]["Hora_Inicio"]);
											}else{
												$diferencia=RestarHoras($hactual,$horas[0]["Hora_Inicio2"]);
											}
											
        									$diferencia=explode(":",$diferencia);
        									
											$sig=1;
											if(strpos($diferencia[0],"-")!==false){
												$sig=-1;
												$diferencia[0]=str_replace("-", "", $diferencia[0]);
											}
											
        									$diff=(($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]))*$sig;
        									$tol_ent=($turno["Tolerancia_Entrada"]*60);
        									
        									if($diff>=$tol_ent){
        									    
        									    $fechames=date("Y-m-d", strtotime(date("Y-m-d").' - 40 days'));
            									$oLista= new lista("Llegada_Tarde");
            									$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
            									$oLista->setRestrict("Fecha","<=",$hoy);
            									$oLista->setRestrict("Fecha",">=",$fechames);
												$oLista->setRestrict("Estado","=","Nueva");
												$oLista->setRestrict("Cuenta","=","Si");
            									$tardias=$oLista->getList();
            									
            									$num_tarde=count($tardias);
            									$num_tarde++;
            									
            									if($num_tarde==1){
            										$lleg="Hoy ha llegado tarde.";
            										$sms_pi='';
            										$sms_jd='';
            										$sms_th='';
            									}elseif($num_tarde==2){
            										$lleg="Es su segunda llegada tarde en 40 días.";
            										$sms_pi='Hoy es su segunda llegada tarde en 40 días, por favor, intenta mejorar para la próxima. Feliz día. \nControl Acceso Sevicol';
            										$sms_jd='';
            										$sms_th='';
            									}elseif($num_tarde==3){
            										$lleg="Es su tercera llegada tarde en 40 días, se le ha enviado un memorando con su jefe directo.";
            										$sms_pi='Es su tercera llegada tarde en 40 días, se le ha enviado un memorando con su jefe directo. Es importante mejorar. Feliz día. \nControl Acceso Sevicol';
            										$sms_jd=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado tarde por tercera vez en 40 días, en el sistema esta cargado el memorando para que tome acciones. \nControl Acceso Sevicol";
            										$sms_th='';
            									}elseif($num_tarde>=4){
            										$lleg="Ha llegado tarde por ".$num_tarde." vez en 40 días, por favor diríjase a la Oficina de Talento Humano.";
            										$sms_pi="Ha llegado tarde por ".$num_tarde." vez en 40 días, por favor diríjase a la Oficina de Talento Humano. \nControl Acceso Sevicol";
            										$sms_jd=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado tarde por ".$num_tarde." vez en 40 días, en este momento se deben dirigir a Oficina de Talento Humano. \nControl Acceso Sevicol";
            										$sms_th=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado tarde por ".$num_tarde." vez en 40 días, en este momento se deben dirigir a su Oficina. \nControl Acceso Sevicol";
            									}
            									
            									$respuesta["Icono"]="success";
    											$respuesta["Titulo"]="Bienvenido de Nuevo";
            									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br><strong style='color:red;'>".$lleg."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));;
            									
        										$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
        										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
        										$oItem->Fecha=$hoy;
        										$oItem->Tiempo=$diff;
												$oItem->Id_Dependencia=$funcionario["Id_Dependencia"];
												$oItem->Id_Grupo=$funcionario["Id_Grupo"];
        										$oItem->Entrada_Turno=$horas[0]["Hora_Inicio2"];
        										$oItem->Entrada_Real=$hactual;
        										$oItem->save();
        										unset($oItem);
        										
												$oItem = new complex('Alerta','Id_Alerta');
        										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
        										$oItem->Fecha=$hoy." ".$hactual;
        										$oItem->Tiempo=$diff;
        										$oItem->Tipo="Llegada Tarde";
        										$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
        										$oItem->save();
        										unset($oItem);
        										
        										if($sms_pi!=""){
											
													if($funcionario["Gcm_Id"]!=""){
														sendPush($funcionario["Gcm_Id"],'Usted ha llegado Tarde',$sms_pi); 
													}
													/*$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
												    $tele = '57'.$funcionario["Celular"];
												    $deliveryId = $elibom->sendMessage($tele,$sms_pi);
												    $info= $elibom->getDelivery($deliveryId);*/
												}
												if($sms_jd!=""){
													$oItem = new complex('Funcionario','Identificacion_Funcionario',$funcionario["Jefe"]);
													$jd=$oItem->getData();
													unset($oItem);
													
													if($jd["Gcm_Id"]!=""){
														sendPush($jd["Gcm_Id"],'Alguien ha llegado Tarde',$sms_jd); 
													}
												
													/*$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
												    $tele = '57'.$jd["Celular"];
												    $deliveryId = $elibom->sendMessage($tele,$sms_jd);
												    $info= $elibom->getDelivery($deliveryId);*/
												}
												if($sms_th!=""){
													$oItem = new complex('Funcionario','Identificacion_Funcionario',63321784);
													$jth=$oItem->getData();
													unset($oItem);
													
													if($jth["Gcm_Id"]!=""){
														sendPush($jth["Gcm_Id"],'Alguien ha llegado Tarde',$sms_th); 
													}
													
													/*
													$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
												    $tele = '573156249459';
												    $deliveryId = $elibom->sendMessage($tele,$sms_th);
												    $info= $elibom->getDelivery($deliveryId);*/
												}
        										
        										
        									}else{
        										$respuesta["Icono"]="success";
    											$respuesta["Titulo"]="Bienvenido de Nuevo";
            									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));;
            									 
        									}
										}elseif($diario[0]["Hora_Salida2"]=="00:00:00"){
											$respuesta["Icono"]="success";
											$respuesta["Titulo"]="Hasta Mañana";
											$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos vemos mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
												
											$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
											$oItem->Hora_Salida2=$hactual;
											$oItem->Img4=$fot;
											if($latitud!=""){
												$oItem->Latitud4=$latitud;
											}
											if($longitud!=""){
												$oItem->Longitud4=$longitud;
											}
											$oItem->save();
											unset($oItem);
										}else{
											$respuesta["Icono"]="warning";
											$respuesta["Titulo"]="Ya ha reportado turno hoy";
											$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya reportaste entrada y salida de turno hoy <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
										}
								}
				}else{
						$respuesta["Icono"]="error";
						$respuesta["Titulo"]="Acceso Denegado";
						$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Hoy no Tiene un Turno Asignado</strong>";					
				}
			}elseif($funcionario["Tipo_Turno"]=="Libre"){
				$oLista= new lista('Diario_Fijo');
				$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
				$oLista->setRestrict("Fecha","=",$hoy);
				$diario=$oLista->getList();
				
				$oItem = new complex('Proceso','Id_Proceso',$funcionario["Id_Proceso"]);
				$proceso=$oItem->getData();
				unset($oItem);
				
				if(!isset($diario[0]["Id_Diario_Fijo"])){
				
					$respuesta["Icono"]="success";
					$respuesta["Titulo"]="Acceso Autorizado";
					$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
										
					$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
					$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
					$oItem->Fecha=$hoy;
					$oItem->Id_Turno=0;
					$oItem->Hora_Entrada1=$hactual;
					$oItem->Proceso=$proceso["Codigo"];
					$oItem->Img1=$fot;
					$oItem->save();
					unset($oItem);
				
				}else{
					if($diario[0]["Hora_Salida1"]=="00:00:00"){
						$respuesta["Icono"]="success";
						$respuesta["Titulo"]="Hasta Luego";
						$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Hasta Luego, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
							
						$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
						$oItem->Hora_Salida1=$hactual;
						$oItem->Img2=$fot;
						$oItem->save();
						unset($oItem);
					}elseif($diario[0]["Hora_Entrada2"]=="00:00:00"){
						$respuesta["Icono"]="success";
						$respuesta["Titulo"]="Bienvenido de Nuevo";
						$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
							
						$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
						$oItem->Hora_Entrada2=$hactual;
						$oItem->Img3=$fot;
						$oItem->save();
						unset($oItem);
					}elseif($diario[0]["Hora_Salida2"]=="00:00:00"){
						$respuesta["Icono"]="success";
						$respuesta["Titulo"]="Hasta Mañana";
						$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos vemos mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
							
						$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
						$oItem->Hora_Salida2=$hactual;
						$oItem->Img4=$fot;
						$oItem->save();
						unset($oItem);
					}else{
						$respuesta["Icono"]="warning";
						$respuesta["Titulo"]="Ya ha reportado turno hoy";
						$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya reportaste entrada y salida de turno hoy <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
					}
				}
				
			
			}
			
			//$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s");  
		}else{
			$respuesta["Icono"]="error";
			$respuesta["Titulo"]="Acceso Denegado";
			$respuesta["Mensaje"]="Candidato Vacío";
		}
	}else{
		$respuesta["Icono"]="error";
		$respuesta["Titulo"]="Acceso Denegado";
		$respuesta["Mensaje"]="Su Cara no es conocida, favor contacte a un administrador";
	}
	
}catch (HttpException $ex){
    $respuesta["Icono"]="error";
	$respuesta["Titulo"]="Acceso Denegado";
	$respuesta["Mensaje"]="Error de Sistema ".$ex;
}

/* FIN DE IDENTIFICACIÓN DE ROSTRO */
}else{
	$respuesta["Icono"]="error";
	$respuesta["Titulo"]="Acceso Denegado";
	$respuesta["Mensaje"]="Error Inesperado identificando su rostro!";	
}

echo json_encode($respuesta);
?>