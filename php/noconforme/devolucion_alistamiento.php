<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query='SELECT D.Codigo, D.Id_Devolucion_Compra AS Id_Remision, "Devolucion" AS Tipo, D.Id_Bodega_Nuevo, D.Observaciones, 
            D.Estado_Alistamiento 
FROM Devolucion_Compra D
WHERE D.Id_Devolucion_Compra='.$id; 
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$devolucion= $oCon->getData();
unset($oCon);


$query='SELECT Id_Bodega_Nuevo, Nombre, Direccion, Telefono, Mapa
FROM Bodega_Nuevo
WHERE Id_Bodega_Nuevo='.$devolucion['Id_Bodega_Nuevo']; 
$oCon= new consulta();
//$oCon->setTipo('Multiple');

$oCon->setQuery($query);
$origen= $oCon->getData();
unset($oCon);



$query='SELECT IFNULL(P.Nombre,CONCAT(P.Primer_Nombre, " " , P.Segundo_Nombre) ) AS Nombre ,
            P.Direccion , P.Telefono
    FROM Proveedor P';            
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$destino= $oCon->getData();
unset($oCon);

/* 
    

if($remision['Tipo_Lista']=="Contrato"){
$oItem=new complex('Contrato','Id_Contrato',$remision['Id_Lista'] );
$contrato=$oItem->getData();
$resultado['Contrato']=$contrato;
unset($oItem);
}elseif($remision['Tipo_Lista']=="Lista_Ganancia"){
$oItem=new complex('Lista_Ganancia','Id_Lista_Ganancia',$remision['Id_Lista'] );
$contrato=$oItem->getData();
$resultado['Lista']=$contrato;
unset($oItem);
} */

$resultado['Remision']=$devolucion;
$resultado['Origen']=$origen;

$resultado['Destino']=$destino;

echo json_encode($resultado);

?>