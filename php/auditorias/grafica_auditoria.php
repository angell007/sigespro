<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT CONCAT(MONTHNAME(A.Fecha_Preauditoria), " - ",YEAR(A.Fecha_Preauditoria) ) as Mes,  
       C.Estado, 
       SUM(case when C.Estado = A.Estado then 1 else 0 end) as Cantidad, (MONTHNAME(A.Fecha_Preauditoria)) as meses 
FROM Auditoria A
CROSS JOIN ( SELECT DISTINCT Estado FROM Auditoria) C
WHERE A.Fecha_Preauditoria BETWEEN ADDDATE(CURDATE(),INTERVAL -6 MONTH) AND A.Fecha_Preauditoria
GROUP BY MONTH(A.Fecha_Preauditoria), YEAR(A.Fecha_Preauditoria), C.Estado
ORDER BY A.Fecha_Preauditoria' ;
            
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$graf2 = $oCon->getData();
unset($oCon);

echo json_encode($graf2);


?>