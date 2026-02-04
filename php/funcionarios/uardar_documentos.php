<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
$datos=(isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$contacto_emergencia = ( isset( $_REQUEST['contacto_emergencia']) ? $_REQUEST['contacto_emergencia'] : '' );
$experiencia = ( isset( $_REQUEST['experiencia'] ) ? $_REQUEST['experiencia'] : '' );
$referencias = ( isset( $_REQUEST['referencias'] ) ? $_REQUEST['referencias'] : '' );
$perfil_id = ( isset( $_REQUEST['id_perfil'] ) ? $_REQUEST['id_perfil'] : '' );
$auth = ( isset( $_REQUEST['autoriza'] ) ? $_REQUEST['autoriza'] : '' );
$contrato = ( isset( $_REQUEST['contrato'] ) ? $_REQUEST['contrato'] : '' );


$funcionario = (array) json_decode($funcionario , true);
$contacto_emergencia = (array) json_decode($contacto_emergencia , true);
$experiencia = (array) json_decode($experiencia , true);
$referencias = (array) json_decode($referencias , true);
$contrato = (array) json_decode($contrato , true);
$perfil_id = json_decode($perfil_id);
$auth = json_decode($auth);


$funcionario['Username'] = md5($funcionario['Identificacion_Funcionario']);
$funcionario['Password'] = md5($funcionario['Identificacion_Funcionario']);
$funcionario['Autorizado'] = $auth;


/*var_dump($perfil_id);
var_dump($auth);
var_dump($funcionario);
exit;*/

/* GUARDA FUNCIONARIO */	
if (!empty($_FILES['Foto']['name'])){
	$posicion1 = strrpos($_FILES['Foto']['name'],'.')+1;
	$extension1 =  substr($_FILES['Foto']['name'],$posicion1);
	$extension1 =  strtolower($extension1);
	$_filename1 = uniqid() . "." . $extension1;
	$_file1 = $MY_FILE . "IMAGENES/FUNCIONARIOS/" . $_filename1;
	
	$ancho="800";
	$alto="800";	
	$subido1 = move_uploaded_file($_FILES['Foto']['tmp_name'], $_file1);
		if ($subido1){
			list($width, $height, $type, $attr) = getimagesize($_file1);		
			@chmod ( $_file1, 0777 );
			$funcionario["Imagen"] = $_filename1;
		} 
}

		
$oItem = new complex('Funcionario','Identificacion_Funcionario');
foreach($funcionario as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);

if (!file_exists( $MY_FILE.'DOCUMENTOS/'.$funcionario["Identificacion_Funcionario"])) {
    mkdir($MY_FILE.'DOCUMENTOS/'.$funcionario["Identificacion_Funcionario"], 0777, true);
}


/* GUARDA CONTACTO EMERGENCIA */
$contacto_emergencia["Identificacion_Funcionario"]=$funcionario["Identificacion_Funcionario"];
$oItem = new complex('Funcionario_Contacto_Emergencia','Identificacion_Funcionario_Contacto_Emergencia');
foreach($contacto_emergencia as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);

/* GUARDA EXPERIENCIA LABORAL */
if(is_array($experiencia)){
	foreach($experiencia as $exp){
		if($exp["Nombre_Empresa"]!=""){
			$exp["Identificacion_Funcionario"]=$funcionario["Identificacion_Funcionario"];
			$oItem = new complex('Funcionario_Experiencia_Laboral','id_Funcionario_Experiencia_Laboral');
			foreach($exp as $index=>$value) {
			    $oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	}
}

/* GUARDA REFERENCIAS PERSONALES */
if(is_array($referencias)){
	foreach($referencias as $ref){
		if($ref["Nombres"]!=""){
			$ref["Identificacion_Funcionario"]=$funcionario["Identificacion_Funcionario"];
			$oItem = new complex('Funcionario_Referencia_Personal','id_Funcionario_Referencias');
			foreach($ref as $index=>$value) {
			    $oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	}
}
$oItem = new complex('Contrato_Funcionario','Id_Contrato_Funcionario');
$contrato['Estado']="Activo";
$contrato['Identificacion_Funcionario']=$funcionario["Identificacion_Funcionario"];
$contrato['Valor']=$funcionario["Salario"];
foreach($contrato as $index=>$value) {
	$oItem->$index=$value;
}
$oItem->save();
unset($oItem);


$resultado['mensaje'] = "¡Funcionario Guardado Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>