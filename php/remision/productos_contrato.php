<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idcontrato = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$bodega= ( isset( $_REQUEST['bodega'] ) ? $_REQUEST['bodega'] : '' );

$query = 'SELECT 
          CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,
       		PC.precio, p.Id_Producto, p.Cantidad_Presentacion
          FROM Producto p 
         INNER JOIN Producto_Contrato PC
         ON p.Codigo_Cum=PC.Cum AND PC.Id_Contrato='.$idcontrato;
         
    
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);
if(!$contrato){
    $query = 'SELECT 
          CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,
       		 p.Id_Producto, p.Cantidad_Presentacion, I.Costo as precio
       		 
          FROM Producto p
          INNER JOIN Inventario I
          ON p.Id_Producto=I.Id_Inventario';
    
}

$i=-1;
foreach($contrato as $contratos){ $i++;

    $query2 = 'SELECT I.*, 
          CONCAT("Lote: ", I.Lote, " - Vencimiento: ", I.Fecha_Vencimiento," - Cantidad: ",I.Cantidad) as label, I.Id_Inventario as value
          FROM Inventario I 
          WHERE I.Id_Producto = '.$contratos["Id_Producto"].'
          AND I.Id_Bodega = '.$bodega.'
          AND I.Cantidad > 0
          ORDER BY I.Fecha_Vencimiento ASC
          ';
         
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query2);
        $lotes = $oCon->getData();
        unset($oCon);

    $contrato[$i]["Lotes"]=$lotes;
}

echo json_encode($contrato);


?>