<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.consulta.php');

$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );

/*$oLista = new Lista("Turnero");
$oLista->setRestrict("Fecha","=",date("Y-m-d"));
$oLista->setRestrict("Id_Turneros","=",$punto);
$oLista->setRestrict("Estado","=","Espera");
$oLista->setOrder("Hora_Turno","ASC");
$turnos = $oLista->getList();
unset($oLista);*/

$query = '
SELECT
    *
FROM Turnero
WHERE
    Fecha = "'.date("Y-m-d")
    .'" AND Id_Turneros = '.$punto
    .' AND Estado = "Espera" 
    ORDER BY Hora_Turno ASC';

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

/*$oLista = new Lista("Turnero");
$oLista->setRestrict("Fecha","=",date("Y-m-d"));
$oLista->setRestrict("Id_Turneros","=",$punto);
$oLista->setRestrict("Estado","=","Auditoria");
$oLista->setOrder("Hora_Turno","ASC");
$turnos2 = $oLista->getList();
unset($oLista);*/
$query = '
SELECT
    *
FROM Turnero
WHERE
    Fecha = "'.date("Y-m-d")
    .'" AND Id_Turneros = '.$punto
    .' AND Estado = "Auditoria" 
    ORDER BY Hora_Turno ASC ';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$turnos2 = $oCon->getData();
unset($oCon);

$i=-1;
foreach($turnos2 as $turno){ $i++;
    $turnos2[$i]["Hora_Turno"]=date("h:ia",strtotime($turno["Hora_Turno"]));
    $turnos2[$i]["Tiempo_Espera"]="8 Minutos";
}

$total_turnos = count($turnos);

if ($total_turnos > 0 && $total_turnos < 5) {
    $diferencia = 5 - $total_turnos;

    for ($i=0; $i < $diferencia; $i++) { 
        $push = ["Persona" => "LIBRE", "Hora_Turno" => "", "Tiempo_Espera" => "8 Minutos"];
        $turnos[] = $push;
    }
}

$total_turnos2 = count($turnos2);

if ($total_turnos2 > 0 && $total_turnos2 < 5) {
    $diferencia = 5 - $total_turnos2;

    for ($i=0; $i < $diferencia; $i++) { 
        $push = ["Persona" => "LIBRE", "Hora_Turno" => "", "Tiempo_Espera" => "8 Minutos"];
        $turnos2[] = $push;
    }
}


$final["Turnos2"]=$turnos2;
$final["Cantidad2"]=$total_turnos2;
$final["Turnos"]=$turnos;
$final["Cantidad"]=$total_turnos;
echo json_encode($final);
?>