<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT C.Id_Categoria, C.Nombre as Categoria, PR.Lote, PR.Cantidad, P.Nombre_Comercial,P.Embalaje, P.Laboratorio_Comercial, P.Laboratorio_Generico, IFNULL(CONCAT(P.Principio_Activo, " ",
P.Presentacion, " ",
P.Concentracion, " - ", 
P.Cantidad," ",
P.Unidad_Medida), P.Nombre_Comercial) AS Nombre_Producto, PR.Fecha_Vencimiento, PR.Id_Producto, P.Peso_Presentacion_Minima, P.Peso_Presentacion_Regular, P.Peso_Presentacion_Maxima,P.Codigo_Barras,I.Alternativo,P.Presentacion, (CEIL((PR.Cantidad/P.Cantidad_Presentacion)*P.Peso_Presentacion_Regular)) as Peso_Total, P.Id_Categoria, PR.Id_Inventario, PR.Id_Producto_Remision, P.Imagen
FROM Producto_Remision PR
INNER JOIN Producto P
ON PR.Id_Producto=P.Id_Producto
INNER JOIN Inventario I
ON PR.Id_Inventario=I.Id_Inventario
LEFT JOIN Categoria C
ON C.Id_Categoria = P.Id_Categoria
WHERE PR.Id_Remision ='.$id.'
ORDER BY P.Id_Categoria DESC, Nombre_Producto ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$oItem = new complex('Configuracion','Id_Configuracion',1);
$nc = $oItem->getData();
unset($oItem);
$peso_general=0;

$i=-1;
foreach($productos as $producto){ $i++;

    $productos[$i]["Tolerancia_Individual"]=(int)$nc["Tolerancia_Peso_Individual"];
   // var_dump( $productos[$i]["Tolerancia_Individual"]);
    $peso_general+=(int)$producto["Peso_Total"];
    
    $productos[$i]["Codigo_Ingresado"]="";
    $productos[$i]["Peso_Ingresado"]="";
    if($i==0){
        $productos[$i]["Habilitado"]="false";
        $productos[$i]["Clase"]="noblur";
        $productos[$i]["Validado"]=false;
         $productos[$i]["Codigo_Validado"]=false;
    }else{
       $productos[$i]["Habilitado"]="true"; 
       $productos[$i]["Clase"]="blur";
       $productos[$i]["Validado"]=false;
       $productos[$i]["Codigo_Validado"]=false;
    }


}
//var_dump($peso_general);
//$productos[]['Peso_General']=$peso_general;
$resultado["Productos"]=$productos;
$resultado["Peso_General"]=$peso_general;
$resultado["Tolerancia_Global"]=$nc["Tolerancia_Peso_Global"];
echo json_encode($resultado);

?>