<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.consulta.php');


$id_doc_inventario_fisico = ( isset( $_REQUEST['inv'] ) ? $_REQUEST['inv'] : '' );

$query = 'SELECT * FROM Doc_Inventario_Fisico_Punto I WHERE I.Id_Doc_Inventario_Fisico_Punto='.$id_doc_inventario_fisico ;

$oCon= new consulta();
$oCon->setQuery($query);
$Inv = $oCon->getData();
unset($oCon);

if($Inv['Estado']=='Primer Conteo'){
   
    $producto_inventario_fisico=GetProductos($Inv['Id_Doc_Inventario_Fisico_Punto']);

    $producto_no_contados=GetProductosNoContados($Inv['Id_Doc_Inventario_Fisico_Punto']);
        
    $ids_prod_inv_fisico;
    foreach ($producto_inventario_fisico as $key => $value) {
        $ids_prod_inv_fisico.=$value['Id_Producto_Doc_Inventario_Fisico_Punto'] . ' ,';
    }

    #quitarle la última coma (,) de la cadena de texto para que funcione la consulta
    $ids_prod_inv_fisico=substr($ids_prod_inv_fisico,0, -1);
      
    
    $prodcuto_no_inventario=GetProductosNoInventario($Inv['Id_Doc_Inventario_Fisico_Punto'],$ids_prod_inv_fisico);
  
 

         $lista=array_merge($producto_inventario_fisico, $producto_no_contados,$prodcuto_no_inventario);
  
     $listaSinDiferencia=[];
     $listaConDiferencia=[];

     //separar la lista en 2, con y sin diferencias para luego ordenarlos por nombre independientemente
     foreach ($lista as $key => $value) {
       
         if ($value['Cantidad_Diferencial']=='0') {
          
             array_push($listaSinDiferencia,$value);
           
         }else{
             array_push($listaConDiferencia,$value);
            
         }
     }

     $listaSinDiferencia=ordenarListaNombre($listaSinDiferencia,'Nombre_Comercial');
     $listaConDiferencia=ordenarListaNombre($listaConDiferencia,'Nombre_Comercial');
   
     $lista=array_merge($listaConDiferencia,$listaSinDiferencia);

     $resultado['tipo']="success";
     $resultado['Productos']=$listaConDiferencia;
     $resultado['Productos_Sin_Diferencia']=$listaSinDiferencia;
     $resultado['Estado']=$Inv['Estado'];
     $resultado['Inventarios']=$Inv['Id_Doc_Inventario_Fisico_Punto'];
    
  
}else{

    $resultado['tipo']="error";
    $resultado['titulo']="No puede realizar esta acción";
    $resultado['mensaje']="Para este inventario ya se esta realizando un reconteo, por favor verifique";

}

 echo json_encode($resultado);

function GetProductos($inventarios){
    
    global $Inv;
    $query = 'SELECT
    GROUP_CONCAT(
        PIF.Id_Producto_Doc_Inventario_Fisico_Punto
    ) AS Id_Producto_Doc_Inventario_Fisico_Punto,
    PIF.Id_Doc_Inventario_Fisico_Punto, PIF.Id_Inventario_Nuevo, P.Nombre_Comercial,
    CONCAT(
        P.Principio_Activo,
        " ",
        P.Presentacion,
        " ",
        P.Concentracion,
        P.Cantidad,
        " ",
        P.Unidad_Medida,
        " LAB: ",
        P.Laboratorio_Comercial
    ) AS Nombre_Producto,
    PIF.Lote,
    PIF.Fecha_Vencimiento,
    SUM(PIF.Primer_Conteo) AS Cantidad_Encontrada,
    (
    CASE 
        WHEN SUM(PIF.Primer_Conteo) =(SELECT SUM(Cantidad)  FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Lote = PIF.Lote AND Id_Estiba = '.$Inv['Id_Estiba'].') THEN 0
        WHEN SUM(PIF.Primer_Conteo) <(SELECT SUM(Cantidad) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Lote = PIF.Lote AND Id_Estiba = '.$Inv['Id_Estiba'].') THEN CONCAT("", (SUM(PIF.Primer_Conteo) -( SELECT SUM(Cantidad) FROM Inventario_Nuevo WHERE  Id_Producto = PIF.Id_Producto AND Lote = PIF.Lote AND Id_Estiba = '.$Inv['Id_Estiba'].')))
        WHEN SUM(PIF.Primer_Conteo) >(SELECT SUM(Cantidad) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Lote = PIF.Lote AND Id_Estiba = '.$Inv['Id_Estiba'].') THEN CONCAT("+",(SUM(PIF.Primer_Conteo) -(SELECT SUM(Cantidad)  FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Lote = PIF.Lote AND Id_Estiba = '.$Inv['Id_Estiba'].')))
    END
) AS Cantidad_Diferencial,
(
    SELECT
        SUM(Cantidad)
    FROM
        Inventario_Nuevo
    WHERE
        Id_Producto = PIF.Id_Producto AND Lote = PIF.Lote AND Id_Estiba ='.$Inv['Id_Estiba'].'
) AS Cantidad_Inventario,
"" AS Cantidad_Final,
PIF.Id_Producto
FROM Producto_Doc_Inventario_Fisico_Punto PIF
INNER JOIN Producto P ON PIF.Id_Producto = P.Id_Producto
WHERE PIF.Id_Doc_Inventario_Fisico_Punto IN('.$inventarios.')
GROUP BY PIF.Id_Producto, PIF.Lote
HAVING Cantidad_Inventario IS NOT NULL
ORDER BY P.Nombre_Comercial
';
    
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos_inventario = $oCon->getData();
  
    unset($oCon);
   
    
    return $productos_inventario;
   
}

function GetProductosNoContados ($inventarios){
    global $Inv;
    $query_inventario='SELECT PIF.Id_Inventario_Nuevo FROM Producto_Doc_Inventario_Fisico_Punto PIF
    WHERE PIF.Id_Doc_Inventario_Fisico_Punto IN ('.$inventarios.') ';
 
   
    $query='SELECT CONCAT("-",I.Cantidad) as Cantidad_Diferencial, 0 as Cantidad_Encontrada, I.Fecha_Vencimiento, I.Id_Producto, 0 as Id_Producto_Doc_Inventario_Fisico_Punto, I.Lote, P.Nombre_Comercial, CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto, I.Cantidad as Segundo_Conteo, I.Cantidad as Cantidad_Inventario, I.Id_Inventario_Nuevo
    FROM Inventario_Nuevo I 
    INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto
    WHERE I.Id_Inventario_Nuevo NOT IN ('.$query_inventario.') AND I.Id_Estiba='.$Inv['Id_Estiba'].' AND I.Cantidad>0 ORDER BY P.Nombre_Comercial';
        
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos_no_contados = $oCon->getData();
    unset($oCon);
   
    return $productos_no_contados;
}

function GetProductosNoInventario($id_inv_fisico,$id_productos){

    if ($id_productos) {
        $cond='WHERE PIF.Id_Producto_Doc_Inventario_Fisico_Punto NOT IN ('.$id_productos.') 
                 AND PIF.Id_Doc_Inventario_Fisico_Punto IN ('.$id_inv_fisico.')
                 ORDER BY P.Nombre_Comercial';
     }else{
         $cond='WHERE PIF.Id_Doc_Inventario_Fisico_Punto IN ('.$id_inv_fisico.')
         ORDER BY P.Nombre_Comercial';
     }
         $query='SELECT CONCAT("+",PIF.Primer_Conteo) as Cantidad_Diferencial, PIF.Primer_Conteo as Cantidad_Encontrada, PIF.Fecha_Vencimiento,
         PIF.Id_Producto, PIF.Id_Producto_Doc_Inventario_Fisico_Punto, PIF.Lote, P.Nombre_Comercial, CONCAT_WS("", P.Principio_Activo,P.Presentacion,
         P.Concentracion, P.Cantidad, P.Unidad_Medida, " LAB1: ", P.Laboratorio_Comercial, " LAB2: ",P.Laboratorio_Generico) AS Nombre_Producto,
         0 as Segundo_Conteo, 0 as Cantidad_Inventario, 0 AS Id_Inventario_Nuevo
         FROM Producto_Doc_Inventario_Fisico_Punto PIF 
         INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto '. $cond;

   
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productosNoInvetario= $oCon->getData();
    unset($oCon);
  
  
    return $productosNoInvetario;

    


}

//ordena la lista muntidimencional por nombre
function ordenarListaNombre ($lista, $campo) {  
    $position = array();  
    $newRow = array();  
    foreach ($lista as $key => $row) {  
            $position[$key]  = $row[$campo];  
            $newRow[$key] = $row;  
    }  
        asort($position);  
  
    $returnArray = array();  
    foreach ($position as $key => $pos) {       
        $returnArray[] = $newRow[$key];  
    }  
    return $returnArray;  
}
