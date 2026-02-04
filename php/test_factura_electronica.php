<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php'); 
//include_once('../class/class.facturacion_electronica_estructura.php');
include_once('../class/class.facturacion_electronica.php');


$fecha_inicio = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : "2025-03-05");
$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : "Factura");
$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : 42);
$facts = GetFacturas($tipo, $reso, $fecha_inicio);

// 19 Administrativa
// 20 Capita
// 21 NP
// 22 EP
// 23 Ventas

/*
$fe = new FacturaElectronica("Factura_Administrativa",180, 39); 
$datos = $fe->GenerarFactura();
echo json_encode($datos);
exit;
*/

$resp['factura'] = $facts;
if(count($facts)==0){
        http_response_code(406);
}
 //echo json_encode($resp);exit;
foreach ($facts as $fac) { 
     //echo json_encode(contarCodigo($tipo, $fac['Codigo']));
    if(contarCodigo($tipo, $fac['Codigo']) =='1'){ 
    
        $fe = new FacturaElectronica($tipo, $fac["Id_Factura"], $fac["Id_Resolucion"]);
        $datos = $fe->GenerarFactura();
        $resp['respuesta'] = $datos;
        
}
    else{
        echo "error"; 
        //http_response_code(406);
    }
}

echo json_encode($resp);


function GetFacturas($tipo, $res, $fecha_inicio)
{
       $cond_res = (isset($res) ? "AND Id_Resolucion IN (" . $res . ")" : '');
    $cond_factura = (isset($_REQUEST['fmin']) ? "AND Id_$tipo >  $_REQUEST[fmin]" : '');
    $query = "SELECT Id_" . $tipo . " as Id_Factura, Codigo, Id_Resolucion FROM " . $tipo . " WHERE 
     (Procesada = 'false' OR Procesada IS NULL)  $cond_res
     $cond_factura
    AND Fecha_Documento >= '" . $fecha_inicio . "'
   order by Fecha_Documento ASC /*LIMIT 0,1  AND Cufe IS NULL*/  "; 

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);

    return $lista;
}
function contarCodigo($tipo, $codigo){
     $query = "SELECT COUNT(Id_$tipo) as Total 
     FROM $tipo 
     WHERE Codigo LIKE '$codigo'";

    // echo $query; exit;
    $oCon = new consulta();
    $oCon->setQuery($query);
    return $oCon->getData()['Total'];
    
}