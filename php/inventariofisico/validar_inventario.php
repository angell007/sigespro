<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id_inventario_fisico = ( isset( $_REQUEST['inv'] ) ? $_REQUEST['inv'] : '' );

$query = 'SELECT * FROM Inventario_Fisico I WHERE I.Id_Inventario_Fisico='.$id_inventario_fisico ;

$oCon= new consulta();
$oCon->setQuery($query);
$Inv = $oCon->getData();
unset($oCon);


if($Inv['Estado']=='Por Confirmar'){

    $query="SELECT GROUP_CONCAT(Id_Inventario_Fisico) as Id_Inventario_Fisico  
    FROM Inventario_Fisico WHERE Tipo_Inventario='Barrido' AND Categoria=$Inv[Categoria] AND Bodega=$Inv[Bodega] AND Estado='Por Confirmar'";

    $oCon= new consulta();
    $oCon->setQuery($query);
    $Inventarios = $oCon->getData();
    unset($oCon);
    

    if($Inventarios['Id_Inventario_Fisico']){
        $query2='UPDATE Inventario_Fisico
        SET Estado ="Reconteo"
        WHERE  Id_Inventario_Fisico IN ('.$Inventarios['Id_Inventario_Fisico'].')';
        $oCon= new consulta();
        $oCon->setQuery($query2);     
        $oCon->createData();     
        unset($oCon);
    }

    $producto_inventario_fisico=GetProductos($Inventarios['Id_Inventario_Fisico']);

    $producto_no_contados=GetProductosNoContados($Inventarios['Id_Inventario_Fisico']);



    $lista=array_merge($producto_inventario_fisico, $producto_no_contados);
    
    usort($lista,'Nombre_Comercial');
    $resultado['tipo']="success";
    $resultado['Productos']=$lista;
    $resultado['Inventarios']=$Inventarios['Id_Inventario_Fisico'];



}else{

    $resultado['tipo']="error";
    $resultado['titulo']="No puede realizar esta acción";
    $resultado['mensaje']="Para este inventario ya se esta realizando un reconteo, por favor verifique";

}


echo json_encode($resultado);

function GetProductos($inventarios){
    global $Inv;
    $query = 'SELECT GROUP_CONCAT(PIF.Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico , PIF.Id_Inventario_Fisico, PIF.Id_Inventario, P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto, PIF.Lote, PIF.Fecha_Vencimiento, SUM(PIF.Primer_Conteo) AS Cantidad_Encontrada, 
    (
        CASE
            WHEN SUM(PIF.Primer_Conteo) <(SELECT SUM(Cantidad) FROM Inventario WHERE Id_Producto=PIF.Id_Producto AND Lote=PIF.Lote AND Id_Bodega='.$Inv['Bodega'].' )
            THEN CONCAT("",SUM(PIF.Primer_Conteo)-(SELECT SUM(Cantidad) FROM Inventario WHERE Id_Producto=PIF.Id_Producto AND Lote=PIF.Lote AND Id_Bodega='.$Inv['Bodega'].' ))
            WHEN SUM(PIF.Primer_Conteo) >(SELECT SUM(Cantidad) FROM Inventario WHERE Id_Producto=PIF.Id_Producto AND Lote=PIF.Lote AND Id_Bodega='.$Inv['Bodega'].' ) THEN CONCAT("+",(SUM(PIF.Primer_Conteo)-(SELECT SUM(Cantidad) FROM Inventario WHERE Id_Producto=PIF.Id_Producto AND Lote=PIF.Lote AND Id_Bodega='.$Inv['Bodega'].' )))
        END
    ) AS Cantidad_Diferencial,
    (SELECT SUM(Cantidad) FROM Inventario WHERE Id_Producto=PIF.Id_Producto AND Lote=PIF.Lote AND Id_Bodega='.$Inv['Bodega'].'  ) as Cantidad_Inventario,
     "" AS Cantidad_Final, PIF.Id_Producto
      FROM Producto_Inventario_Fisico PIF 
      INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto 
      WHERE PIF.Id_Inventario_Fisico IN ('.$inventarios .' )
    GROUP BY PIF.Id_Producto, PIF.Lote
    HAVING Cantidad_Encontrada!=Cantidad_Inventario
    ORDER BY P.Nombre_Comercial';
     

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos_inventario = $oCon->getData();
    unset($oCon);

    return $productos_inventario;
}

function GetProductosNoContados ($inventarios){
    
    global $Inv;

    $query_inventario='SELECT PIF.Id_Inventario FROM Producto_Inventario_Fisico PIF
    WHERE PIF.Id_Inventario_Fisico IN ('.$inventarios.') ';
    $condicion="";
    if($Inv['Categoria']!='0'){
        $condicion=" AND P.Id_Categoria=".$Inv['Categoria'];
    }
   
   
    $query='SELECT CONCAT("-",I.Cantidad) as Cantidad_Diferencial, 
    0 as Cantidad_Encontrada, I.Fecha_Vencimiento, I.Id_Producto, 0 as Id_Producto_Inventario_Fisico, 
    I.Lote, P.Nombre_Comercial, 
    CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto, 
    I.Cantidad as Segundo_Conteo, 
    I.Cantidad as Cantidad_Inventario, 
    I.Id_Inventario
    FROM Inventario I 
    INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto
    WHERE I.Id_Inventario NOT IN ('.$query_inventario.') AND I.Id_Bodega='.$Inv['Bodega'].$condicion.' AND I.Cantidad>0 ORDER BY P.Nombre_Comercial';

    
  
             
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos_no_contados = $oCon->getData();
    unset($oCon);

    return $productos_no_contados;
}


function Nombre_Comercial($a,$b){
    return strnatcmp($a['Nombre_Comercial'],$b['Nombre_Comercial']);
}
?>