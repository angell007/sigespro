<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT id,Psede FROM Positiva_Data WHERE Psede like "%PRO-H SA. - %"'; 
        
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$sede = $oCon->getData();

unset($oCon);

foreach ($sede as $se)
{   
    $str = str_replace('PRO-H SA. - ',' ',$se["Psede"]);

    $query = "UPDATE Positiva_Data SET Psede = '$str'  WHERE id = $se[id] ";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon); 
       
}


?>