<?php

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');
require_once('../class/class.configuracion.php');
include_once('../class/class.consulta.php');
include_once('../class/class.complex.php');
//include_once('../class/class.contabilizar.php');
//include_once('./notas_credito_nuevo/helper_consecutivo.php');


$notas= getComprobantes();

echo "<table><tr><td>Comprobante</td><td>Estado</td></tr>";

foreach($notas as $datos){
    
        $mov=getMovimientos($datos['Codigo']);
        /*
        $datos['Id_Registro']=$datos['Id_Nota_Credito_Global'];
        $datos['Tipo_Factura']= str_replace('_'," ",$datos['Tipo_Factura']);
        $datos['Nit'] = $datos['Id_Cliente'];
        $contabilizar = new Contabilizar();
        $contabilizar->CrearMovimientoContable('Nota Credito Global',$datos);
        unset($contabilizar); 
        */
        if(count($mov)==0){
           echo "<tr><td>".$datos['Codigo']."</td><td></td></tr>"; 
        }
        
}
echo "</table>";

function getComprobantes(){
    $query='SELECT * FROM Comprobante WHERE Estado = "Activa"';   //Fecha_Comprobante LIKE "%2019%" AND 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $fras= $oCon->getData();
    unset($oCon);
    
    return $fras;
}
function getMovimientos($cod){
    $query='SELECT Numero_Comprobante FROM Movimiento_Contable WHERE Numero_Comprobante="'.$cod.'" LIMIT 1';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $fras= $oCon->getData();
    unset($oCon);
    
    return $fras;
}