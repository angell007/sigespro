<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT 
       C.Estado, 
       SUM(case when C.Estado = R.Estado then 1 else 0 end) as Cantidad 
FROM Remision R
CROSS JOIN ( SELECT DISTINCT Estado FROM Remision) C
WHERE R.Fecha BETWEEN ADDDATE(CURDATE(),INTERVAL -6 MONTH) AND R.Fecha
GROUP BY C.Estado
ORDER BY R.Fecha' ;
            

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$graf2 = $oCon->getData();
unset($oCon);

echo json_encode($graf2);
?>