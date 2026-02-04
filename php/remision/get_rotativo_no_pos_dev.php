<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();


$punto_dispensacion = (isset($_REQUEST['id_destino']) ? $_REQUEST['id_destino'] : '');
$fecha_inicio = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '');
$fecha_fin = (isset($_REQUEST['ffin']) ? $_REQUEST['ffin'] : '');
$bodega = (isset($_REQUEST['id_origen']) ? $_REQUEST['id_origen'] : '');
$eps = (isset($_REQUEST['eps']) ? $_REQUEST['eps'] : '');



$hoy=date("Y-m-t", strtotime(date('Y-m-d')));
$nuevafecha = strtotime ( '+ 1 months' , strtotime ( $hoy) ) ;
$nuevafecha= date('Y-m-t', $nuevafecha);

$condicion=SetCondiciones();

$condicion_lotes=SetCondicionLotes(); 

$query=CrearQuery();

$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');



$j = - 1;
 foreach ($productos as $producto) {
    $j++;
    if ($producto["Id_Categoria"] != '') {
        //Busco los lotes de inventario de los productos
      
        $productos[$j]['Cantidad_Requerida']=ValidarRotacion($producto);
       
        $lotes=GetLotes($producto);
                
        if (count($lotes)>0) {  
                 
            $cantidad_presentacion=$producto['Cantidad_Presentacion'];
            $cantidad=$productos[$j]['Cantidad_Requerida'];
            $modulocantidad=$cantidad%$cantidad_presentacion;
            if($modulocantidad!=0){
                $cantidad=$cantidad+($cantidad_presentacion-$modulocantidad);
                $productos[$j]['Cantidad_Requerida']=$cantidad; 
               
            }
            

            $cantidad_inicial= $productos[$j]['Cantidad_Requerida'];
            $productos[$j]['Lotes']=$lotes;


            $multiplo=0;
            $cantidad_presentacion_producto=false;

            if($bodega !=6 || $bodega !=7 || $bodega !=8 || $bodega !=9 ){
                $multiplo=$cantidad%$cantidad_presentacion;
                $cantidad_presentacion_producto=true;

            }

            $lotes_seleccionados=[];
            $lotes_visuales=[];

            if($multiplo==0 && $cantidad>0){
                $flag=true;

                for ($i=0; $i <count($lotes) ; $i++) { 
                   
                    if($flag && $cantidad<=$lotes[$i]['Cantidad']){
                        $lote=$lotes[$i];
                        $lote['Cantidad_Seleccionada']=$cantidad;

                        #metodo de seleccionar los lotes
                        //SelecionarLotes($lote);
                        
                        $lotes[$i]['Cantidad_Seleccionda']=$cantidad;
                        $labellote="Lote: ".$lotes[$i]['Lote']." - Vencimiento: ".$lotes[$i]['Fecha_Vencimiento']." - Cantidad: ".$cantidad;

                        $productos[$j]['Cantidad']=$cantidad_inicial;

                        array_push($lotes_visuales,$labellote);
                        array_push($lotes_seleccionados,$lote);
                        $flag=false;
                    }elseif ($flag && $cantidad>$lotes[$i]['Cantidad']){
                        $lote=$lotes[$i];
                        $lote['Cantidad_Seleccionada']=$lotes[$i]['Cantidad'];

                        #metodo de seleccionar los lotes
                        //SelecionarLotes($lote);


                        array_push($lotes_seleccionados,$lote);

                        $labellote="Lote: ".$lotes[$i]['Lote']." - Vencimiento: ".$lotes[$i]['Fecha_Vencimiento']." - Cantidad: ".$lotes[$i]['Cantidad'];

                        $productos[$j]['Cantidad']=$productos[$j]['Cantidad']+$lotes[$i]['Cantidad'];

                        $cantidad=$cantidad-(INT)$lotes[$i]['Cantidad'];

                        if($cantidad_presentacion_producto){
                            $modulo=$cantidad%$cantidad_presentacion;
                            if($modulo!=0){
                                $productos[$j]['Cantidad_Requerida']=$productos[$j]['Cantidad_Requerida']+($cantidad_presentacion-$modulo);
                                $cantidad=$cantidad+($cantidad_presentacion-$modulo);                                
                            }
                        }
                        array_push($lotes_visuales,$labellote);

                    }

                }

                $productos[$j]['Lotes_Visuales']=$lotes_visuales;
                $productos[$j]['Lotes_Seleccionados']=$lotes_seleccionados;
            }else{
                unset($productos[$j]);
            }



            
        } else {

            $similares= GetSimilares($producto);
            if(!$similares){
                unset($productos[$j]);

            }else{
               
                $productossimilares=GetLotesProductosimilares($similares,$producto["Cantidad"]);
                if(count($productossimilares)==0){
                    unset($productos[$j]);
                }else{
                    $productos[$j]["Similares"] = $productossimilares;
                }
            }  
        }
    }else {
        unset($productos[$j]);
    }
} 

$productos = array_values($productos);


echo json_encode($productos);


function CrearQuery(){

    global $condicion,$punto_dispensacion,$bodega;
   
    


    $query='SELECT
    *
    FROM
    (
        SELECT
    (SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria) as Categoria, 
        
         PRD.Id_Producto, PRD.Id_Categoria,
       
        PRD.Embalaje,
    
        CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, 
    
        PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,
    
        PRD.Codigo_Cum, PRD.Cantidad_Presentacion, 
    
        (
            CASE
            WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
            WHEN PRD.Gravado="No"  THEN 0
            END
                ) as Impuesto,
        
         SUM(PR.Cantidad_Formulada-PR.Cantidad_Entregada) as Cantidad_Requerida,        
         0 as Cantidad 
        
    
    
        FROM Producto_Dispensacion PR
        INNER JOIN (SELECT A.Id_Dispensacion FROM Auditoria A  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND A.Punto_Pre_Auditoria='.$punto_dispensacion.') A

        ON PR.Id_Dispensacion=A.Id_Dispensacion

        INNER JOIN (SELECT Id_Dispensacion, Numero_Documento, Fecha_Actual,Id_Punto_Dispensacion,EStado_Dispensacion  FROM Dispensacion WHERE Estado_Dispensacion!="Anulada"  ) D ON PR.Id_Dispensacion=D.Id_Dispensacion

        INNER JOIN (SELECT Id_Paciente, EPS,Nit  FROM Paciente   ) PA ON D.Numero_Documento=PA.Id_Paciente          
        INNER JOIN Producto PRD
        ON PR.Id_Producto = PRD.Id_Producto  
         '.$condicion.' 
        GROUP BY  PR.Id_Producto 
        HAVING Cantidad_Requerida >0
        ORDER BY Nombre_Comercial
    ) r
    INNER JOIN (
    SELECT
        Id_Producto,
        ROUND(AVG(Costo)) AS Precio,
        SUM(Cantidad-(Cantidad_Apartada+Cantidad_Seleccionada)) AS Cantidad_Disponible
        FROM
        Inventario
        WHERE Id_Bodega = '.$bodega.' 
        GROUP BY Id_Producto
    ) I ON r.Id_Producto = I.Id_Producto';
 
 



    return $query;
}



function CalcularModulo($presentacion,$cantidad){
    $modulo=$cantidad%$presentacion;
    if($modulo!=0){
        $cantidad=$cantidad-$modulo;
    }
    return $cantidad;
}


function SetCondiciones(){
    global $bodega,$eps;

    $condicion='  WHERE D.Estado_Dispensacion!="Anulada" ';

    if($bodega!=2){
        $condicion .= ' AND PRD.Id_Categoria NOT IN (6,2)';
    }else{
        $condicion .= ' AND PRD.Id_Categoria  IN (6,2) ';
    }

    if($eps!=''){
        $condicion .= ' AND PA.Nit="'.$eps.'"';
    }
    return $condicion;
    
}

function SetCondicionLotes(){

    global $bodega,$nuevafecha;

    if( $bodega =='6' || $bodega =='8' || $bodega =='9'  ){
        $condicion_principal=" WHERE Id_Bodega=$bodega ";
    }else{
        $condicion_principal=" WHERE Id_Bodega=$bodega AND I.Fecha_Vencimiento>='$nuevafecha' ";
    }

    return $condicion_principal;

}

function GetLotes($producto){
    global  $queryObj,$condicion_lotes;
    $having="  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
   
   
    $query1="SELECT I.Id_Inventario, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,I.Fecha_Vencimiento,$producto[Precio] as Precio, 0 as Cantidad_Seleccionada FROM Inventario I 
    ".$condicion_lotes." AND I.Id_Producto= $producto[Id_Producto] ". $having ;

    $queryObj->SetQuery($query1);
    $lotes=$queryObj->ExecuteQuery('Multiple');

      return $lotes;
   
}

function ValidarRotacion($producto){
    $cantidad=$producto['Cantidad_Requerida']-$producto['Cantidad_Inventario'];

    if($cantidad<0){
        $cantidad=0;
    }


    return $cantidad;

}

function GetSimilares($producto){
   
    global $queryObj;

    $query="SELECT Producto_Asociado FROM Producto_Asociado WHERE (Producto_Asociado LIKE '".$producto['Id_Producto'].','."%' OR Producto_Asociado LIKE '%, ".$producto['Id_Producto'].','."%' OR Producto_Asociado LIKE '%, ".$producto['Id_Producto']."') ";


    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');

  
    return $productos;
}

function SelecionarLotes($lote){
    global $queryObj;

    $query="SELECT Cantidad_Seleccionada FROM Inventario WHERE Id_Inventario =$lote[Id_Inventario]";
    $queryObj->SetQuery($query);
    $cantidad_seleccionada_inventario = $queryObj->ExecuteQuery('simple');
    $cantidad_total=$lote['Cantidad_Seleccionada']+$cantidad_seleccionada_inventario['Cantidad_Seleccionada'];

    $oItem=new complex ("Inventario","Id_Inventario",$lote['Id_Inventario']);
    $oItem->Cantidad_Seleccionada=number_format($cantidad_total,0,"","");
   //$oItem->save();
    unset($oItem);

}

function GetLotesProductosimilares($productos){

    global $bodega,$nuevafecha,$queryObj;

    $query = 'SELECT SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible,P.Nombre_Comercial,
    CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre, P.Id_Producto, 0 as Seleccionado
    FROM Inventario I 
    INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
    WHERE I.Id_Producto IN (' .$productos['Producto_Asociado']. ')
    AND I.Id_Bodega = ' . $bodega . ' AND  I.Fecha_Vencimiento>"'.$nuevafecha.'"
    GROUP BY I.Id_Producto
    HAVING Cantidad_Disponible > 0 ';

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

   

    return $productos;
}





?>