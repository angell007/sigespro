<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$query = " SELECT   M.Id_Movimiento_Contable, M.Numero_Comprobante, M.Documento
                FROM Movimiento_Contable M WHERE M.Numero_Comprobante = 'NOS202010013' 
                AND M.Documento LIKE 'NP%' AND Id_Registro_Modulo IS NULL";

// WHERE D.Estado_Dispensacion != 'Anulada' AND DM.Tipo_Tecnologia = 'M'

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$movs = $oCon->getData();
unset($oCon);
$x=-1;

foreach( $movs as $mov ){
    $x++;
    $query = " SELECT Id_Factura FROM Factura  WHERE Codigo = '".$mov['Documento']."'";
        
    $oCon = new consulta();
    $oCon->setQuery($query);
    $doc = $oCon->getData();
    unset($oCon);
    
    
    if($doc){
        $query = " UPDATE Movimiento_Contable SET  Id_Registro_Modulo = ".$doc['Id_Factura']."
                    WHERE Id_Movimiento_Contable = ".$mov['Id_Movimiento_Contable'];
        
        $oCon = new consulta();
        $oCon->setQuery($query);
       
        $doc = $oCon->createData();
        unset($oCon);
      
    }
    echo '<pre>';
    echo $x.' - '.$mov['Id_Movimiento_Contable'];
    echo '<pre>';
}

echo 'termnió';