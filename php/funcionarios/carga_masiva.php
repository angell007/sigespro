<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once 'HTTP/Request2.php';


$y=-1;
if (!empty($_FILES['Archivo']['name'])){
  $arrResult = array();
  $final="";
  $total=0;
  $handle = fopen($_FILES['Archivo']['tmp_name'], "r");
  if($handle){
  	$h=0;
    while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) { 
      $y++;
      $arrResult[] = $data;
	  $oItem5 = new complex('Funcionario','Identificacion_Funcionario',utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))));
	  $func= $oItem5->getData();
	  //unset($oItem5);
		
		if(!isset($func["Identificacion_Funcionario"])){	 $h++;  
			  $oItem = new complex('Funcionario','Identificacion_Funcionario');
			  /*if(trim($arrResult[$y][1])!==""){
			  	$oItem->Jefe=trim($arrResult[$y][1]);
			  }*/
			  
			  $oItem->Identificacion_Funcionario=utf8_encode(ucwords(strtolower(trim($arrResult[$y][0]))));
			  $oItem->Codigo=utf8_encode(ucwords(strtolower(trim($arrResult[$y][1]))));	  
			  $oItem->Nombres=utf8_encode(ucwords(strtolower(trim($arrResult[$y][2]))));	  
			  $oItem->Apellidos=utf8_encode(ucwords(strtolower(trim($arrResult[$y][3]))));	
			  
			  $oItem->Correo=utf8_encode(strtolower(trim($arrResult[$y][5])));  
			  $oItem->Sexo=utf8_encode(ucwords(strtolower(trim($arrResult[$y][6]))));
			  $oItem->Id_Dependencia=utf8_encode(ucwords(strtolower(trim($arrResult[$y][7]))));
			  $oItem->Id_Grupo=utf8_encode(ucwords(strtolower(trim($arrResult[$y][8]))));
			  $oItem->Telefono=utf8_encode(ucwords(strtolower(trim($arrResult[$y][9]))));
			  $oItem->Celular=utf8_encode(ucwords(strtolower(trim($arrResult[$y][10]))));
			  $oItem->Tipo_Sangre=utf8_encode(ucwords(strtolower(trim($arrResult[$y][11]))));	  
			  $oItem->Direccion_Residencia=utf8_encode(ucwords(strtolower(trim($arrResult[$y][12]))));
			  
			  $oItem->Username=md5(utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))));
			  $oItem->Password=md5(utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))));
			  
			  //$oItem->Fecha_Nacimiento=utf8_encode(ucwords(strtolower(trim($arrResult[$y][3]))));	  
			  //$oItem->Lugar_Nacimiento=utf8_encode(ucwords(strtolower(trim($arrResult[$y][5]))));	 
			  //$oItem->Estado_Civil=utf8_encode(ucwords(strtolower(trim($arrResult[$y][10]))));
			  //$oItem->Hijos=utf8_encode(ucwords(strtolower(trim($arrResult[$y][11]))));
			  //$oItem->Grado_Instruccion=utf8_encode(ucwords(strtolower(trim($arrResult[$y][12]))));
			  //$oItem->Titulo_Estudio=utf8_encode(ucwords(strtolower(trim($arrResult[$y][13]))));
			  //$oItem->Id_Cargo=utf8_encode(ucwords(strtolower(trim($arrResult[$y][4]))));
			  
			  $oItem->Tipo_Turno="Fijo";
			  $oItem->Id_Turno=1;
			  $oItem->Fecha_Ingreso='2018-01-01';
			  
			  //$oItem->Id_Proceso=utf8_encode(ucwords(strtolower(trim($arrResult[$y][18]))));
			  //$oItem->Salario=utf8_encode(ucwords(strtolower(trim($arrResult[$y][11]))));
			  //$oItem->Imagen=utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))).".jpg";
			  //$oItem->Jefe=utf8_encode(ucwords(strtolower(trim($arrResult[$y][3]))));
			  //$oItem->Id_Dependencia=utf8_encode(ucwords(strtolower(trim($arrResult[$y][4]))));
			  
			  //$oItem->save();
			  unset($oItem);
			  
			  /*
			  $oItem = new complex('Funcionario','Identificacion_Funcionario',utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))));
			  $funcionario=$oItem->getData();
			  unset($oItem);
			  */
			  /*
			  $oItem = new complex('Funcionario_Contacto_Emergencia','Identificacion_Funcionario_Contacto_Emergencia');
			  $oItem->Identificacion_Funcionario=utf8_encode(ucwords(strtolower(trim($arrResult[$y][0]))));
			  $oItem->Parentesco=utf8_encode(ucwords(strtolower(trim($arrResult[$y][22]))));
			  $oItem->Nombre=utf8_encode(ucwords(strtolower(trim($arrResult[$y][23]))));
			  $oItem->Celular=utf8_encode(ucwords(strtolower(trim($arrResult[$y][24]))));
			  $oItem->save();
			  unset($oItem);
			  */
			  echo "NEW ". $h." - ".utf8_encode(ucwords(strtolower(trim($arrResult[$y][0]))))." - ".utf8_encode(ucwords(strtolower(trim($arrResult[$y][1]))))."<br>";
			  /* GUARDA PERSONA EN MICROSOFT */
			  
			  /*
			  if(isset($funcionario["Identificacion_Funcionario"])){
				$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons');
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
				$body = array(
					"name"=>$funcionario["Nombres"]." ".$funcionario["Apellidos"],
				    "userData"=>$funcionario["Identificacion_Funcionario"]
				);
				$url->setQueryVariables($parameters);
				$request->setMethod(HTTP_Request2::METHOD_POST);
				$request->setBody(json_encode($body));
				try{
				    $response = $request->send();
				    $resp=$response->getBody();
					$resp=json_decode($resp);
					$person_id=$resp->personId;
					
					$oItem = new complex('Funcionario','Identificacion_Funcionario',$funcionario["Identificacion_Funcionario"]);
					$oItem->personId=$person_id;
					$func=$oItem->getData();
					$oItem->save();
					unset($oItem);	
					
				}catch (HttpException $ex){
				    echo "error: ".$ex;
				}
			   
			   
				}
			   */
				/* GUARDA FOTO DE PERSONA */
				/*
				 if($func["Imagen"]!=""){
					$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons/'.$person_id.'/persistedFaces');
					$url = $request->getUrl();
					$request->setConfig(array(
					    'ssl_verify_peer'   => FALSE,
					    'ssl_verify_host'   => FALSE
					));
					$headers = array(
					    'Content-Type' => 'application/json',
					    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
					);
					
					$request->setHeader($headers);
					$parameters = array(
					);
					$body=array(
						"url"=>$URL."IMAGENES/FUNCIONARIOS/" . $func["Imagen"]
					);
					$url->setQueryVariables($parameters);
					$request->setMethod(HTTP_Request2::METHOD_POST);
					$request->setBody(json_encode($body));
					try{
					    $response = $request->send();
					    $resp=$response->getBody();
						$resp=json_decode($resp);
						$persistedFaceId=$resp->persistedFaceId;
						$oItem = new complex('Funcionario','Identificacion_Funcionario',$funcionario["Identificacion_Funcionario"]);
						$oItem->persistedFaceId=$persistedFaceId;
						$oItem->save();
						unset($oItem);
					}catch (HttpException $ex){
					    echo $ex;
					}	
				}
		
		*/
		}else{
			$h++;
			echo $h." - ".utf8_encode(ucwords(strtolower(trim($arrResult[$y][0]))))." - ".utf8_encode(ucwords(strtolower(trim($arrResult[$y][1]))))."<br>"; 
			//$oItem5->Fecha_Ingreso=utf8_encode(ucwords(strtolower(trim($arrResult[$y][1]))));
			//$oItem5->Salario=0;
			$oItem5->personId=trim($arrResult[$y][1]);
			$oItem5->save();  
		}		
    }
    fclose($handle);
  }
 
 /*
 $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/train');
$url = $request->getUrl();
$request->setConfig(array(
    'ssl_verify_peer'   => FALSE,
    'ssl_verify_host'   => FALSE
));
$headers = array(
    'Ocp-Apim-Subscription-Key' => $AZURE_ID,
);
$request->setHeader($headers);
$parameters = array(

);
$url->setQueryVariables($parameters);
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setBody("");
try{
    $response = $request->send();
    echo $response->getBody();
}catch (HttpException $ex){
    echo $ex;
}
*/
  //$text="Completado";
}else{
  $text="Sin Archivo";
}
//echo $text;
?>



