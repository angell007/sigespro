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

if ($bodega=='1') {
  $productos=CrearQueryMedicamentos($punto_dispensacion,$fecha_inicio, $fecha_fin, $bodega);

}elseif($bodega=='2'){    
    $productos=CrearQueryMateriales($punto_dispensacion,$fecha_inicio, $fecha_fin, $bodega);
}

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
            $cantidad = $producto["Cantidad"] - $producto['Cantidad_Inventario'];
         
            if ($cantidad > 0) {
                $productos[$i]["Rotativo"] = $productos[$i]["Cantidad"] . "/" . $productos[$i]['Cantidad_Inventario'];
                $modulo = $cantidad % $producto['Cantidad_Presentacion'];
                if($modulo!=0){
                    $cantidad = $cantidad + ($producto['Cantidad_Presentacion'] - $modulo);
                }
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
                        // $oItem->Cantidad_Seleccionada = number_format($cantidadseleccionada, 0, "", "");
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
                        $modulo = $cantidad % $producto['Cantidad_Presentacion'];
                        if($modulo!=0){
                            $cantidad = $cantidad + ($producto['Cantidad_Presentacion'] - $modulo);
                            $productos[$i]['Cantidad']+=($producto['Cantidad_Presentacion'] - $modulo);
                            $productos[$i]['Cantidad_Requerida']+=($producto['Cantidad_Presentacion'] - $modulo);
                        }
                      
                        $cantidadDisponoble=CalcularModulo($producto['Cantidad_Presentacion'],$cantidadDisponoble);
                        if($cantidadDisponoble!=0){
                            $item = $lote;
                            $item["label"] = "Lote: " . $lote["Lote"] . " - Vencimiento: " . $lote["Fecha_Vencimiento"] . " - Cantidad: " . $cantidadDisponoble;
    
                            $cantidadseleccionada = $cantidadDisponoble + $lote['Cantidad_Descontar'];
                            $oItem = new complex("Inventario", "Id_Inventario", $lote["Id_Inventario"]);
                            // $oItem->Cantidad_Seleccionada = number_format($cantidadseleccionada, 0, "", "");
                            $oItem->save();
                            unset($oCon);
    
                            $item["Cantidad"] = $cantidad;                      
                            $item['Cantidad_Seleccionada'] = $cantidadDisponoble;
                            $lotes[$j]['Cantidad_Seleccionada']= $cantidadDisponoble;
                            $Lista_Bandera[] = $item;
                            $productos[$i]["Lotes_Seleccionados"][] = $item;
                            $productos[$i]["Lotes_Visuales"][] = $item["label"];
                        }else{
                            unset($lotes[$j]);
                        }
                     
                      
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
            $query = 'SELECT GROUP_CONCAT(I2.Id_Inventario) as Inventario FROM Inventario I2 WHERE I2.Id_Producto IN (' . $producto["Id_Producto"] . ') AND  I2.Id_Bodega = ' . $bodega . '
              GROUP BY I2.Id_Producto';
            $oCon = new consulta();
            $oCon->setQuery($query);
            $inventario = $oCon->getData();
            unset($oCon);
            $cantidad = $producto["Cantidad"]-$producto['Cantidad_Inventario'];
            if ($inventario &&  $cantidad>0) {
                    $item=[];
                    $modulo = $cantidad % $producto['Cantidad_Presentacion'];
                    if($modulo!=0){
                        $cantidad = $cantidad + ($producto['Cantidad_Presentacion'] - $modulo);
                    }
                    $productos[$i]["Rotativo"] = $producto["Cantidad"] . "/" . $producto['Cantidad_Inventario'];
                    $productos[$i]["Lotes_Seleccionados"] = [];
                    $productos[$i]["Lotes_Visuales"] = [];
                    $item["label"] = "Lote:Pendiente - Vencimiento: Pendiente- Cantidad: " . $cantidad;
                    $item["Id_Inventario"] = 0;
                    $idpro=explode(",",$producto['Id_Producto']);
                    $item["Id_Producto"] =$idpro[0];
                    $productos[$i]['Cantidad_Requerida'] = $cantidad;
                    $productos[$i]['Cantidad'] = $cantidad;
                    $productos[$i]["Lotes_Seleccionados"][] = $item;
                    $productos[$i]["Lotes_Visuales"][] = $item["label"];
                    $productos[$i]["Cantidad_Disponible"] = 0;
                    $productos[$i]["Pendientes"] = $cantidad;              
            } else {
                unset($productos[$i]);
            }
        }
    }else {
        unset($productos[$i]);
    }
}

$productos = array_values($productos);

sort($productos);

echo json_encode($productos);



function CrearQueryMedicamentos($punto_dispensacion,$fecha_inicio, $fecha_fin, $bodega){
    $query = '(SELECT  GROUP_CONCAT(DISTINCT PRD.Id_Producto) as Id_Producto,IFNULL(CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
    PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
    ), CONCAT(PRD.Nombre_Comercial," ", PRD.Laboratorio_Comercial)) as Nombre_Producto ,PRD.Nombre_Comercial,  SUM( PR.Cantidad_Formulada ) as Formuladas, ROUND((SUM(PR.Cantidad_Formulada)*0.1)+SUM(PR.Cantidad_Formulada)) as Cantidad, PRD.Cantidad_Presentacion, C.Nombre as Categoria, PRD.Id_Categoria,IFNULL((SELECT SUM(I2.Cantidad-I2.Cantidad_Apartada-I2.Cantidad_Seleccionada) FROM Inventario I2 WHERE I2.Id_Punto_Dispensacion=' . $punto_dispensacion . ' AND I2.Id_Producto=PR.Id_Producto GROUP BY I2.Id_Producto),0) as Cantidad_Inventario, PRD.ATC, PRD.Presentacion, PRD.Concentracion, PRD.Principio_Activo, PRD.Embalaje
    FROM Producto_Dispensacion PR
    INNER JOIN Dispensacion D
    ON D.Id_Dispensacion = PR.Id_Dispensacion
    INNER JOIN Producto PRD
    ON PR.Id_Producto = PRD.Id_Producto
    LEFT JOIN Categoria C
    ON PRD.Id_Categoria=C.Id_Categoria
    WHERE D.Id_Punto_Dispensacion =' . $punto_dispensacion . '
    AND D.Fecha_Actual BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59" AND PRD.ATC IS NOT NULL AND PRD.Id_Categoria IN (12,9,8)
    GROUP BY  PRD.ATC, PRD.Cantidad, PRD.Unidad_Medida, PRD.Concentracion, PRD.Presentacion ORDER BY PRD.ATC) UNION (
        
    SELECT  GROUP_CONCAT(DISTINCT PRD.Id_Producto) as Id_Producto,IFNULL(CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
    PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
    ), CONCAT(PRD.Nombre_Comercial," ", PRD.Laboratorio_Comercial)) as Nombre_Producto ,PRD.Nombre_Comercial,  SUM( PR.Cantidad_Formulada ) as Formuladas, ROUND((SUM(PR.Cantidad_Formulada)*0.1)+SUM(PR.Cantidad_Formulada)) as Cantidad, PRD.Cantidad_Presentacion, C.Nombre as Categoria, PRD.Id_Categoria,IFNULL((SELECT SUM(I2.Cantidad-I2.Cantidad_Apartada-I2.Cantidad_Seleccionada) FROM Inventario I2 WHERE I2.Id_Punto_Dispensacion=' . $punto_dispensacion . ' AND I2.Id_Producto=PR.Id_Producto GROUP BY I2.Id_Producto),0) as Cantidad_Inventario, PRD.ATC, PRD.Presentacion, PRD.Concentracion, PRD.Principio_Activo, PRD.Embalaje
    FROM Producto_Dispensacion PR
    INNER JOIN Dispensacion D
    ON D.Id_Dispensacion = PR.Id_Dispensacion
    INNER JOIN Producto PRD
    ON PR.Id_Producto = PRD.Id_Producto
    LEFT JOIN Categoria C
    ON PRD.Id_Categoria=C.Id_Categoria
    WHERE D.Id_Punto_Dispensacion =' . $punto_dispensacion . '
    AND D.Fecha_Actual BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59" AND PRD.ATC IS NOT NULL AND PRD.Id_Categoria NOT IN (12,9,8)
    GROUP BY  PRD.Id_Producto ORDER BY PRD.ATC ) ';
    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos = $oCon->getData();
    unset($oCon);

    return $productos;
}
function CrearQueryMateriales($punto_dispensacion,$fecha_inicio, $fecha_fin, $bodega){
        $query = 'SELECT  GROUP_CONCAT(DISTINCT PRD.Id_Producto) as Id_Producto,IFNULL(CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
        PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
        ), CONCAT(PRD.Nombre_Comercial," ", "PRD.Laboratorio_Comercial")) as Nombre_Producto ,PRD.Nombre_Comercial,  SUM( PR.Cantidad_Formulada ) as Formuladas, ROUND((SUM(PR.Cantidad_Formulada)*0.1)+SUM(PR.Cantidad_Formulada)) as Cantidad, PRD.Cantidad_Presentacion, C.Nombre as Categoria, PRD.Id_Categoria,IFNULL((SELECT SUM(I2.Cantidad-I2.Cantidad_Apartada-I2.Cantidad_Seleccionada) FROM Inventario I2 WHERE I2.Id_Punto_Dispensacion=' . $punto_dispensacion . ' AND I2.Id_Producto=PR.Id_Producto GROUP BY I2.Id_Producto),0) as Cantidad_Inventario, PRD.ATC, PRD.Presentacion, PRD.Concentracion, PRD.Principio_Activo, PRD.Embalaje
        FROM Producto_Dispensacion PR
        INNER JOIN Dispensacion D
        ON D.Id_Dispensacion = PR.Id_Dispensacion
        INNER JOIN Producto PRD
        ON PR.Id_Producto = PRD.Id_Producto
        LEFT JOIN Categoria C
        ON PRD.Id_Categoria=C.Id_Categoria
        WHERE D.Id_Punto_Dispensacion =' . $punto_dispensacion . '
        AND D.Fecha_Actual BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59" AND PRD.Id_Categoria=6
        GROUP BY PR.Id_Producto ';


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