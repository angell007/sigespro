<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$idlista = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$idBodega = ( isset( $_REQUEST['IdBodega'] ) ? $_REQUEST['IdBodega'] : '' );


$query = 'SELECT 
          CONCAT( p.Principio_Activo, " ", p.Presentacion, " ", p.Concentracion, " (", p.Nombre_Comercial,") ", p.Cantidad," ", p.Unidad_Medida, " " ) as Nombre,p.Id_Producto, PLG.Precio as precio, p.Cantidad_Presentacion
       	  FROM Producto_Lista_Ganancia PLG
          INNER JOIN Producto p          
         ON PLG.Cum=p.Codigo_Cum AND PLG.Id_Lista_Ganancia='.$idlista.'
         GROUP BY PLG.Cum';
       	  
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);

foreach($contrato as $contratos){ $i++;

    $query2 = 'SELECT I.*, 
          CONCAT("Lote: ", I.Lote, " - Vencimiento: ", I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad - I.Cantidad_Apartada)) as label, I.Id_Inventario as value
          FROM Inventario I 
          WHERE I.Id_Producto = '.$contratos["Id_Producto"].'
          AND I.Id_Bodega = '.$idBodega.'
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

