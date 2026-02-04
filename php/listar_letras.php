<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$letras="A-B-C-D-E-F-G-H-I-J-K-L-M-N-O-P-Q-R-S-T-U-V-W-X-Y-Z";

$letras=explode("-",$letras);
for ($i=0; $i < count($letras); $i++) { 
    $query='SELECT count(*) as Total FROM Producto WHERE `Codigo_Barras` IS NOT NULL AND Id_Categoria=7 AND SUBSTRING(Nombre_Comercial, 1, 1)="'.$letras[$i].'"';
           
    $oCon= new consulta();
    $oCon->setQuery($query);
    //$oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    echo $resultado['Total']."  letra--->".$letras[$i]."<br>";

}

?>