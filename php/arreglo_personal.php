<?php 
require_once("../config/start.inc.php");
include_once($MY_CLASS . "class.complex.php");
include_once($MY_CLASS . "class.imageresize.php");
include_once($MY_CLASS . "class.dao.php");
include_once($MY_CLASS . 'class.lista.php');
require_once 'HTTP/Request2.php';

ini_set('memory_limit', '-1');


    $oLista = new lista("Funcionario");
    $oLista->setRestrict("Imagen","!=","");
   // $oLista->setRestrict("personId","LIKE","Persona");
    
    $oLista->setRestrict("Autorizado","=","Si");
   // $oLista->setRestrict("personId","NOT LIKE","Persona");
   // $oLista->setRestrict("personId","NOT LIKE","Sin");
    // $oLista->setRestrict("personId","=","000");
    $oLista->setRestrict("personId","=",'');
    $oLista->setRestrict("persistedFaceId","=",'');
    //$oLista->setItems(3);
    $funcio = $oLista->getList();
    unset($oLista);
    
    echo "funcionarios: ".count($funcio)."<br>";

   
    foreach($funcio as $fun){
     
            //echo $fun["personId"]." - ".$fun["Nombres"]." ".$fun["Apellidos"]."<br>";
           
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
            	"name"=>$fun["Nombres"]." ".$fun["Apellidos"],
                "userData"=>$fun["Identificacion_Funcionario"]
            );
            $url->setQueryVariables($parameters);
            $request->setMethod(HTTP_Request2::METHOD_POST);
            $request->setBody(json_encode($body));
            try{
                $response = $request->send();
                $resp=$response->getBody();
            	$resp=json_decode($resp);
            	$person_id=$resp->personId;
            	var_dump($resp);
            	echo" ==================================================================>"."<br>";
            	$oItem = new complex('Funcionario','Identificacion_Funcionario',$fun["Identificacion_Funcionario"]);
            	$oItem->personId=$person_id;
            	$func=$oItem->getData();
            	$oItem->save();
            	unset($oItem);	
            	
            }catch (HttpException $ex){
                echo "error: ".$ex;
            }
            
            sleep(5);
           
            if($fun["Imagen"]!=""){
                
                
            	$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons/'.$func["personId"].'/persistedFaces');
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
            		"url"=>$URL."IMAGENES/FUNCIONARIOS/" . $fun["Imagen"]
            	);
            	$url->setQueryVariables($parameters);
            	$request->setMethod(HTTP_Request2::METHOD_POST);
            	$request->setBody(json_encode($body));
            	try{
            	    $response = $request->send();
            	    $resp=$response->getBody();
            		$resp=json_decode($resp);
            		var_dump($resp);
            		$persistedFaceId=$resp->persistedFaceId;
            		$oItem = new complex('Funcionario','Identificacion_Funcionario',$fun["Identificacion_Funcionario"]);
            		$oItem->persistedFaceId=$persistedFaceId;
            		$oItem->save();
            	}catch (HttpException $ex){
            	    echo $ex;
            	}
            	
            }
            
            /* ELIMINA DE MICROSOFT LA CARA */
           
           /* $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons/'.$fun["personId"]);
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
        	);
        	$url->setQueryVariables($parameters);
        	
        	$request->setMethod(HTTP_Request2::METHOD_DELETE);
        	$request->setBody($body);
        	try
        	{
        	    $response = $request->send();
        	    echo $response->getBody();
        	    
    	    	
        	
            	
        	}
        	catch (HttpException $ex)
        	{
        	    echo $ex;
        	} 
        	sleep(5);  */
        /*	$oItem = new complex('Funcionario','Identificacion_Funcionario',$fun["Identificacion_Funcionario"]);
        	$oItem->personId='Persona'.$fun["Identificacion_Funcionario"];
        	$oItem->save();
        	unset($oItem);	*/
    }

/*PERMITE QUE SE PUEDAN REVISAR LAS FOTOS NUEVAS */
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

?>