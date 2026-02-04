<?php
 header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$condicion = SetCondiciones();

$query='SELECT D.Codigo, (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente, PD.Id_Dispensacion, CONCAT_WS(" ",PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido )  as Paciente, PD.Id_Producto  FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion INNER JOIN Paciente PA ON D.Numero_Documento=PA.Id_Paciente WHERE PD.Cantidad_Formulada> PD.Cantidad_Entregada AND D.Estado_Dispensacion!="Anulada" AND  D.Estado_Facturacion="Sin Facturar" '.$condicion ;


$oCon= new consulta();     
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);
  



echo json_encode($productos);

function SetCondiciones(){
    $condicion = '';
    if (isset($_REQUEST['Id_Producto']) && $_REQUEST['Id_Producto'] != "") {       
            $condicion .= " AND  PD.Id_Producto=".$_REQUEST['Id_Producto']."";
    }
    if (isset($_REQUEST['Punto']) && $_REQUEST['Punto'] != "") {        
            $condicion .= " AND  D.Id_Punto_Dispensacion='".$_REQUEST['Punto']."'";
    
    }
    if (isset($_REQUEST['Id_Dispensacion']) && $_REQUEST['Id_Dispensacion'] != "") {        
            $condicion .= " AND  PD.Id_Dispensacion!=".$_REQUEST['Id_Dispensacion']."";
    
    }
    if (isset($_REQUEST['Pac']) && $_REQUEST['Pac'] != "") {        
            $condicion .= " AND  CONCAT_WS(' ',PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido )LIKE '%".$_REQUEST['Pac']."%'";
    
    }
    if (isset($_REQUEST['Dis']) && $_REQUEST['Dis'] != "") {        
            $condicion .= " AND  D.Codigo LIKE '%".$_REQUEST['Dis']."%'";
    
    }


    return $condicion;
}



?>