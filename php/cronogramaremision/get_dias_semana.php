<?
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$semana = ( isset( $_REQUEST['semana'] ) ? $_REQUEST['semana'] : '' );
	$http_response = new HttpResponse();

$semana_anio = date('W');
$semana_mes = date('W', strtotime('Y-m'));

$modulo = $semana_mes % 2 == 0 ? 2 : 1;

if($semana != $modulo)
{
  $semana_anio += 1;
}

$dias = ["Lunes","Martes","Miercoles","Jueves","Viernes","Sabado"];
$resultado = [];


for($i=0; $i<6; $i++){
  $resultado[] = $dias[$i] . " " . getFecha($semana_anio,$i);
}

echo json_encode($resultado);

function getFecha($semana_anio,$i) {
	return date('d/m/Y', strtotime('01/01 +' . ($semana_anio-2) . ' weeks first monday +' . $i . ' day'));
}
?>