<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT F.Imagen, CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, F.Identificacion_Funcionario,
(SELECT COUNT(*) FROM Remision WHERE Identificacion_Funcionario=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) AND Tipo_Origen="Bodega" AND Estado!="Anulada" ) as Remisiones
FROM Funcionario F
INNER JOIN Cargo C
ON F.Id_Cargo=C.Id_Cargo
WHERE (F.Id_Cargo=49 OR F.Id_Cargo=47)' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$remisiones = $oCon->getData();
unset($oCon);
$i=-1;
foreach ($remisiones as $value) {$i++;
   $porcentaje=($value['Remisiones']*100)/150;
   $remisiones[$i]['Porcentaje']=number_format($porcentaje,2,".","");
}

$query='SELECT F.Imagen, CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, F.Identificacion_Funcionario,
(SELECT COUNT(*) FROM Factura WHERE Id_Funcionario=F.Identificacion_Funcionario AND  MONTH(Fecha_Documento) = MONTH(NOW()) ) as Facturas
FROM Funcionario F
INNER JOIN Cargo C
ON F.Id_Cargo=C.Id_Cargo
WHERE F.Id_Cargo=17';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);

$i=-1;
foreach ($facturas as  $value) {$i++;
    $porcentaje=($value['Facturas']*100)/2400;
    $facturas[$i]['Porcentaje']=number_format($porcentaje,2,".","");
}

$query='SELECT F.Imagen, CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, F.Identificacion_Funcionario,
(SELECT COUNT(*) FROM Factura_Venta WHERE Id_Funcionario=F.Identificacion_Funcionario AND  MONTH(Fecha_Documento) = MONTH(NOW()) ) as Facturas
FROM Funcionario F
INNER JOIN Cargo C
ON F.Id_Cargo=C.Id_Cargo
WHERE F.Id_Cargo=18';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas_ventas = $oCon->getData();
unset($oCon);

$i=-1;



$resultado['Remisiones']=$remisiones;
$resultado['Facturas']=$facturas;
$resultado['Facturas_Venta']=$facturas_ventas;




echo json_encode($resultado);

?>