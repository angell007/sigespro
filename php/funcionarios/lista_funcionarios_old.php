<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT F.*, 
                 CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, 
                 C.Nombre as Cargo , 
                 D.Nombre as Dependencia , 
                 G.Nombre as Grupo 
          FROM Funcionario F 
          INNER JOIN Cargo C 
            on F.Id_Cargo=C.Id_Cargo 
          INNER JOIN Dependencia D 
            on D.Id_Dependencia = F.Id_Dependencia 
          INNER JOIN Grupo G 
            on G.Id_Grupo = F.Id_Grupo
          ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);

$i=-1;
foreach($funcionarios as $fun){ $i++;
    $query2 = 'SELECT P.Nombre as Nombre_Punto
    FROM Funcionario_Punto FP
    INNER JOIN Punto_Dispensacion P
    on FP.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
    WHERE FP.Identificacion_Funcionario = '.$fun["Identificacion_Funcionario"];
   
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query2);
    $puntos2 = $oCon->getData();
    $puntos="";
    foreach($puntos2 as $punto){
        $puntos.=$punto["Nombre_Punto"].", ";
    }
    $puntos=trim(trim($puntos),",");
    //echo $puntos;
    unset($oCon);

    $funcionarios[$i]["Nombre_Punto"]=$puntos;
}


echo json_encode($funcionarios);

?>
