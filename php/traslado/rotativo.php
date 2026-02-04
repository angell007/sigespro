<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$punto_dispensacion = ( isset( $_REQUEST['punto_dispensacion'] ) ? $_REQUEST['punto_dispensacion'] : '' );
$fecha_inicio= ( isset( $_REQUEST['fecha_inicio'] ) ? $_REQUEST['fecha_inicio'] : '' );
$fecha_fin= ( isset( $_REQUEST['fecha_fin'] ) ? $_REQUEST['fecha_fin'] : '' );
$bodega = ( isset( $_REQUEST['bodega'] ) ? $_REQUEST['bodega'] : '' );


$query = 'SELECT  PRD.Id_Producto, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ", PRD.Cantidad," ", PRD.Unidad_Medida, " ") as Nombre_Producto , SUM( PR.Cantidad_Formulada ) as Formuladas, (CEILING((((SUM(  PR.Cantidad_Formulada )*0.05)+SUM( PR.Cantidad_Formulada ))/100))*100) as Cantidad, PLG.Precio as Precio_Venta,
((CEILING((((SUM(  PR.Cantidad_Formulada )*0.05)+SUM( PR.Cantidad_Formulada ))/100))*100)*(PLG.Precio)) as Subtotal
	FROM Dispensacion D
    INNER JOIN Punto_Dispensacion PD
    ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
    INNER JOIN Producto_Dispensacion PR
    ON D.Id_Dispensacion = PR.Id_Dispensacion
    INNER JOIN Producto PRD
    ON PR.Id_Producto = PRD.Id_Producto
    INNER JOIN Producto_Lista_Ganancia PLG
    on PRD.Codigo_Cum=PLG.Cum AND PLG.Id_Lista_Ganancia=1 
    WHERE D.Id_Punto_Dispensacion ='.$punto_dispensacion.'
    AND D.Fecha_Actual BETWEEN "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 00:00:00"
    GROUP BY PR.Id_Producto';
$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());
$productos = [];
while($lista=mysql_fetch_assoc($result)){
    $productos[]=$lista;
}
@mysql_free_result($productos);
$i=-1;
foreach($productos as $producto){ $i++;
    $query2 = 'SELECT I.*, 
          CONCAT("Lote: ", I.Lote, " - Vencimiento: ", I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada)) as label, I.Id_Inventario as value
          FROM Inventario I 
          WHERE I.Id_Producto = '.$producto["Id_Producto"].'
          AND I.Id_Bodega = '.$bodega.'
          AND (I.Cantidad-I.Cantidad_Apartada) > 0
          ORDER BY I.Fecha_Vencimiento ASC';
          
         $result2 = mysql_query($query2) or die('Consulta fallida: ' . mysql_error());
         $lotes = [];
         while($lista=mysql_fetch_assoc($result2)){
         $lotes[]=$lista;
        }
        @mysql_free_result($lotes);
    
        $productos[$i]["Lotes"]=$lotes;
        $productos[$i]["Lotes_Auxiliar"]=$lotes;
        $siguiente = true;
        $Lista_Bandera=[];
        $productos[$i]["Lotes_Seleccionados"]=[];
        $productos[$i]["Lotes_Visuales"]=[];
        $cantidad = $producto["Cantidad"];
        $productos[$i]["Descuento"]=0;
        $productos[$i]["Rotativo"]=$productos[$i]["Formuladas"];
        $productos[$i]["Impuesto"]=0;
         $suma=0;
        foreach($lotes as $lote){
           $cantidadDisponoble=$lote["Cantidad"]-$lote["Cantidad_Apartada"];
            if($siguiente&&$producto["Cantidad"]<=$cantidadDisponoble){
                $item = $lote;
                $item["label"] ="Lote: ".$lote["Lote"]." - Vencimiento: ".$lote["Fecha_Vencimiento"]." - Cantidad: ".$cantidad;
                $suma=$suma+$cantidadDisponoble;
                $item["Cantidad"]=$cantidad;
                $Lista_Bandera[]=$item;
                $productos[$i]["Lotes_Seleccionados"][]=$item;
                $productos[$i]["Lotes_Visuales"][]=$item["label"];
                $siguiente=false;
            }elseif($siguiente&&$producto["Cantidad"]>$cantidadDisponoble){
                $cantidad = $cantidad - $cantidadDisponoble;
                $suma=$suma+$cantidadDisponoble;
                $Lista_Bandera[]=$lote;
                $productos[$i]["Lotes_Seleccionados"][]=$lote;
                $productos[$i]["Lotes_Visuales"][]=$lote["label"];
            }
              
        }
        $suma=$suma-$producto["Cantidad"];
        $prod["Nombre"]=$producto["Nombre_Producto"];
        $prod["Id_Producto"]=$producto["Id_Producto"];
        $prod["Lotes"]=$lotes;
        $prod["precio"]=$producto["Precio_Venta"];
        $productos[$i]["producto"]=$prod;
        $productos[$i]["Suma"]=$suma;
}
mysql_close($link);

echo json_encode($productos);

?>