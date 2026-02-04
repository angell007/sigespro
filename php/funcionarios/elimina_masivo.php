<?php
// Inicio llamado funciones basicas sistema
require_once("../../config/start.inc.php");
include_once($MY_CLASS . "class.complex.php");
include_once($MY_CLASS . "class.imageresize.php");
include_once($MY_CLASS . "class.dao.php");
include_once('../../class/class.lista.php');
require_once 'HTTP/Request2.php';
// Fin llamado funciones basicas sistema

function fecha($str)
{
    $date = explode("/",$str);
    return $date[2] ."-".  $date[1] ."-". $date[0];
}

$oLista= new lista("Funcionario");
$oLista->setRestrict("Identificacion_Funcionario","!=","1127943747");
$funcionarios=$oLista->getList();
unset($oLista);

// Inicio captura variables
$identificacion_funcionario  = (isset($_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : '' );
$date  = (isset($_REQUEST['date'] ) ? $_REQUEST['date'] : '' );
// Final captura variables
 
// funcion borrar

foreach($funcionarios as $fun){
	$oItem = new complex('Funcionario','Identificacion_Funcionario',$fun["Identificacion_Funcionario"]);
	$per=$oItem->getData();
	$oItem->delete();
	unset($oItem);
	/*Eliminar de Microsoft */
	$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/persons/'.$per["personId"]);
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
}




?>