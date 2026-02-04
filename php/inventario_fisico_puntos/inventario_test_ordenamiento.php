<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$tipo='Si';

echo $tipo;
if($tipo=="No"){
        $query = 'SELECT 
        PIFP.Id_Producto,
        GROUP_CONCAT(PIFP.Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico ,
        PIFP.Lote,
        PIFP.Fecha_Vencimiento,
        SUM( PIFP.Primer_Conteo) AS Cantidad_Encontrada,
        SUM( PIFP.Segundo_Conteo) AS Segundo_Conteo, P.Nombre_Comercial, CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto,
        (
        CASE
            WHEN (SUM(PIFP.Primer_Conteo) < SUM(PIFP.Segundo_Conteo))  THEN CONCAT("+",(SUM( PIFP.Segundo_Conteo) -SUM( PIFP.Primer_Conteo)))
            WHEN (SUM(PIFP.Primer_Conteo) > SUM(PIFP.Segundo_Conteo)) THEN CONCAT("",(SUM(PIFP.Segundo_Conteo) -SUM(PIFP.Primer_Conteo)))
        END
    ) AS Cantidad_Diferencial
        FROM
        Producto_Inventario_Fisico_Punto PIFP
        INNER JOIN Inventario_Fisico_Punto IFP
        ON PIFP.Id_Inventario_Fisico_Punto=IFP.Id_Inventario_Fisico_Punto
        INNER JOIN Producto P ON PIFP.Id_Producto=P.Id_Producto
        WHERE IFP.Estado = "Por Confirmar" AND IFP.Id_Punto_Dispensacion='.$invenatrio['Id_Punto_Dispensacion'].' AND IFP.Fecha_Inicio LIKE  "%'.date("Y-m-d",strtotime($invenatrio["Fecha_Inicio"])).'%" 
        GROUP BY PIFP.Id_Producto, PIFP.Lote
        HAVING Cantidad_Encontrada!=Segundo_Conteo';
}elseif ($tipo=="Si") {
        $query = 'SELECT 
        PIFP.Id_Producto,
        GROUP_CONCAT(PIFP.Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico ,
        PIFP.Lote,
        PIFP.Fecha_Vencimiento,PIFP.Id_Inventario,
        SUM( PIFP.Primer_Conteo) AS Cantidad_Encontrada,
        PIFP.Cantidad_Inventario AS Segundo_Conteo, P.Nombre_Comercial, CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto,
        (
        CASE
            WHEN (PIFP.Cantidad_Inventario < SUM(PIFP.Primer_Conteo))  THEN CONCAT("+",(SUM( PIFP.Primer_Conteo) -( PIFP.Cantidad_Inventario)))
            WHEN (PIFP.Cantidad_Inventario > SUM(PIFP.Primer_Conteo)) THEN CONCAT("",(SUM(PIFP.Primer_Conteo) -(PIFP.Cantidad_Inventario)))
        END
    ) AS Cantidad_Diferencial
        FROM
        Producto_Inventario_Fisico_Punto PIFP
        INNER JOIN Inventario_Fisico_Punto IFP
        ON PIFP.Id_Inventario_Fisico_Punto=IFP.Id_Inventario_Fisico_Punto
        INNER JOIN Producto P ON PIFP.Id_Producto=P.Id_Producto
        WHERE PIFP.Id_Inventario_Fisico_Punto IN (242,241)

        GROUP BY PIFP.Id_Producto, PIFP.Lote
        HAVING Cantidad_Encontrada!=Segundo_Conteo
        LIMIT 10';
}



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$listado = [];

if($tipo=="Si"){

    $query_inventario='SELECT PIF.Id_Inventario FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Inventario_Fisico_Punto IFP ON PIF.Id_Inventario_Fisico_Punto=IFP.Id_Inventario_Fisico_Punto
    WHERE IFP.Id_Inventario_Fisico_Punto  IN (242,241)';

   
    $query='SELECT CONCAT("-",I.Cantidad) as Cantidad_Diferencial, 0 as Cantidad_Encontrada, I.Fecha_Vencimiento, I.Id_Producto, 0 as Id_Producto_Inventario_Fisico, I.Lote, P.Nombre_Comercial, CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto, I.Cantidad as Segundo_Conteo, I.Cantidad as Cantidad_Inventario, I.Id_Inventario, "Si" as Tiene_Inventario
    FROM Inventario I 

    INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto
    WHERE I.Id_Inventario NOT IN ('.$query_inventario.') AND I.Id_Punto_Dispensacion=64 AND I.Cantidad>0 ORDER BY P.Nombre_Comercial';
    
             
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos_no_contados = $oCon->getData();
    unset($oCon);
    
    $resultado=array_merge($resultado,$productos_no_contados);
   

    
}

var_dump($resultado);

usort($resultado,'Nombre_Comercial');

var_dump($resultado);


foreach ($resultado as $i => $res) {
    $resultado[$i]['Cantidad_Encontrada'] = (int) $res['Cantidad_Encontrada'];
    $resultado[$i]['Segundo_Conteo'] = (int) $res['Segundo_Conteo'];
}

echo json_encode($resultado);
function Nombre_Comercial($a,$b){
    return strnatcmp($a['Nombre_Comercial'],$b['Nombre_Comercial']);
}

?>