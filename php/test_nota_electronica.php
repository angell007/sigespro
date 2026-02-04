<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
include_once('../class/class.nota_credito_electronica.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$reso = '24';
$facts = GetNotas();
 // TIPO + ID


if($reso){
    foreach($facts as $nota){
        $fe = new NotaCreditoElectronica($nota['Tipo'],$nota['Id'],$reso);
        $datos = $fe->GenerarNota();
        //echo $nota["Codigo"]."<br>";
        echo json_encode($datos);
        //sleep("5");
    }
    
}

function GetNotas(){
    
    
    
    $query = ' SELECT N.* 
            FROM(
                     (SELECT Id_Nota_Credito AS Id , "Nota_Credito" AS Tipo, Codigo, Fecha 
                     FROM Nota_Credito 
                     WHERE DATE(Fecha) >= "2025-03-01" AND Procesada IS NULL
                     )
                     
                     UNION  (
                        SELECT Id_Nota_Credito_Global AS Id , "Nota_Credito_Global" AS Tipo, Codigo, Fecha  
                        FROM Nota_Credito_Global 
                        WHERE DATE(Fecha) >= "2025-03-01" AND Procesada IS NULL
                    )
                ) N
                  ORDER BY N.Codigo
                  #LIMIT 1
            #LIMIT 0,1000 
          
            # DESC
        ';
    
    //echo $query; exit;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);

    return $lista;
}
?>