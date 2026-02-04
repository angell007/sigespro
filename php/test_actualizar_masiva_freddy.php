<?php

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

include_once('../class/class.contabilizar.php');

include_once('../class/class.facturacion_electronica.php');




$facturas = getFacturas();

$i=0;

foreach($facturas as $fact){ $i++;
    $concepto = substr(Quitar_Espacios($fact["Concepto"]),0,42);
    
    $factura_real = getDetalle($fact,$concepto);
    
    //if(count($factura_real)!=1){
        echo $i.".- ".$concepto." ======== ".count($factura_real)." - ".$factura_real[0]["Factura"]." - ".$factura_real[0]["Valor"]." - ".$factura_real[0]["Concepto"]."<br><br>";
        ActualizaLinea($fact,$factura_real[0]["Id_Masiva_Freddy3"]);
    //}
}

function ActualizaLinea($fact,$data){
    
    $query="UPDATE Masiva_Freddy SET Original='".$data."' WHERE ID=".$fact["ID"];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
				
}


function getDetalle($fact,$concepto){
    $query = "SELECT MF.Id_Masiva_Freddy3, MF.Factura, MF.Cum, F.Id_Dispensacion, MF.Valor, CONCAT_WS(' ', 'VALOR DEJADO DE COBRAR FACTURA',MF.Factura,'CORRESPONDIENTE A MEDICAMENTO',MF.Producto, 'USUARIO', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido, 'ID',D.Numero_Documento) AS Concepto


FROM Masiva_Freddy3 MF
LEFT JOIN Factura F ON F.Codigo = MF.Factura
INNER JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion 
INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento 

WHERE MF.Concepto LIKE '%".$concepto."%' AND MF.Valor = ".$fact["Subtotal"]."
    ";




    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);
    
    return $facturas;
    
}

function getDetalle2($fact,$concepto){
    $query = "SELECT FA.Codigo, FA.Estado_Factura

FROM Descripcion_Factura_Administrativa DFA
INNER JOIN Factura_Administrativa FA ON DFA.Id_Factura_Administrativa=FA.Id_Factura_Administrativa
WHERE DFA.Descripcion LIKE '%".$concepto."%' AND DFA.Subtotal = ".$fact["Subtotal"]." AND FA.Estado_Factura='Pagada'
    ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);
    
    return $facturas;
    
}


function getFacturas(){
    $query = "SELECT *
              FROM Masiva_Freddy
              WHERE Glosa = 'Si'
    ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);
    
    return $facturas;
}

function Quitar_Espacios($cadena)
{
    return preg_replace(['/\s+/','/^\s|\s$/'],[' ',''], $cadena);
}

?>