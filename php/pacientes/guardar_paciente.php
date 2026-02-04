<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$nit = ( isset( $_REQUEST['nit_eps'] ) ? $_REQUEST['nit_eps'] : '' );

$datos = (array) json_decode(utf8_decode($datos));
$nit_eps = json_decode($nit);

$datos["Nit"]=$nit_eps;

if(isset($datos["editar"])&&$datos["editar"] == true){   
	$oItem = new complex('Paciente', 'Id_Paciente', $datos["Id_Paciente"],'Varchar');	
}else{	
    $oItem = new complex('Paciente', 'Id_Paciente');
}

foreach($datos as $index=>$value) {
    if($value!=''){
        $oItem->$index=$value;
    }
    
}

$id_pac = $oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se ha guardado correctamente el paciente";
$resultado['tipo'] = "success";
$resultado['titulo'] = "Paciente registrado";

echo json_encode($resultado);

function limpiar($String)
{
$String = str_replace("  "," ",$String);
$String = str_replace("á","a",$String);
$String = str_replace("Á","A",$String);
$String = str_replace("Í","I",$String);
$String = str_replace("í","i",$String);
$String = str_replace("é","e",$String);
$String = str_replace("É","E",$String);
$String = str_replace("ó","o",$String);
$String = str_replace("Ó","O",$String);
$String = str_replace("ú","u",$String);
$String = str_replace("Ú","U",$String);
$String = str_replace("ç","c",$String);
$String = str_replace("Ç","C",$String);
$String = str_replace("ñ","n",$String);
$String = str_replace("Ñ","N",$String);
$String = str_replace("Ý","Y",$String);
$String = str_replace("ý","y",$String);
$String = str_replace("'","",$String);
$String = str_replace('"',"",$String);
$String = str_replace('\'',"",$String);
$String = str_replace('º',"",$String);
$String = str_replace('\n'," ",$String);
$String = str_replace('\t'," ",$String);
$String = str_replace('\r'," ",$String);
str_replace('?',"",$String);
$String = utf8_encode(strtoupper(trim($String)));
return $String;
}

function camposLimpiar($str) {
    $campos = [
        "Primer_Apellido",
        "Segundo_Apellido",
        "Primer_Nombre",
        "Segundo_Nombre"
    ];

    return array_search($str,$campos);
}
?>