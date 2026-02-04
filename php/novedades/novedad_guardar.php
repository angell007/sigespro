<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once('../../vendor/autoload.php');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');

use Carbon\Carbon as Carbon;

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = json_decode($datos,true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Datos invalidos.', 'tipo' => 'error']);
    exit;
}

if (!isset($datos['Fecha_Inicio'], $datos['Fecha_Fin'], $datos['Id_Tipo_Novedad'])) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan campos requeridos.', 'tipo' => 'error']);
    exit;
}

if (isset($datos['Funcionario']) && !isset($datos['Identificacion_Funcionario'])) {
    $datos['Identificacion_Funcionario'] = $datos['Funcionario'];
}

if (class_exists('Carbon\\Carbon')) {
    $datI = new Carbon($datos["Fecha_Inicio"]);
    $datF = new Carbon($datos["Fecha_Fin"]);
    $diff = $datF->diffInDays($datI);
    $year = $datI->year;
    $useCarbon = true;
} else {
    $datI = new DateTime($datos["Fecha_Inicio"]);
    $datF = new DateTime($datos["Fecha_Fin"]);
    $diff = $datF->diff($datI)->days;
    $year = (int) $datI->format('Y');
    $useCarbon = false;
}


$libres = 0;
$oItem = new complex('Festivos_Anio',"Anio",$year);
$festivos = $oItem->getData();

for($x=0 ; $x <= $diff ; $x++  ){
    $currentDate = (clone $datI);
    if ($useCarbon) {
        $currentDate = $currentDate->addDays($x);
        $isSunday = ($currentDate->locale('es')->dayName == 'domingo');
        $dateString = $currentDate->toDateString();
    } else {
        $currentDate = $currentDate->modify("+$x days");
        $isSunday = ($currentDate->format('w') === '0');
        $dateString = $currentDate->format('Y-m-d');
    }

    if ($isSunday) {
        $libres ++;
    } else if (isset($festivos['Festivos']) && strpos($festivos['Festivos'], $dateString) !== false) {
        $libres ++;
    }
}
 

if(isset($datos["id"]) && $datos["id"]!=""){
    $oItem = new complex('Novedad',"Id_Novedad",$datos["id"]);
}else{
    $oItem = new complex('Novedad',"Id_Novedad");
}

 if( $datos['Id_Tipo_Novedad'] == 1){
     $oItem->Vacaciones_Tomadas = ($diff - $libres);
     $oItem->Periodo = ($diff - $libres);
 } 
    $datos['Id_Tipo_Novedad'] = explode("-",$datos['Id_Tipo_Novedad'])[0]; // Sacar el tipo de novedad
 
    foreach($datos as $index=>$value){
        $oItem->$index=$value;
    }
    
 
$oItem->save();
$id_novedad = $oItem->getId();
unset($oItem);

if($id_novedad != ""){
    $resultado['mensaje'] = "Se ha guardado correctamente la Vacante";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);



?>
