<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

function claveAleatoria($longitud = 10, $opcLetra = TRUE, $opcNumero = TRUE, $opcMayus = TRUE, $opcEspecial = TRUE){
$letras ="abcdefghijklmnopqrstuvwxyz";
$numeros = "1234567890";
$letrasMayus = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$especiales ="@#$%=*+";
$listado = "";
$password = ""; 
if ($opcLetra == TRUE) $listado .= $letras;
if ($opcNumero == TRUE) $listado .= $numeros;
if($opcMayus == TRUE) $listado .= $letrasMayus;
if($opcEspecial == TRUE) $listado .= $especiales;

for( $i=1; $i<=$longitud; $i++) {
$caracter = $listado[rand(0,strlen($listado)-1)];
$password.=$caracter;
$listado = str_shuffle($listado);
}
return $password;
}

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos["Username"]=md5($datos["Identificacion_Funcionario"]);
$pass=claveAleatoria();
$datos["Password"]=md5($pass);
$datos["Autorizado"]="Si";
$oItem = new complex("Funcionario","Identificacion_Funcionario");
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);

$oLista = new lista("Funcionario");
$oLista->setRestrict("Identificacion_Funcionario","!=",1127943747);
$funcionarios= $oLista->getlist();
unset($oLista);


$i=-1;
foreach($funcionarios as $funcionario){ $i++;
	$funcionarios[$i]["Cupo"]="$ ".number_format($funcionario["Cupo"],0,",",".");
}
$final["Usuario"]=$datos["Identificacion_Funcionario"];
$final["Clave"]=$pass;
$final["funcionarios"]=$funcionarios;

echo json_encode($final);

?>