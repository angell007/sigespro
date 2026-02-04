<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );

/*$oLista = new Lista("Turnero");
$oLista->setRestrict("Fecha","=",date("Y-m-d"));
$oLista->setRestrict("Id_Turneros","=",$punto);
$oLista->setRestrict("Estado","=","Atencion");
$oLista->setOrder("Fecha","ASC");
$turnos = $oLista->getList();
unset($oLista);*/

$query = 'SELECT   T.*
FROM Turnero T 
WHERE Fecha = "'.date("Y-m-d").'" AND Id_Turneros = '.$punto.' AND Estado = "Atencion" ORDER BY Fecha ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$turnos = $oCon->getData();
unset($oCon);

$i=-1;
foreach($turnos as $turno){ $i++;
    $turnos[$i]["Hora_Turno"]=date("h:ia",strtotime($turno["Hora_Turno"]));
    $turnos[$i]["Tiempo_Espera"]="8 Minutos";
}

$final["Turnos"]=$turnos;
$final["Cantidad"]=count($turnos);
echo json_encode($final);
?>