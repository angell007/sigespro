<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT   C.Tipo, SUM(case when C.Tipo = R.Tipo then 1 else 0 end) as Cantidad
FROM Remision R
CROSS JOIN ( SELECT DISTINCT Tipo FROM Remision) C

GROUP BY C.Tipo' ;
            
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$tipo = $oCon->getData();
unset($oCon);

$query2='SELECT COUNT( R.Estado) as cantidad , R.Estado
From Remision R
WHERE R.Estado="Anulada"
GROUP By R.Estado';
$oCon= new consulta();
$oCon->setQuery($query2);
$anuladas = $oCon->getData();
unset($oCon);

$query3='SELECT
(SELECT COUNT(*)
From Remision R
WHERE R.Id_Factura IS NOT NULL AND R.Tipo="Cliente") AS Facturadas,
(SELECT COUNT(*)
From Remision R
WHERE R.Id_Factura IS NULL AND R.Tipo="Cliente") AS No_Facturadas';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$facturadas= $oCon->getData();
unset($oCon);

$query4='SELECT COUNT(NC.Tipo) as Cantidad FROM No_Conforme NC WHERE NC.Tipo="Remision"';
$oCon= new consulta();
$oCon->setQuery($query4);
$noconforme= $oCon->getData();
unset($oCon);

if($anuladas=='' || count($anuladas)==0){
	$anuladas['cantidad']=0;
	$anuladas['Estado']="Anulada";
}

$resultado['Tipo']=$tipo;
$resultado['Anuladas']=$anuladas;
$resultado['Tipo_Facturacion']=$facturadas;
$resultado['No_Conforme']=$noconforme;

echo json_encode($resultado);