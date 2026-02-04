<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
include_once('../class/class.mipres.php');
include_once('../class/class.php_mailer.php');

$queryObj = new QueryBaseDatos();




for($i=1;$i<=24363;$i++){
    $cod="FENP".$i;
    
    $query="SELECT F.* FROM Factura F Where F.Codigo = '".$cod."'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $fact = $oCon->getData();
    unset($oCon);
    
    if($fact){
        echo $cod." - SI ESTA<br>";
    }else{
        echo $cod." - NO ESTA<br>";
    }
}




?>