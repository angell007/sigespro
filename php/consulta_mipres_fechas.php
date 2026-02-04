<?php
ini_set("memory_limit","1024M");
ini_set('max_execution_time', 480);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
include_once('../class/class.mipres.php');
include_once('../class/class.php_mailer.php');

$query="SELECT *
FROM Producto_Dispensacion_Mipres PDM
WHERE PDM.IdProgramacion != 0
GROUP BY PDM.NoPrescripcion
";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos_mipres = $oCon->getData();
unset($oCon);


$mipres= new Mipres();

$i=0;
foreach($productos_mipres as $pm){ $i++;
    echo $pm["NoPrescripcion"]."<br><br>";
    $programaciones=$mipres->ConsultaProgramacion($pm["NoPrescripcion"]);
    $j=0;
    foreach($programaciones as $prog){ $j++;
        echo $i.") ---- ".$j.")  ".$prog["ID"]."<br>";
        var_dump($prog);
        echo "<br>";
        if($prog["ID"]!="E"){
            $query='UPDATE Producto_Dispensacion_Mipres SET IdProgramacion ='.$prog["IDProgramacion"].', Fecha_Programacion="'.$prog["FecProgramacion"].'"
                WHERE ID = '.$prog["ID"].'';
                
            $oCon= new consulta();
            $oCon->setQuery($query);     
            $oCon->createData();     
            unset($oCon);
            
        }
        
    }
    
}



?>