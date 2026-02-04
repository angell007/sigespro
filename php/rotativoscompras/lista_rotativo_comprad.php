<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fechaInicial = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fechaFinal = ( isset( $_REQUEST['final'] ) ? $_REQUEST['final'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

$oItem = new complex('Configuracion','Id_Configuracion',1);
$nc = $oItem->getData();
unset($oItem);




switch($tipo){
case "Dispositivo":{
     $query = 'SELECT P.ATC as ATC , P.Descripcion_ATC as Descripcion , P.Codigo_Cum as CUM, P.Id_Producto as Id_Producto , PR.Nombre_Producto as Producto , sum(PR.Cantidad) as CantidadTotal
           FROM Remision R 
               INNER JOIN Producto_Remision PR 
               ON R.Id_Remision = PR.Id_Remision 
               INNER JOIN Producto P 
               ON P.Id_Producto = PR.Id_Producto 
               INNER JOIN Inventario I
               ON I.Id_Producto=PR.Id_Producto
           WHERE R.Fecha BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
           AND P.Tipo = "Dispositivo"
           GROUP BY PR.Id_Producto 
           ORDER BY P.ATC ASC , P.Presentacion ASC'  ; 
    break;
}
case "Medicamento":{ 
     $query = 'SELECT P.ATC as ATC , P.Descripcion_ATC as Descripcion , P.Codigo_Cum as CUM, GROUP_CONCAT(DISTINCT P.Id_Producto) as Id_Producto , IF(PR.Nombre_Producto="" OR PR.Nombre_Producto IS NULL, CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", P.Cantidad, 			 
     P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ), PR.Nombre_Producto) as Producto , ( ROUND((sum(PR.Cantidad)*2)*('.$nc["Rotativo"].'/100) ) )  as CantidadTotal,sum(PR.Cantidad) as Consumida, (SELECT sum(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada))  FROM Inventario I WHERE I.Id_Producto=PR.Id_Producto AND I.Id_Bodega!=0) as CantidadActual, P.Cantidad_Presentacion, "true" AS Desabilitado
           FROM Producto_Remision PR 
               INNER JOIN Remision R 
               ON R.Id_Remision = PR.Id_Remision 
               INNER JOIN Producto P 
               ON P.Id_Producto = PR.Id_Producto 
               
           WHERE DATE_FORMAT(R.Fecha,"%Y-%m-%d") BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
           AND P.Tipo = "Medicamento" AND R.Estado!="Anulada" 
           GROUP BY  P.ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion 
           HAVING (CantidadTotal/2)>CantidadActual
           ORDER BY P.Descripcion_ATC ASC , P.Presentacion ASC '  ; 
       
    break;
}
}



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$i=-1;
foreach ($resultado as $value) {$i++;

    $query ='SELECT SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada))  as CantidadActual FROM Inventario I
    WHERE I.Id_Producto IN ('.$value['Id_Producto'].') AND I.Id_Bodega!=0';
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $Cantidad = $oCon->getData();
    unset($oCon);

    if(($value['CantidadTotal']/2)<(INT)$Cantidad['CantidadActual']){
       
        unset($resultado[$i]);
    }else{
        $resultado[$i]['CantidadActual']=$Cantidad['CantidadActual'];
    }
   
}
$resultado=array_values($resultado);

$mensaje[] = array('Mensaje' => 'No se encuentra alguna compra relacionada con alguna remision' , 'DivMensaje' => true , "DivEncabezado" => false);

$i=-1;
foreach($resultado as $result){$i++;
    //query where = $resultado['Id_Producto']
    switch($tipo){
        case "Dispositivo":{
             $query1 = 'SELECT 
                    P.Id_Proveedor as Id_Proveedor,P.Nombre as NombreProveedor, POCN.Cantidad as Cantidad , POCN.Total as Total, OCN.Codigo as Codigo , OCN.Fecha as Fecha, 
                    IFNULL(CONCAT( Pr.Principio_Activo, " ", Pr.Presentacion, " ", Pr.Concentracion, " (", Pr.Nombre_Comercial,") ", Pr.Cantidad," ", Pr.Unidad_Medida, " " ), Pr.Nombre_Comercial) as nombre,
                    Pr.Cantidad_Minima as Cantidad_Minima,
                    Pr.Cantidad_Maxima as Cantidad_Maxima,
                     false as "DivMensaje",
                    true as "DivEncabezado"
                    FROM `Orden_Compra_Internacional` OCN 
                    INNER JOIN Producto_Orden_Compra_Internacional POCN 
                        ON POCN.Id_Orden_Compra_Internacional = OCN.Id_Orden_Compra_Internacional
                    INNER JOIN Proveedor P 
                        ON P.Id_Proveedor = OCN.Id_Proveedor 
                    INNER JOIN Producto Pr 
                        ON POCN.Id_Producto = Pr.Id_Producto 
               WHERE 
               POCN.Id_Producto IN ('.$result['Id_Producto'].')';

            break;
        }
        case "Medicamento":{
            $modulo=$result['CantidadTotal']%$result['Cantidad_Presentacion'];
            $temporal=$result['Cantidad_Presentacion']/2; //
            if($modulo>$temporal){
                $cantidad_final=$result['Cantidad_Presentacion']-$modulo;
                $resultado[$i]['CantidadTotal']=$result['CantidadTotal']+$cantidad_final;
                
            }else{
                $resultado[$i]['CantidadTotal']=$result['CantidadTotal']-$modulo;
               
            }
            if($result['CantidadActual']<0){
                $resultado[$i]['CantidadActual']=0;
            }
             $query1 = 'SELECT 
                    P.Id_Proveedor as Id_Proveedor,P.Nombre as NombreProveedor, SUM(PAR.Cantidad) as Cantidad , PAR.Precio as Total, AR.Codigo as Codigo , AR.Fecha_Creacion as Fecha, 
                    IFNULL(CONCAT( Pr.Principio_Activo, " ", Pr.Presentacion, " ", Pr.Concentracion, " (", Pr.Nombre_Comercial,") ", Pr.Cantidad," ", Pr.Unidad_Medida, " " ), Pr.Nombre_Comercial) as nombre,
                    Pr.Cantidad_Minima as Cantidad_Minima,
                    Pr.Cantidad_Maxima as Cantidad_Maxima,
                    false as "DivMensaje",
                    true as "DivEncabezado", Pr.Id_Producto
                    FROM Acta_Recepcion AR 
                    INNER JOIN Producto_Acta_Recepcion PAR
                        ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion 
                    INNER JOIN Proveedor P 
                        ON P.Id_Proveedor = AR.Id_Proveedor 
                    INNER JOIN Producto Pr 
                        ON PAR.Id_Producto = Pr.Id_Producto 
               WHERE 
               PAR.Id_Producto IN ('.$result['Id_Producto'].')
               GROUP BY AR.Id_Acta_Recepcion ';
            break;
        }
    }
               

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query1);
    $resultado1 = $oCon->getData();
    unset($oCon);
    if($resultado1 == null){
        $resultado[$i]['Compras'] = $mensaje;
    }else{
        $resultado[$i]['Compras'] = $resultado1; //resultado query
    }
    
  
    
}

echo json_encode($resultado);

?>          
          