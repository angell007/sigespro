<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

function diff($date1, $date2){
       if (!is_integer($date1)) $date1 = strtotime($date1);
       if (!is_integer($date2)) $date2 = strtotime($date2);  
       return floor(abs($date1 - $date2) / 60 / 60 / 24);
}


$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT *, DATE_FORMAT(Fecha,"%d de %M del %Y") as Fecha_Solicitud
FROM Cesantia WHERE Identificacion_Funcionario ='.$funcionario.' ORDER BY Fecha DESC'; 

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);


$hoy= date("Y");
$i=-1;
foreach($datos as $dat){ $i++;
    $pos = strpos($dat["Fecha"], $hoy);
    if($pos!==false){
        $datos[$i]["Link"]="Si";
    }else{
         $datos[$i]["Link"]="No";
    }
}

echo json_encode($datos);

?>