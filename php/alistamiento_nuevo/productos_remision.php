<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');

if($tipo=='Devolucion'){
    $desde = 'Devolucion_Compra';
}else{
    $desde = 'Remision';
}
$query = 'SELECT SubC.Id_Categoria_Nueva, SubC.Nombre as Subcategoria,
    PR.Lote, PR.Cantidad, PR.Fecha_Vencimiento, PR.Id_Producto,
    PR.Id_Inventario_Nuevo, /*PR.Id_Producto_Remision, */
    P.Nombre_Comercial,P.Embalaje, P.Laboratorio_Comercial, P.Laboratorio_Generico, 
    P.Peso_Presentacion_Minima, P.Peso_Presentacion_Regular, P.Imagen,
    P.Peso_Presentacion_Maxima,P.Codigo_Barras,P.Presentacion, P.Id_Subcategoria,
        IFNULL(CONCAT(P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " - ", 
            P.Cantidad," ",
            P.Unidad_Medida), P.Nombre_Comercial) AS Nombre_Producto,
    
    I.Alternativo,
    E.Nombre AS Nombre_Estiba, E.Id_Estiba,
    (CEIL((PR.Cantidad/P.Cantidad_Presentacion)*P.Peso_Presentacion_Regular)) as Peso_Total
    
FROM Producto_'.$desde.' PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
INNER JOIN Inventario_Nuevo I ON PR.Id_Inventario_Nuevo=I.Id_Inventario_Nuevo
INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
LEFT JOIN Subcategoria SubC ON SubC.Id_Subcategoria = P.Id_Subcategoria
WHERE PR.Id_'.$desde.' =' . $id . '
ORDER BY E.Nombre DESC, Nombre_Producto ASC';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$oItem = new complex('Configuracion', 'Id_Configuracion', 1);
$nc = $oItem->getData();
unset($oItem);
$peso_general = 0;

$i = -1;
foreach ($productos as $producto) {
    $i++;

    $productos[$i]["Tolerancia_Individual"] = (int) $nc["Tolerancia_Peso_Individual"];
    // var_dump( $productos[$i]["Tolerancia_Individual"]);
    $peso_general += (int) $producto["Peso_Total"];

    $productos[$i]["Codigo_Ingresado"] = "";
    $productos[$i]["Peso_Ingresado"] = "";
    /* if ($i == 0) {
        $productos[$i]["Habilitado"] = "false";
        $productos[$i]["Clase"] = "noblur";
        $productos[$i]["Validado"] = false;
        $productos[$i]["Codigo_Validado"] = false;
    } else { */
        $productos[$i]["Habilitado"] = "true";
        $productos[$i]["Clase"] = "blur";
        $productos[$i]["Validado"] = false;
        $productos[$i]["Codigo_Validado"] = false;
     } 
// }

$productos=separarPorEstiba($productos);
$resultado["Productos"] = $productos;
$resultado["Peso_General"] = $peso_general;
$resultado["Tolerancia_Global"] = $nc["Tolerancia_Peso_Global"];
echo json_encode($resultado);


function separarPorEstiba($productos)
{
    

    
    $XProducto = 0; //index Por Producto 
   
    $cantidad=0;
    foreach ($productos as $key => $producto) {
        if ($producto['Nombre_Estiba'] != $productos[$key-1]['Nombre_Estiba'] || $key==0) {
        
            foreach($productos as $key2=>$productoComparacion){
                if ($producto['Nombre_Estiba'] == $productoComparacion['Nombre_Estiba']) {
                    $cantidad++;     
                
                }
            }
           
            $productos[$key]['Cantidades_Productos_Estiba']=$cantidad;
         
            $cantidad=0;
        }
    }

    return $productos;
}
