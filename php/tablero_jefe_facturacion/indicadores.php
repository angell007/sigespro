<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$func = ( isset( $_REQUEST['func'] ) ? $_REQUEST['func'] : '' );

$condicion = "";
$condicion2 = "";
$condicion3 = "";

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $condicion2 .= " AND DATE(Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $condicion3 .= " AND DATE(Fecha_Facturado) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$query = 'SELECT COUNT(*) AS Total, 
(SELECT Count(*) FROM Dispensacion WHERE Facturador_Asignado ='.$func.' AND Estado_Facturacion = "Facturada" '.$condicion3.') as Facturadas, 
(SELECT Count(*) FROM Dispensacion WHERE Facturador_Asignado ='.$func.' AND Estado_Facturacion != "Facturada" '.$condicion.') as Pendiente, 
(SELECT COUNT(*) FROM Factura  WHERE Id_Funcionario='.$func.' AND Tipo="Homologo" '.$condicion2.') as Homologo, 
(SELECT COUNT(*) FROM Factura  WHERE Id_Funcionario='.$func.' AND Tipo="Factura" '.$condicion2.') as Facturas
          FROM Dispensacion D
          WHERE D.Id_Tipo_Servicio!=7 AND D.Facturador_Asignado='.$func . $condicion;



$oCon= new consulta();

$oCon->setQuery($query);
$indicadores = $oCon->getData();
unset($oCon);

if ($indicadores['Total'] != 0) {
    $indicadores['Cumplimiento'] = number_format(($indicadores['Facturadas'] * 100) / $indicadores['Total'],0,".","");
} else {
    $indicadores['Cumplimiento'] = 0;
}




echo json_encode($indicadores);
?>