<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('../class/NumeroALetra.php');


include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
require_once('../class/class.configuracion.php');
include_once('../class/class.mipres.php');

$mipres= new Mipres();
$queryObj = new QueryBaseDatos();

$query = 'SELECT *
		    FROM A_Entrega_Mipres_2025 E
		    WHERE E.Estado=0';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

$codigo_sede=GetCodigoSede();
$nit=GetNitProh();

// E.Estado=1  AND E.CRUCE_JURIDICA="JURIDICO" AND 

$i=0;

echo "<table border='1' cellspacing='1' cellpadding='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>ID Programacion</td><td>Id Entrega</td><td>Id Entrega Efectiva</td><td>Cantidad Dir</td><td>Estado</td></tr>";
foreach($productos as $pm){ $i++;

    $dishoy=$mipres->GetDireccionamientoPorPrescripcion($pm["NoPrescripcion"]);
    usort($dishoy,'OrderByNumeroEntrega');
    $j=-1;
    $pro ='';
    
    
    if(count($dishoy)>=1){ $pro = "CONSULTADO"; }
    echo "<tr><td>".$i."</td><td>".$pm["NoPrescripcion"]."</td><td>".(INT)$pm['ID']."</td><td></td><td></td><td></td><td>".count($dishoy)."</td><td>".$pro."</td></tr>";
    foreach($dishoy as $d){ $j++;
        if($d["ID"]==$pm["ID"]){
            $oItem=new complex('A_Entrega_Mipres_2025','Id_Entrega_Mipres',$pm['Id_Entrega_Mipres']);
            foreach($d as $index=>$value) {
                $oItem->$index=$value;
            }
            $oItem->Estado=1;
            $oItem->save();
            unset($oItem);
            //var_dump($dishoy); 
            //exit;
        }
    }
    
}

echo "</table>";



function GetCodigoSede(){
    global $queryObj;
    $query = 'SELECT Codigo_Sede				
        FROM Configuracion
        WHERE Id_Configuracion=1';
    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');
    return $dato['Codigo_Sede'];
}
function GetNitProh(){
    global $queryObj;
    $query = 'SELECT NIT				
            FROM Configuracion
            WHERE Id_Configuracion=1';
    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');

    $n=explode('-',$dato['NIT']);
    $nit=$n[0];
    $nit=str_replace('.','',$nit);
    return $nit;
}