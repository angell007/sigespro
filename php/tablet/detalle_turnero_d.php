<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$turnero = ( isset( $_REQUEST['turnero'] ) ? $_REQUEST['turnero'] : '' );

$query = "SELECT * FROM Turneros WHERE Id_Turneros =".$turnero;

$oCon= new consulta();
$oCon->setQuery($query);
$detalle = $oCon->getData();
unset($oCon);

$query="SELECT *, '0' as Seleccionado FROM Prioridad_Turnero ";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$prioridad = $oCon->getData();
unset($oCon);

$punto_turnero = getPuntoTurnero($turnero);

/* $query="SELECT S.Nombre,'0' as Seleccionado,
(CASE 
WHEN S.Autorizacion='Si' THEN 'CON AUTORIZACION'
ELSE 'SIN AUTORIZACION'
END ) as Texto, S.Autorizacion, S.Nombre as Tipo
 FROM Servicio_Turnero ST INNER JOIN Servicio S ON ST.Id_Servicio=S.Id_Servicio  WHERE Id_Turnero=$turnero"; */

 $query="SELECT S.Nombre,'0' as Seleccionado, 
 (
     CASE ST.Id_Servicio
     WHEN 1 THEN 'No'
     ELSE 
        'Si'
     END
 ) AS Autorizacion, S.Nombre as Tipo
 FROM Servicio_Turnero ST INNER JOIN Servicio S ON ST.Id_Servicio=S.Id_Servicio  WHERE Id_Turnero=$turnero";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$servicio = $oCon->getData();
unset($oCon);

$servicio = validarPosEvento($servicio, $punto_turnero);
$servicio = addTextAutorizacion($servicio);

$resultado['Detalle']=$detalle;
$resultado['Prioridad']=$prioridad;
$resultado['Servicios']=$servicio;


echo json_encode($resultado);

function getPuntoTurnero($turnero) {
    $query = "SELECT Id_Punto_Dispensacion FROM Punto_Turnero WHERE Id_Turneros = $turnero";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $punto = $oCon->getData();
    unset($oCon);

    return $punto['Id_Punto_Dispensacion'];
}

function validarPosEvento($servicios, $punto) {
    $query = "SELECT Id_Tipo_Servicio FROM Tipo_Servicio_Punto_Dispensacion WHERE Id_Tipo_Servicio = 9 AND Id_Punto_Dispensacion = $punto";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    if ($resultado) {
        $data = [
            "Nombre" => "Pos",
            "Seleccionado" => "0",
            "Autorizacion" => "Si",
            "Tipo" => "Pos"
        ];
        array_push($servicios, $data);
    }
    
    return $servicios;
    
}

function addTextAutorizacion($servicios) {
    foreach ($servicios as $i => $value) {
        if ($value['Autorizacion'] == 'Si') {
            $servicios[$i]['Texto'] = "CON AUTORIZACIÓN";
        } else {
            $servicios[$i]['Texto'] = "SIN AUTORIZACIÓN";
        }
    }

    return $servicios;
}


?>