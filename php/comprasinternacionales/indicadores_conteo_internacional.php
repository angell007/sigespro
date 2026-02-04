<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT DISTINCT 
            (SELECT COUNT(Id_Orden_Compra_Internacional) FROM Orden_Compra_Internacional) AS Total_Ordenes, 
            (SELECT COUNT(Id_Orden_Compra_Internacional) FROM Orden_Compra_Internacional where Estado = "Anulada") AS Anulados , 
            (SELECT COUNT(Id_Orden_Compra_Internacional) FROM Orden_Compra_Internacional where Estado = "No Conforme" ) AS No_Conformes , 
            (SELECT SUM(Subtotal) FROM Producto_Orden_Compra_Internacional) AS Total_Compras 
          FROM Orden_Compra_Internacional' ;

$oCon= new consulta();
$oCon->setQuery($query);
$conteos = $oCon->getData();
unset($oCon);
echo json_encode($conteos);

?>

