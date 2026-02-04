<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

function MesString($mes_index){
    global $meses;
    return  $meses[($mes_index-1)];
}


$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

$condicion = '';


if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    $condicion .= " WHERE N.Identificacion_Funcionario = ".$_REQUEST['funcionario'];
}


$query = 'SELECT N.* 
FROM Nomina_Funcionario N '.$condicion.' ORDER BY N.Fecha DESC';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$nomina1 = $oCon->getData();
unset($oCon);

$i=-1;
foreach ($nomina1 as $value) {$i++;
    $fecha=explode(";",$value['Periodo_Pago']);
    $mes=explode("-",$fecha[0]);
    $mes1=MesString($mes[1]);
    $nomina1[$i]['Nomina']=$fecha[1]." Quincena de ".$mes1." del ".$mes[0];
   
}

echo json_encode($nomina1);


?>

