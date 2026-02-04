<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// Inicio llamado funciones basicas sistema
require_once("../../config/start.inc.php");

include_once($MY_CLASS . "class.consulta.php");
include_once($MY_CLASS . "class.complex.php");
// Fin llamado funciones basicas sistema

// Inicio captura variables
 $username  = (isset($_REQUEST['username'] ) ? $_REQUEST['username'] : "" );
 $password  = (isset($_REQUEST['password'] ) ? $_REQUEST['password'] : "" );
// Fin captura variables


    // Inicio busqueda empleados usuario existe
	 $query = "SELECT *, '$username' AS Identificacion_Funcionario, '5bd4d56bc5df7.jpg' AS Imagen FROM Usuario WHERE User = '".md5($username)."'";
	 $oCon = new consulta();
	 $oCon->setQuery($query);
	 $lista = $oCon->getData();
	 unset($oCon);
	// Fin busqueda empleados usuario existe

	// Inicio validar contraseña usuario
	 if ( (count($lista)==0)){
	  $error='Funcionario No Existe';
	 }elseif($lista["Password"]!=md5($password)){
	  $error='Contraseña Incorrecta';
	 }elseif($lista["Estado"]!="Activo"){
	  $error='Funcionario No Autorizado';
	 }else{
	  $error=false;
	 }
	
	 if($error===false){

	  $img= "https://192.168.40.201/IMAGENES/FUNCIONARIOS/5bd4d56bc5df7.jpg";
	  $lista['Nombre_Func'] = $lista["Nombre"];
	  $lista['Nombres'] = $lista["Nombre"];
	  $lista["Imagen_Func"]=$img;
	  
	  if(!isset($_SESSION)){ session_start(); }
	  
	  $token =  uniqid();
	  
	  $_SESSION["login"]="Si";
	  $_SESSION["user"]=$lista["User"];
	  $_SESSION["hora"]=date("Y-m-d H:i:s");
	  $_SESSION["Token"]=$token;

	  $lista['Last_Login'] = registerLastLogin($lista['Id_Usuario']);
	  
	   $respuesta["Error"]="No";
	   $respuesta["Mensaje"]=$error;
	   $respuesta["Token"] = $token;
	   $respuesta["Funcionario"] = $lista;

	 }else{
	  	$respuesta["Error"]="Si";
	    $respuesta["Mensaje"]=$error;
	 }
	// fin validar contraseña usuario 
    
	echo json_encode($respuesta);
	
	function registerLastLogin($id) {
		$oItem = new complex('Usuario','Id_Usuario',$id);
		$oItem->Last_Login = date('Y-m-d H:i:s');
		$oItem->save();
		unset($oItem);

		return date('Y-m-d H:i:s');
	}
	
?>