<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$fecha_inicial = isset($_REQUEST['fini']) ? $_REQUEST['fini'] : false;
$fecha_final = isset($_REQUEST['ffin']) ? $_REQUEST['ffin'] : false;
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

if (!$fecha_inicial || !$fecha_final || $id === false || $id === '' || $id === 'undefined' || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametros invalidos.']);
    exit;
}

$fecha_final = explode('T', $fecha_final);
$fecha_final = $fecha_final[0];

$fecha_inicial = explode('T', $fecha_inicial);
$fecha_inicial = $fecha_inicial[0];

$condicion = " WHERE  (Fecha_Inicio BETWEEN  '$fecha_inicial' AND  '$fecha_final'   OR Fecha_Fin  BETWEEN '$fecha_inicial' AND  '$fecha_final'  ) AND N.Identificacion_Funcionario =" . $id;


$query = 'SELECT N.*, T.Novedad 
FROM Novedad N 
INNER JOIN Tipo_Novedad T ON N.Id_Tipo_Novedad=T.Id_Tipo_Novedad'. $condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$novedad= $oCon->getData();
unset($oCon);

if($novedad['Id_Novedad']){
    $resultado=" El funcionario tiene esta novedad registrada ".$novedad['Novedad']." y no puede tener dos novedades en la misma fecha";
}else{
    $resultado=0;
}


echo json_encode($resultado);
?>
