<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT ARC.Id_Acta_Recepcion, ARC.Codigo, ARC.Fecha_Creacion, F.Imagen, B.Nombre as Bodega, OCN.Codigo as Codigo_Compra_N, 
(CASE 
        WHEN ARC.Tipo = "Nacional" THEN ARC.Id_Orden_Compra_Nacional
        ELSE ARC.Id_Orden_Compra_Internacional
END) AS Id_Orden_Compra,
( SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = ARC.Id_Acta_Recepcion ) as Facturas,
ARC.Tipo, ARC.Estado
FROM Acta_Recepcion ARC 
LEFT JOIN Funcionario F
ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
LEFT JOIN Orden_Compra_Nacional OCN
ON OCN.Id_Orden_Compra_Nacional = ARC.Id_Orden_Compra_Nacional
LEFT JOIN Bodega B
ON B.Id_Bodega = ARC.Id_Bodega
WHERE ARC.Estado = "Pendiente"
ORDER BY ARC.Fecha_Creacion DESC, ARC.Codigo DESC';
     
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$actarecepcion= $oCon->getData();
unset($oCon);
          


echo json_encode($actarecepcion);


?>