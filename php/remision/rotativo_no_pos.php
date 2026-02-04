<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once ('../../config/start.inc.php');
include_once ('../../class/class.lista.php');
include_once ('../../class/class.complex.php');
include_once ('../../class/class.consulta.php');

$punto_dispensacion = (isset($_REQUEST['punto_dispensacion']) ? $_REQUEST['punto_dispensacion'] : '');
$fecha_inicio = (isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : '');
$fecha_fin = (isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : '');
$bodega = (isset($_REQUEST['bodega']) ? $_REQUEST['bodega'] : '');



$hoy=date("Y-m-t", strtotime(date('Y-m-d')));
$nuevafecha = strtotime ( '+ 1 months' , strtotime ( $hoy) ) ;
$nuevafecha= date('Y-m-t', $nuevafecha);


  $productos=CrearQueryMedicamentos($punto_dispensacion,$fecha_inicio, $fecha_fin, $bodega);



$i = - 1;
foreach ($productos as $producto) {
    $i++;
    if ($producto["Id_Categoria"] != '') {
        //Busco los lotes de inventario de los productos
        $lotes=CrearQueryInventarioLotes($producto,$bodega,$nuevafecha);       
        if ($lotes) {            
            $siguiente = true;
            $Lista_Bandera = [];
            $productos[$i]["Lotes_Seleccionados"] = [];
            $productos[$i]["Lotes_Visuales"] = [];
            $cantidad = $producto["Cantidad"];
            if($cantidad>$producto['Cantidad_Inventario_Bodega']){
                $cantidad=$producto['Cantidad_Inventario_Bodega'];
            }
         
            if ($cantidad > 0) {
                $productos[$i]["Rotativo"] = $productos[$i]["Cantidad"] . "/" . $productos[$i]['Cantidad_Inventario'];
                $modulo = $cantidad % $producto['Cantidad_Presentacion'];
                $productos[$i]['Cantidad'] = $cantidad;
                //precio venta
                $productos[$i]["Precio_Venta"] = $lotes[0]['Precio_Venta'];
                $productos[$i]["Descuento"] = 0;
                $productos[$i]['Cantidad_Requerida'] = $cantidad;
                $productos[$i]["Impuesto"] = 0;
                $suma = 0;
                $j = - 1;
                foreach ($lotes as $lote) {
                    $j++;
                    $productos[$i]["Cantidad_Disponible"] = $lote['Cantidad_Disponible'];
                    $cantidadDisponoble = $lote["Cantidad"] - $lote["Cantidad_Apartada"] - $lote["Cantidad_Descontar"];
                    if ($cantidadDisponoble < 0) {
                        $cantidadDisponoble = 0;
                    }
                 
                    if ($siguiente && $productos[$i]["Cantidad"] <= $cantidadDisponoble) {
                        $item = $lote;
                        $item["label"] = "Lote: " . $lote["Lote"] . " - Vencimiento: " . $lote["Fecha_Vencimiento"] . " - Cantidad: " . $cantidad;
                        $cantidadseleccionada = $lote['Cantidad_Descontar'] + $cantidad;
                        $oItem = new complex("Inventario", "Id_Inventario", $lote["Id_Inventario"]);
                        $oItem->Cantidad_Seleccionada = number_format($cantidadseleccionada, 0, "", "");
                       $oItem->save();
                        unset($oCon);
                        $item['Cantidad_Seleccionada'] = $cantidad;
                        $lotes[$j]['Cantidad_Seleccionada'] = $cantidad;
                      
                        $item["Cantidad"] = $cantidad;
                        $Lista_Bandera[] = $item;
                        $productos[$i]["Lotes_Seleccionados"][] = $item;
                        $productos[$i]["Lotes_Visuales"][] = $item["label"];
                        $siguiente = false;
                    } elseif ($siguiente && $productos[$i]["Cantidad"] > $cantidadDisponoble) {
                        $cantidad = $cantidad - $cantidadDisponoble;                                            
                        $item = $lote;
                        $item["label"] = "Lote: " . $lote["Lote"] . " - Vencimiento: " . $lote["Fecha_Vencimiento"] . " - Cantidad: " . $cantidadDisponoble;
                        $cantidadseleccionada = $cantidadDisponoble + $lote['Cantidad_Descontar'];
                        if($cantidadseleccionada<0){
                            $cantidadseleccionada=0;
                        }
                        $oItem = new complex("Inventario", "Id_Inventario", $lote["Id_Inventario"]);
                        $oItem->Cantidad_Seleccionada = number_format($cantidadseleccionada, 0, "", "");
                       $oItem->save();
                        unset($oCon);

                        $item["Cantidad"] = $cantidad;                      
                        $item['Cantidad_Seleccionada'] = $cantidadDisponoble;
                        $lotes[$j]['Cantidad_Seleccionada']= $cantidadDisponoble;
                        $Lista_Bandera[] = $item;
                        $productos[$i]["Lotes_Seleccionados"][] = $item;
                        $productos[$i]["Lotes_Visuales"][] = $item["label"];
                      
                    }
                }
                if(count($lotes)>0){
                    $suma = $suma - $producto["Cantidad"];
                    $prod["Nombre"] = $producto["Nombre_Producto"];
                    $prod["Id_Producto"] = $producto["Id_Producto"];
                    $prod["Lotes"] =  array_values($lotes);
                    $prod["precio"] = $producto["Precio_Venta"];
                    $productos[$i]["producto"] = $prod;
                    $productos[$i]["Suma"] = $suma;
                    $productos[$i]["Lotes"] = $lotes;
                    $productos[$i]["Lotes_Auxiliar"] = $lotes;
                }else{
                    unset($productos[$i]);
                }
                
            } else {
                unset($productos[$i]);
            }
        } else {
      
                unset($productos[$i]);
            
        }
    }else {
        unset($productos[$i]);
    }
}

$productos = array_values($productos);


echo json_encode($productos);



function CrearQueryMedicamentos($punto_dispensacion,$fecha_inicio, $fecha_fin, $bodega){
    $condicion='';
    if($bodega!=2){
        $condicion= ' AND P.Id_Categoria NOT IN (6,2,8,12)';
    }else{
        $condicion= ' AND P.Id_Categoria  IN (6,2) ';
    }
    $query = 'SELECT SUM(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad, PD.Id_Producto,IFNULL(CONCAT(P.Nombre_Comercial, " (",P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, ") ", P.Cantidad," ",P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico 
    ), CONCAT(P.Nombre_Comercial," ", "P.Laboratorio_Comercial")) as Nombre_Producto,P.Id_Categoria, (SELECT Nombre FROM Categoria WHERE Id_Categoria=P.Id_Categoria) as Categoria,P.Nombre_Comercial, (SELECT SUM(Cantidad-(Cantidad_Apartada-Cantidad_Seleccionada)) FROM Inventario WHERE Id_Producto=PD.Id_Producto) as Cantidad_Inventario_Bodega, 0 as Cantidad_Inventario, P.Embalaje, P.Cantidad_Presentacion
    FROM Producto_Dispensacion PD INNER JOIN (SELECT A.Id_Dispensacion FROM Auditoria A  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND A.Punto_Pre_Auditoria='.$punto_dispensacion.') A ON PD.Id_Dispensacion=A.Id_Dispensacion 
    INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE D.Estado_Dispensacion!="Anulada"
    '.$condicion.'
    GROUP BY PD.Id_Producto
   
    HAVING Cantidad>0 Order BY Nombre_Producto'; 

    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos = $oCon->getData();
    unset($oCon);
    return $productos;
}

function CrearQueryInventarioLotes($producto,$bodega,$nuevafecha){

   
    $query2 = 'SELECT I.Id_Inventario,I.Fecha_Vencimiento,(I.Cantidad) as Cantidad, I.Cantidad_Apartada,I.Codigo_CUM as Codigo_Cum,I.Lote,I.Cantidad_Seleccionada as Cantidad_Descontar,I.Costo as Precio_Venta,I.Id_Producto,
    CONCAT("Lote: ", I.Lote, " - Vencimiento: ", I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)) as label, I.Id_Inventario as value,(SELECT SUM(I2.Cantidad-I2.Cantidad_Apartada-I2.Cantidad_Seleccionada) FROM Inventario I2 WHERE I2.Id_Producto IN (' . $producto["Id_Producto"] . ') AND  I2.Id_Bodega = ' . $bodega . '
    AND I2.Cantidad-I2.Cantidad_Apartada-I2.Cantidad_Seleccionada > 0
   ) AS Cantidad_Disponible, (SELECT Id_Categoria FROM Categoria WHERE Id_Categoria=' . $producto["Id_Categoria"] . ') as Id_Categoria
    FROM Inventario I 
    WHERE I.Id_Producto IN (' . $producto["Id_Producto"] . ')
    AND I.Id_Bodega = ' . $bodega . ' AND  I.Fecha_Vencimiento>"'.$nuevafecha.'"
    AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) > 0
    ORDER BY I.Fecha_Vencimiento ASC';
    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query2);
    $lotes = $oCon->getData();
    unset($oCon);
    return $lotes;
}

function CalcularModulo($presentacion,$cantidad){
    $modulo=$cantidad%$presentacion;
    if($modulo!=0){
        $cantidad=$cantidad-$modulo;
    }
    return $cantidad;
}




?>