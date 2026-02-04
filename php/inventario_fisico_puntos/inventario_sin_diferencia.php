<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_inventario_fisico = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

/*$query = 'SELECT PIF.Id_Producto_Inventario_Fisico, PIF.Id_Inventario_Fisico_Punto, P.Nombre_Comercial, CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto, PIF.Lote, PIF.Fecha_Vencimiento, PIF.Primer_Conteo AS Cantidad_Encontrada, PIF.Segundo_Conteo, "" AS Cantidad_Final
 FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto WHERE PIF.Id_Inventario_Fisico_Punto='.$id_inventario_fisico . ' AND PIF.Primer_Conteo=PIF.Segundo_Conteo ORDER BY P.Nombre_Comercial';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$listado = [];*/
$query='SELECT I.*
FROM Inventario_Fisico_Punto I 
WHERE I.Id_Inventario_Fisico_Punto='.$id_inventario_fisico;

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$invenatrio = $oCon->getData();
unset($oCon);

$query='SELECT I.*
FROM Inventario_Fisico_Punto I 
WHERE I.Estado = "Por Confirmar" AND  I.Id_Punto_Dispensacion='.$invenatrio['Id_Punto_Dispensacion'].'
 AND I.Fecha_Inicio LIKE "%'.date("Y-m-d",strtotime($invenatrio["Fecha_Inicio"])).'%"';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$inventarios= $oCon->getData();
unset($oCon);
if( count($inventarios)>1){
    foreach ($inventarios as $item) {
        $oItem = new complex('Inventario_Fisico_Punto','Id_Inventario_Fisico_Punto',$item['Id_Inventario_Fisico_Punto']);
        $oItem->Comparar='No';
        $oItem->save();
        unset($oItem);
    }
}
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
    HAVING Cantidad_Encontrada=Segundo_Conteo';
}elseif ($tipo=="Si") {
    $query = 'SELECT 
    PIFP.Id_Producto,
    GROUP_CONCAT(PIFP.Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico ,
    PIFP.Lote,
    PIFP.Fecha_Vencimiento,PIFP.Id_Inventario_Nuevo,
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
    WHERE IFP.Estado = "Por Confirmar" AND IFP.Id_Punto_Dispensacion='.$invenatrio['Id_Punto_Dispensacion'].' AND IFP.Fecha_Inicio LIKE  "%'.date("Y-m-d",strtotime($invenatrio["Fecha_Inicio"])).'%" 
    GROUP BY PIFP.Id_Producto, PIFP.Lote
    HAVING Cantidad_Encontrada=Segundo_Conteo';
}

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

/*foreach ($resultado as $value) {
    $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
    // Registrar (actualizar) el conteo final en el producto de inventario físico
    
        for ($i=0; $i < count( $id_inventario) ; $i++) { 
            if($i!=0){
                /* $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
                $oItem->delete();
                unset($oItem); 
             }else{
                $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
                $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
                $conteo1 = number_format((INT)$value['Cantidad_Encontrada'],0,'',''); // parseando
                $conteo2 = number_format((INT)$value['Segundo_Conteo'],0,'',''); // parseando
                $oItem->Cantidad_Final = $cantidad;
                $oItem->Primer_Conteo = $conteo1;
                $oItem->Segundo_Conteo = $conteo2;
               $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
                $oItem->save();
                unset($oItem);
             }
        } 
        
        
    }
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
    HAVING Cantidad_Encontrada=Segundo_Conteo';
   
   $oCon= new consulta();
   $oCon->setTipo('Multiple');
   $oCon->setQuery($query);
   $resultado = $oCon->getData();
   unset($oCon);

*/

echo json_encode($resultado);


?>