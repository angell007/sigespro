<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/*
header('Content-Type: application/vnd.ms-excel');

header('Content-Disposition: attachment;filename="Reporte_'.$_REQUEST['tipo'].'.xls"');
header('Cache-Control: max-age=0'); */

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

if(isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != ""){
    $tipo=$_REQUEST['tipo'];
    $query=CrearQuery($tipo);
    ArmarReporte($query);
}


function ArmarReporte($query){
    global $tipo;
    $validar_codigos = ValidarCodigos($tipo);
    $encabezado=GetEncabezado($query);

    $datos=GetDatos($query);
   /*  echo '<pre>';
    var_dump($datos);exit;
    echo '</pre>'; */
    $contenido = '';
    
    if ($encabezado) {
        $valores_encabezado = array_keys($encabezado);
        $contenido .= '<table border="1"><tr>';
        foreach ($encabezado as $key => $value) {
          $contenido.='<th>'.$key.'</th>';
        }
        $contenido .= '</tr>';
    }

    if ($datos) {
   
        foreach ($datos as $i => $dato) {
             //VALOR NUMERICO DEL CODIGO
             if ($i!=0 &&  $validar_codigos) {
                 $numero_actual = preg_replace('/[^0-9]/',"", $dato['Codigo']);
                 //PREFIJO DEL CODIGO
                 $prefijo_cod = str_replace($numero_actual,"", $dato['Codigo']);

                 $numero_anterior = preg_replace('/[^0-9]/',"",$datos[$i-1]['Codigo']);     
                 $prefijo_anterior_cod = str_replace($numero_anterior,"", $datos[$i-1]['Codigo']);
               
                
                 if ($prefijo_anterior_cod == $prefijo_cod ) {
                     # code...
                     //VALIDAR SI EXISTEN CONSECUTIVOS SALTADO
                      while ( ($numero_actual - $numero_anterior  > 1 ) ) {
                         $numero_anterior++; 
                         $contenido .= '<tr style="background-color:red">';
                         $contenido.= '<td>'.$prefijo_cod.$numero_anterior.'</td>';
                         for ( $col = 1; $col < count($valores_encabezado); $col++ ) { 
                             # code...
                             if(ValidarKey($valores_encabezado[$col]) ){
                                 $contenido.= '<td> 0 </td>';
                             }else{
                                 
                                 if($valores_encabezado[$col]=='Tipo_Servicio'){
                                     $contenido.= '<td> '. $dato['Tipo_Servicio'] .'  </td>';
                                 }else if($valores_encabezado[$col]=='Estado'){
                                     $contenido.= '<td>Anulada</td>';
                                 }else{
                                     
                                     $contenido.= '<td>   </td>';
                                 }
                             }
                         } 
                         $contenido .= '</tr>';
                     }  
                 }

             }  
           

             $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {   

                if(ValidarKey($key) ){
                    $valor = $dato[$key] != '' ? $dato[$key] : 0;
                    try {
                        //code...
                        $valor = (float)$valor;
                        $contenido.= '<td>' . number_format($valor,2,",","") . '</td>';
                    } catch (\Throwable $th) {
                        //throw $th;
                        var_dump($key);exit;
                    }
                  
                  
                }else{
                    $contenido.= '<td>' . $dato[$key] . '</td>';
                }       

            }    
           
            $contenido .= '</tr>'; 
        }
     /*    exit; */
     $contenido .= '</table>';
    }

    if ($contenido == '') {
        $contenido .= '
            <table>
                <tr>
                    <td>NO EXISTE INFORMACION PARA MOSTRAR</td>
                </tr>
            </table>
        ';
    }

 echo $contenido;

}
function GetEncabezado($query){
    $oCon= new consulta();
    $oCon->setQuery($query);
    $encabezado= $oCon->getData();
    unset($oCon);

    return $encabezado;
}

function GetDatos($query){
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos= $oCon->getData();
    unset($oCon);
    return $datos;
}
function CrearQuery($tipo){

    $condicion_nit = '';
    if ($_REQUEST['nit'] && $_REQUEST['nit'] != '') {
        if ($tipo == 'DevolucionC') {
            $condicion_nit .= " AND NC.Id_Proveedor=$_REQUEST[nit]";
        } elseif ($tipo == 'Acta_Compra') {
            $condicion_nit .= " AND P.Id_Proveedor=$_REQUEST[nit]";
        } elseif($tipo == 'Reporte_Nacionalizacion'){
            $condicion_nit .= " AND OCI.Id_Proveedor=$_REQUEST[nit]";
        } elseif($tipo == 'Dispensacion'){
            $condicion_nit .= " HAVING Nit_Cobrar=$_REQUEST[nit]";
        } else {
            $condicion_nit .= " AND F.Id_Cliente=$_REQUEST[nit]";
        }
    }
    
    
    switch ($tipo) {
        case 'Inventario_Valorizado':
            $query="SELECT
            #P.Id_Producto,
                    (
                    CASE
                        WHEN SIK.Id_Bodega != 0 THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega = SIK.Id_Bodega)
                        WHEN SIK.Id_Punto_Dispensacion != 0 THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = SIK.Id_Punto_Dispensacion)
                    END
                    ) AS 'Punto/Bodega',
                    P.Codigo_Cum, 
                    P.Nombre_Comercial AS Producto, 
                    CAST(SUM(SIK.Cantidad) AS SIGNED) AS Cantidad, 
                    CAST(I.Costo AS UNSIGNED) AS Costo, 
                    CAST((SUM(SIK.Cantidad) * I.Costo) AS UNSIGNED) AS Valorizado
                    FROM Saldo_Inicial_Kardex SIK 
                    INNER JOIN Producto P ON SIK.Id_Producto = P.Id_Producto 
                    INNER JOIN (SELECT AVG(Costo) AS Costo, Id_Producto FROM Inventario GROUP BY Id_Producto) I ON SIK.Id_Producto = I.Id_Producto WHERE SIK.Fecha = '$_REQUEST[fecha]' AND I.Costo != 0 AND I.Id_Producto != 1 GROUP BY SIK.Id_Producto";
                break;
        case 'Ventas':
                $fecha_inicio = '';
                $fecha_fin = '';
                if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                    $fecha_inicio = $_REQUEST['fini'];
                }
                if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                    $fecha_fin = $_REQUEST['ffin'];
                }
            $query='SELECT 
                        F.Codigo as Factura, F.Codigo,F.Id_Resolucion,
                        F.Fecha_Documento as Fecha_Factura, 
                        
                        IFNULL(F.Id_Cliente,F.Id_Cliente2) as NIT_Cliente, 
                        
                        IFNULL(C.Nombre,C2.Nombre) AS Nombre_Cliente,
                                        
                        IFNULL(Z.Nombre,Z2.Nombre) AS Zona_Comercial,

                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM(IF(PFV.Impuesto!=0 ,(PFV.Cantidad*PFV.Precio_Venta),0 )) 
                            END	  
                        ) AS Gravada,	

                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                IFNULL(SUM(IF(PFV.Impuesto=0, PFV.Cantidad*PFV.Precio_Venta ,0) ) ,0)
                            END	  
                        )  AS Excenta,

            /*           IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                        FROM Producto_Factura_Venta PFV
                        WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto=0),0) as Excenta, */
                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM(IF( PFV.Impuesto!=0, ROUND( (PFV.Cantidad*PFV.Precio_Venta)*(PFV.Impuesto/100),2) , 0 ) )  
                            END	  
                        )  AS Iva,
                                
                        0 AS Descuentos_Gravados,
                        
                        0 AS Descuentos_Excentos,

                        0 AS Cuota_Moderadora,

                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM(IFNULL( PFV.Cantidad*PFV.Precio_Venta , 0) )
                            END	  
                        )   AS Total_Venta, 

                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM(IFNULL( ( PFV.Cantidad*PFV.Precio_Venta ) + ROUND( (PFV.Cantidad*PFV.Precio_Venta)*(PFV.Impuesto/100) ,2 ) , 0 ))
                            END	  
                        )   AS Neto_Factura,


                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM(IF( PFV.Impuesto=0,  IFNULL( COALESCE( IF( PR.Costo != NULL AND PR.Costo != 0 , PR.Costo , NULL), CP.Costo_Promedio, 0) *PFV.Cantidad,0) , 0 ) )
                            END	  
                        )   AS Costo_Venta_Exenta, 
                                            
                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM(IF( PFV.Impuesto!=0,  IFNULL( COALESCE( IF( PR.Costo != NULL AND PR.Costo != 0 , PR.Costo , NULL), CP.Costo_Promedio, 0)*PFV.Cantidad,0) , 0 ) ) 
                            END	  
                        )  AS Costo_Venta_Gravada, 
                        

                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    (
                                        (ROUND(SUM(IF( PFV.Impuesto=0, IFNULL( COALESCE( IF( PR.Costo != NULL AND PR.Costo != 0 , PR.Costo , NULL), CP.Costo_Promedio, 0)*PFV.Cantidad,0), 0 ))) ) + 
                                        (ROUND(SUM(IF( PFV.Impuesto!=0, IFNULL( COALESCE( IF( PR.Costo != NULL AND PR.Costo != 0 , PR.Costo , NULL), CP.Costo_Promedio, 0)*PFV.Cantidad,0), 0 ))) ) 
                                    )
                                END	  
                        )   AS Total_Costo_Venta,

                        (							
                            CASE F.Estado							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    ROUND( 
                                            (    
                                                ( ( SUM(IFNULL(PFV.Cantidad*PFV.Precio_Venta,0))  
                                                    - SUM( IFNULL( COALESCE( IF( PR.Costo != NULL AND PR.Costo != 0 , PR.Costo , NULL), CP.Costo_Promedio, 0) * PFV.Cantidad,0)) )
                                                / ( SUM(IFNULL(PFV.Cantidad*PFV.Precio_Venta,0)) ) ) * 100
                                            ) 
                                            ,2
                                        ) 
                                END	  
                        )  AS Rentabilidad,
                        
                        F.Estado,

                        "Comercial" AS Tipo_Servicio,
                        
                        "" AS Punto,
                        "" AS Ciudad,

                        R.Codigo AS Prefijo
                        
                        FROM Factura_Venta F

                        INNER JOIN Producto_Factura_Venta PFV 
                        ON PFV.Id_Factura_Venta = F.Id_Factura_Venta

                        INNER JOIN Producto P 
                        ON P.Id_Producto = PFV.Id_Producto

                        /* LEFT JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario_Viejo WHERE Id_Bodega != 0 GROUP BY Id_Producto) I 
                        ON PFV.Id_Producto = I.Id_Producto */
                        
                        LEFT JOIN (SELECT PRE.Costo, PRE.Id_Remision, PRE.Id_Producto FROM 
                                Producto_Remision PRE
                                LIMIT 1
                                ) PR ON PR.Id_Remision = PFV.Id_Remision AND PR.Id_Producto = PFV.Id_Producto
                    
                        LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = PFV.Id_Producto


                        LEFT JOIN Cliente C
                        ON C.Id_Cliente = F.Id_Cliente
                    
                        LEFT JOIN Cliente C2 
                        ON C2.Id_Cliente = F.Id_Cliente2
                    
                        LEFT JOIN Zona Z
                        ON Z.Id_Zona = C.Id_Zona
                        
                        LEFT JOIN Zona Z2
                        ON Z2.Id_Zona = C2.Id_Zona

                        INNER JOIN Resolucion R ON 
                        R.Id_Resolucion = F.Id_Resolucion 
                        
                        WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'") '.$condicion_nit.'
                        GROUP BY F.Id_Factura_Venta
                        
                        UNION ALL
                        
                        (
                            SELECT
                            F.Codigo AS Factura, F.Codigo,F.Id_Resolucion,
                            F.Fecha_Documento AS Fecha_Factura,
                            F.Id_Cliente AS NIT_Cliente,
                            C.Nombre AS Nombre_Cliente,
                            Z.Nombre as Zona_Comercial,
                        
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        SUM(IF(PF.Impuesto!=0 ,(PF.Cantidad*PF.Precio),0 )) 
                                END	  
                            ) AS Gravada,	
                        
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        SUM(IF(PF.Impuesto=0, (PF.Cantidad*PF.Precio) ,0)) 
                                END	  
                            )  AS Excenta,

                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        SUM(IF( 
                                                PF.Impuesto!=0,
                                                ROUND( 
                                                    IFNULL( 
                                                            ( 
                                                                (PF.Cantidad*PF.Precio)
                                                                -(PF.Cantidad*PF.Descuento) 
                                                            )
                                                            *(PF.Impuesto/100) 
                                                    , 0)
                                                ,2)
                                                ,0 
                                                )
                                            )  
                                END	  
                            )  AS Iva,

                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        ROUND(
                                            SUM(IF( 
                                                PF.Impuesto!=0, 
                                                IFNULL( PF.Cantidad*PF.Descuento , 0) 
                                                , 0 )
                                            )
                                        ,2)  
                                END	  
                            )  AS Descuentos_Gravados,
    
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        ROUND(
                                            SUM(IF( 
                                            PF.Impuesto=0, 
                                            IFNULL( PF.Cantidad*PF.Descuento , 0) 
                                            , 0 )
                                        )
                                        ,2)    
                                END	  
                            )  AS Descuentos_Excentos,
                            
                
                            IF(F.Estado_Factura = "Anulada",0,F.Cuota) AS Cuota_Moderadora,

                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        ROUND( 
                                            SUM(
                                                IFNULL( (PF.Cantidad*PF.Precio)-(PF.Cantidad*PF.Descuento)  , 0)
                                            )  
                                        ,2)
                                END	  
                            )  AS Total_Venta,
                        
                            (		
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    (SUM(  IFNULL(
                                            ( PF.Cantidad * PF.Precio - ( PF.Cantidad * PF.Descuento) ) + 
                                                ROUND( (PF.Cantidad * PF.Precio - ( PF.Cantidad * PF.Descuento ) )  *  (PF.Impuesto/100), 2) 
                                                
                                            ,0)
                                            ))- F.Cuota
                                END							
                            ) as Neto_Factura, 		
        
                            /* (
                                (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio) FROM Producto_Factura PF WHERE PF.Id_Factura = F.Id_Factura),0)) + 
                                IFNULL((SELECT ROUND(SUM(((PF.Cantidad*PF.Precio)-(PF.Cantidad*PF.Descuento))*(PF.Impuesto/100)),2) FROM Producto_Factura PF WHERE PF.Id_Factura = F.Id_Factura),0) - 
                                (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento) FROM Producto_Factura PF WHERE PF.Id_Factura = F.Id_Factura),0) + F.Cuota)
                            ) AS Neto_Factura, */
                            
                            
                        
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        SUM(IF( PF.Impuesto=0 ,
                                            IFNULL( 
                                            COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), CP.Costo_Promedio, 0)
                                            *PF.Cantidad,0) 
                                        , 0 ) ) 
                                END	  
                            )   AS Costo_Venta_Exenta, 
                                        
                            (
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        SUM(IF( PF.Impuesto!=0 ,
                                                IFNULL( 
                                                    COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), CP.Costo_Promedio, 0)
                                                    *PF.Cantidad,0) 
                                                , 0 ) ) 
                                END	  
                            )  AS Costo_Venta_Gravada, 
                                                
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        (
                                            (ROUND(SUM(IF( PF.Impuesto=0, IFNULL(
                                                COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), CP.Costo_Promedio, 0)                                                               
                                                *PF.Cantidad,0), 0 )),2) ) + 
                                            (ROUND(SUM(IF( PF.Impuesto!=0, IFNULL(
                                                COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), CP.Costo_Promedio, 0)                                            
                                                *PF.Cantidad,0), 0 )),2) ) 
                                        )
                                    END	  
                            )   AS Total_Costo_Venta,
                            
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                        ROUND( 
                                            (    
                                                SUM(
                                                    IFNULL(
                                                    (
                                                            ( 
                                                                (  (PF.Cantidad*PF.Precio) - (PF.Cantidad*PF.Descuento)  )
                                                                - ( 
                                                                    COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), CP.Costo_Promedio, 0)
                                                                    *PF.Cantidad 
                                                                ) 
                                                            )   
                                                            / ( (PF.Cantidad*PF.Precio) - (PF.Cantidad*PF.Descuento)  ) 
                                                        )
                                                    , 0)
                                                )  * 100
                                            ) 
                                        ,2  ) 
                                    END	  
                            )  AS Rentabilidad,

                        /*  ROUND(((((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                            
                            FROM Producto_Factura PF
                            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                            FROM Producto_Factura PF
                            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) - (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
                            FROM Producto_Factura PF
                            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0))) - ((SELECT IFNULL((SUM(I.Costo*PF.Cantidad)),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario_Viejo WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = "No") + (SELECT IFNULL((SUM(I.Costo*PF.Cantidad)),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario_Viejo WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = "Si"))) / (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                            FROM Producto_Factura PF
                            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                            FROM Producto_Factura PF
                            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) - (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
                            FROM Producto_Factura PF
                            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)))) * 100),2) AS Rentabilidad,
                            */
                            F.Estado_Factura AS Estado, 

                            IFNULL(D.Tipo_Servicio,D2.Tipo_Servicio) AS Tipo_Servicio,
                            IFNULL(D.Punto_Dis,D2.Punto_Dis) AS Punto,
                            IFNULL(D.Municipio,D2.Municipio) AS Ciudad ,
                            R.Codigo AS Prefijo
                        
                            
                        
                            FROM Factura F
                            INNER JOIN Producto_Factura PF 
                            ON PF.Id_Factura = F.Id_Factura

                            INNER JOIN Producto_Dispensacion PD 
                            ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion

                            LEFT JOIN Costo_Promedio CP 
                            ON CP.Id_Producto = PF.Id_Producto

                            INNER JOIN Producto P 
                            ON P.Id_Producto = PF.Id_Producto

                            /* LEFT JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario_Viejo WHERE Id_Bodega != 0 GROUP BY Id_Producto) I 
                            ON PF.Id_Producto = I.Id_Producto */
                        
                            LEFT JOIN (SELECT T.Id_Dispensacion, T.Tipo, PD.Nombre AS Punto_Dis, M.Nombre as Municipio, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = T.Id_Tipo_Servicio) AS Tipo_Servicio FROM Dispensacion T INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = T.Id_Punto_Dispensacion INNER JOIN Municipio M ON M.Id_Municipio=PD.Municipio WHERE Id_Tipo_Servicio != 7 ) D ON D.Id_Dispensacion = F.Id_Dispensacion
                            LEFT JOIN (SELECT T.Id_Dispensacion, T.Tipo, PD.Nombre AS Punto_Dis, M.Nombre as Municipio, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = T.Id_Tipo_Servicio) AS Tipo_Servicio FROM Dispensacion T INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = T.Id_Punto_Dispensacion INNER JOIN Municipio M ON M.Id_Municipio=PD.Municipio WHERE Id_Tipo_Servicio != 7 ) D2 ON D2.Id_Dispensacion = F.Id_Dispensacion2
                            INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
                            INNER JOIN Zona Z ON Z.Id_Zona = C.Id_Zona
                            INNER JOIN Resolucion R ON 
                            R.Id_Resolucion = F.Id_Resolucion 
                            WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") '.$condicion_nit.'
                            GROUP BY F.Id_Factura
                        )
                        
                        UNION ALL
                        
                        (SELECT
                        F.Codigo AS Factura, F.Codigo, F.Id_Resolucion,
                        F.Fecha_Documento as Fecha_Factura, 
                        F.Id_Cliente, 
                        C.Nombre as Razon_Social,
                        Z.Nombre as Zona_Comercial, 
                        0 AS Gravado, 
                        
                        IF(F.Estado_Factura = "Anulada",0, (DFC.Cantidad * DFC.Precio) )  AS Excento,


                        0 AS Iva, 0 AS Descuentos_Gravados, 0 AS Descuentos_Excentos,
                        #F.Cuota_Moderadora,
                        IF(F.Estado_Factura = "Anulada",0,F.Cuota_Moderadora) AS Cuota_Moderadora,
                        
                        IF(F.Estado_Factura = "Anulada",0, ((DFC.Cantidad * DFC.Precio)) ) AS Total_Factura,
                        
                        
                        IF(F.Estado_Factura = "Anulada",0, ((DFC.Cantidad * DFC.Precio)-F.Cuota_Moderadora) ) AS Neto_Factura,
                        
                        0 AS Costo_Venta_Exenta,
                        0 AS Costo_Venta_Gravada,
                        0 AS Total_Costo_Venta,
                        0 AS Rentabilidad,
                        F.Estado_Factura AS Estado,
                        "Capita" AS Tipo_Servicio,
                        "" AS Punto,
                        "" AS Ciudad,
                        R.Codigo AS Prefijo
                        FROM
                        Descripcion_Factura_Capita DFC
                        INNER JOIN Factura_Capita F ON DFC.Id_Factura_Capita = F.Id_Factura_Capita
                        
                        INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
                        INNER JOIN Zona Z
                        ON Z.Id_Zona = C.Id_Zona
                        INNER JOIN Resolucion R ON 
                        R.Id_Resolucion = F.Id_Resolucion 
                        WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'") '.$condicion_nit.'
                        )

                        UNION ALL (
                            SELECT							
                                
                            F.Codigo AS Factura, F.Codigo, F.Id_Resolucion,							
                            F.Fecha as Fecha_Factura, 							
                            F.Id_Cliente, 							
                            ( 
                                CASE F.Tipo_Cliente							
                                    WHEN  "Cliente"  							
                                        THEN (SELECT C.Razon_Social From Cliente C WHERE C.Id_Cliente = F.Id_Cliente )							
                                    WHEN  "Proveedor" 							
                                        THEN (SELECT COALESCE( P.Nombre, CONCAT(P.Primer_Nombre, " ",P.Primer_Apellido )  )
                                            From Proveedor P WHERE P.Id_Proveedor = F.Id_Cliente )  		
                                    ELSE IFNULL( (SELECT  CONCAT(P.Nombres, " ",P.Apellidos ) From 
                                            Funcionario P WHERE P.Identificacion_Funcionario = F.Id_Cliente), "SIN INFORMACIÓN"  )			
                                END 
                            ) AS Razon_Social,							
                                                        
                        /*   IF(F.Tipo_Cliente="Cliente" ,(SELECT Z.Nombre FROM Zona Z
                                                        INNER JOIN Cliente CL ON CL.Id_Cliente = F.Id_Cliente
                                                        WHERE Z.Id_Zona = CL.Id_Zona ), " " ) as Zona_Comercial, 	*/
                            "" AS Zonas_Comercial,
                            (							
                                CASE F.Estado_Factura							
                                    WHEN "Anulada" THEN 0							
                                    ELSE							
                                    SUM( IF(DFA.Impuesto>0 , (  DFA.Cantidad * DFA.Precio ), 0 ) ) 
                                END	  
                            ) as Gravada,					
                            
                            (							
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM( IF(DFA.Impuesto=0 , (  DFA.Cantidad * DFA.Precio ), 0 ) )
                                END							
                            ) as Excenta,					
                                    
                            (							
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    ROUND(SUM( (DFA.Cantidad*DFA.Precio)*(DFA.Impuesto/100) ),2)
                                END							
                            ) as Iva,					
                                                                        
                            (							
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    ROUND(
                                        SUM(IF( 
                                        DFA.Impuesto!=0, 
                                        IFNULL( DFA.Cantidad*DFA.Descuento , 0) 
                                        , 0 )
                                        )
                                    ,2)  
                                END							
                            ) as Descuentos_Gravados,		
                            
                            (							
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    ROUND(SUM( IF(DFA.Impuesto=0 , (  DFA.Cantidad * DFA.Descuento ), 0 ) ) ,2)
                                END							
                            ) as Descuentos_Excentos,		
                                                                            
                            0 AS Cuota_Moderadora,     							
                                                
                            (							
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    ROUND( 
                                        SUM(
                                            IFNULL( (DFA.Cantidad*DFA.Precio)-(DFA.Cantidad*DFA.Descuento)  , 0)
                                            )  
                                    ,2)
                                END							
                            ) as Total_Venta,				
                                                        
                            (		
                                CASE F.Estado_Factura							
                                WHEN "Anulada" THEN 0							
                                ELSE							
                                    SUM( ( DFA.Cantidad * DFA.Precio - ( DFA.Cantidad * DFA.Descuento) ) + 
                                            ROUND( (DFA.Cantidad * DFA.Precio - ( DFA.Cantidad * DFA.Descuento) )  *  (DFA.Impuesto/100), 2) )
                                END							
                            ) as Neto_Factura, 					
                                        
                                        
                            0 AS Costo_Venta_Exenta,			
                            0 AS Costo_Venta_Gravada,             							
                            0 AS Total_Costo_Venta,							
                            100 AS Rentabilidad,							
                            F.Estado_Factura AS Estado,							
                            "Administrativa" AS Tipo_Servicio,							
                            "" AS Punto,							
                            "" AS Ciudad,							
                            R.Codigo AS Prefijo							
                                    
                            FROM							
                            Descripcion_Factura_Administrativa DFA							
                            INNER JOIN Factura_Administrativa F ON F.Id_Factura_Administrativa = DFA.Id_Factura_Administrativa							
                            INNER JOIN Resolucion R ON 							
                            R.Id_Resolucion = F.Id_Resolucion							
                            WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'") '.$condicion_nit.'						
                            GROUP BY F.Id_Factura_Administrativa

                        )
                        
                        ORDER BY Id_Resolucion, CONVERT(SUBSTRING(
                                                            Codigo 
                                                            , IF(Prefijo != "0" , LENGTH(Prefijo) + 1 , 0)  ),UNSIGNED INTEGER) ';
                    
                break;
        case 'DevolucionV':
                $fecha_inicio = '';
                $fecha_fin = '';
                if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                    $fecha_inicio = $_REQUEST['fini'];
                }
                if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                    $fecha_fin = $_REQUEST['ffin'];
                }

                $condicion_nit = $condicion_nit != '' ? " AND NC.Id_Cliente = $_REQUEST[nit]" : '';

            $query="SELECT 
                        NC.Codigo AS Devolucion_Venta, NC.Codigo,
                        NC.Fecha AS Fecha_Devolucion_Venta,
                        NC.Id_Cliente AS NIT,
                        C.Nombre AS Cliente,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0							
                                ELSE
                                IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)

                            FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) 
                            END
                        ) as Gravado,
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                                IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                                FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) 
                                END
                        ) as Excento,
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                                IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Precio_Venta*(0.19)))
                                FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0)
                                END 
                        ) AS Iva,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) 
                        END
                        ) AS Total,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) + IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Precio_Venta*(0.19)))
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) 
                        END
                        ) AS Neto,
                        NC.Estado
                        FROM Nota_Credito NC
                        INNER JOIN Cliente C 
                        ON C.Id_Cliente = NC.Id_Cliente
                        WHERE (DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin') $condicion_nit
                        HAVING Neto != 0
                        ORDER BY CONVERT(SUBSTRING(NC.Codigo, 3),UNSIGNED INTEGER) ";
                break;
        case 'DevolucionC':
                $fecha_inicio = '';
                $fecha_fin = '';
                if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                    $fecha_inicio = $_REQUEST['fini'];
                }
                if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                    $fecha_fin = $_REQUEST['ffin'];
                }
                $condicion_nit = $condicion_nit != '' ? " AND NC.Id_Proveedor = $_REQUEST[nit]" : '';
            $query="SELECT 
                        NC.Codigo AS Devolucion_Compra, NC.Codigo,
                        NC.Fecha AS Fecha_Devolucion_Compra,
                        NC.Id_Proveedor AS NIT,
                        C.Nombre AS Cliente,
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) 
                                END
                        ) as Gravado,

                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE

                            IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                            FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) 
                            END
                        ) as Excento,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                            IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Costo*(0.19)))
                            FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) 
                                END
                        ) AS Iva,

                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                        IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 320),0)
                        END
                        ) AS Rte_Fuente,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                        IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 328),0) 
                        END
                        ) AS Rte_Ica,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) 
                        END
                        ) AS Total,
                        
                        (		
                                CASE NC.Estado							
                                WHEN 'Anulada' THEN 0	
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) + IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Costo*(0.19)))
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) - IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 320),0) - IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 328),0)
                        END
                        ) AS Neto 
                        FROM Devolucion_Compra NC
                        INNER JOIN Proveedor C 
                        ON C.Id_Proveedor = NC.Id_Proveedor
                        WHERE (DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin') $condicion_nit
                        ORDER BY CONVERT(SUBSTRING(NC.Codigo, 4),UNSIGNED INTEGER)";
                    
                break;

        case 'Acta_Compra':
                $fecha_inicio = '';
                $fecha_fin = '';
                if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                    $fecha_inicio = $_REQUEST['fini'];
                }
                if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                    $fecha_fin = $_REQUEST['ffin'];
                }
            $query = "
                SELECT 
                    AR.Codigo as Acta_Recepcion,  AR.Codigo,

                    DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Recepcion,


                    OCN.Id_Proveedor AS Nit,
                    P.Nombre AS Proveedor, 
                    IF(AR.Id_Bodega = 0, 'Punto Dispensacion', 'Bodega') AS Tipo,
                    FAR.Factura, 
                    FAR.Fecha_Factura,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Impuesto = 0 AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                        END
                    ) as Valor_Excento,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion  )
                        END
                    ) as Valor_Gravado,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(PAR.Impuesto/100)),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion  )
                        END
                    ) as Iva,
                    
                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                        END
                    ) AS Rte_Fuente,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                        END
                    ) AS Rte_Ica,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            ((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE  PAR.Impuesto = 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion ) )
                        END
                    ) as Total_Compra,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL(((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                             WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR 
                             WHERE PAR.Impuesto = 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            +
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                            FROM Producto_Acta_Recepcion PAR
                             WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0)
                            FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura =
                            FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) 
                            FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = 
                            FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                            ),0)
                        END
                    ) AS Neto_Factura,

                    AR.Estado
                    
                    FROM Orden_Compra_Nacional OCN
                    INNER JOIN Acta_Recepcion AR  ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional 
                    INNER JOIN Factura_Acta_Recepcion FAR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
                    INNER JOIN Proveedor P ON P.Id_Proveedor = OCN.Id_Proveedor
                    WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$fecha_inicio."' AND '".$fecha_fin."'".$condicion_nit."
                    ORDER BY CONVERT(SUBSTRING(AR.Codigo, 4),UNSIGNED INTEGER) ";
               
                break;

        case 'Reporte_Nacionalizacion':
                $fecha_inicio = '';
                $fecha_fin = '';
                if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                    $fecha_inicio = $_REQUEST['fini'];
                }
                if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                    $fecha_fin = $_REQUEST['ffin'];
                }
            $query = '
                SELECT 
                PRD.Nombre_Comercial, "" as Codigo,
                IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                    CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
                IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
                PRD.Embalaje,
                PRD.Codigo_Cum,
                IF((PRO.Primer_Nombre IS NULL OR PRO.Primer_Nombre = ""), PRO.Nombre, CONCAT_WS(" ", PRO.Primer_Nombre, PRO.Segundo_Nombre, PRO.Primer_Apellido, PRO.Segundo_Apellido)) AS Nombre_Proveedor,
                OCI.Codigo AS Codigo_Compra,
                ARI.Codigo AS Codigo_Acta,
                NP.Codigo AS Codigo_Nacionalizacion,
                NP.Fecha_Registro,
                NP.Tasa_Cambio AS Tasa,
                NP.Tramite_Sia,
                NP.Formulario,
                NP.Cargue,
                NP.Gasto_Bancario,
                NP.Descuento_Parcial AS Descuento_Arancelario,
                PNP.Total_Flete AS Flete_Internacional_USD,
                PNP.Total_Seguro AS Seguro_Internacional_USD,
                PNP.Total_Flete_Nacional,
                PNP.Total_Licencia,
                PNP.Total_Arancel,
                PNP.Total_Iva,
                PNP.Subtotal AS Subtotal_Importacion,
                (PNP.Subtotal+PNP.Total_Flete_Nacional+PNP.Total_Licencia+PNP.Total_Iva) AS Subtotal_Nacionalizacion
            FROM Nacionalizacion_Parcial NP
            INNER JOIN Producto_Nacionalizacion_Parcial PNP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial          
            INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
            INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
            INNER JOIN Producto PRD ON PNP.Id_Producto = PRD.Id_Producto
            INNER JOIN Funcionario F ON NP.Identificacion_Funcionario = F.Identificacion_Funcionario
            INNER JOIN Proveedor PRO ON OCI.Id_Proveedor = PRO.Id_Proveedor
            WHERE DATE_FORMAT(NP.Fecha_Registro, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'"'.$condicion_nit;

            break;
        case 'Dispensacion':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $query = "SELECT 
            DATE_FORMAT(Fecha_Actual, '%d/%m/%Y') AS Fecha,
            Codigo AS Dispensacion, Codigo,
            Tipo,
            IF(Tipo NOT IN ('Evento' , 'Cohortes', 'Capita'),
                (SELECT 
                        Nombre
                    FROM
                        Tipo_Servicio
                    WHERE
                        Id_Tipo_Servicio = D.Tipo_Servicio),
                '') AS Tipo_Servicio,
            EPS AS Eps_Punto_Dispensacion,
            IF(Tipo IN ('Evento','Cohortes','NoPos') AND PC.Id_Regimen = 1, PC.Nit, PT.Nit) AS Nit_Cobrar,
            IF(Tipo IN ('Evento','Cohortes','NoPos') AND PC.Id_Regimen = 1, PC.Cliente_Cobrar, PT.Cliente_Cobrar) AS Cliente_Cobrar,
            PT.Nombre AS Punto_Dispensacion,
            PD.Costo_Total,
            Estado_Facturacion,
            Estado_Dispensacion AS Estado
            FROM
            Dispensacion D
                INNER JOIN
            (SELECT 
                PD2.Id_Dispensacion,
                    SUM(I.Costo * PD2.Cantidad_Entregada) AS Costo_Total
            FROM
                Producto_Dispensacion PD2
            INNER JOIN (SELECT 
                Id_Producto, ROUND(AVG(Costo),2) AS Costo
            FROM
                Inventario_Viejo
            WHERE
                Costo > 0 AND Id_Bodega != 0
            GROUP BY Id_Producto) I ON I.Id_Producto = PD2.Id_Producto
            GROUP BY PD2.Id_Dispensacion) PD ON PD.Id_Dispensacion = D.Id_Dispensacion
            INNER JOIN (SELECT Id_Paciente, Id_Regimen, Nit, (SELECT Razon_Social FROM Cliente WHERE Id_Cliente = P.Nit) AS Cliente_Cobrar FROM Paciente P) PC ON PC.Id_Paciente = D.Numero_Documento
            INNER JOIN (SELECT Id_Punto_Dispensacion, PT2.Nombre, DC.Id_Cliente AS Nit, (SELECT Nombre FROM Cliente WHERE Id_Cliente = DC.Id_Cliente) AS Cliente_Cobrar FROM Punto_Dispensacion PT2 LEFT JOIN Departamento_Cliente DC ON PT2.Departamento = DC.Id_Departamento) PT ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
            WHERE
            DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND D.Estado_Dispensacion!='Anulada' $condicion_nit 
            
            ORDER BY CONVERT(SUBSTRING(D.Codigo, 4),UNSIGNED INTEGER) ";
            
            break;

        case 'Dispensacion_Pendientes':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            if ($_REQUEST['nit'] && $_REQUEST['nit'] != '') {
                $condicion_nit=' AND PA.Nit="'.$_REQUEST['nit'].'"';
            }
            

            $query = "SELECT 
            DATE(D.Fecha_Actual) AS Fecha_Dispensacion,
            D.Codigo,
            D.EPS,PA.Nit,
            D.Estado_Dispensacion,
            SUM( PD.Cantidad_Entregada * COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL) , I.Costo_Promedio , 0 )  ) AS Entergado_Sin_Facturar,
            SUM( (PD.Cantidad_Formulada - PD.Cantidad_Entregada) * COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), I.Costo_Promedio , 0 )  ) AS Sin_Entregar,
            (SELECT 
                    Nombre
                FROM
                    Servicio
                WHERE
                    Id_Servicio = D.Id_Servicio) AS Servicio,
            T.Nombre AS Tipo_Servicio
            FROM
                Dispensacion D
                    INNER JOIN
                Producto_Dispensacion PD ON D.Id_Dispensacion = PD.Id_Dispensacion
                    
                    INNER JOIN
                Tipo_Servicio T ON D.Id_Tipo_Servicio = T.Id_Tipo_Servicio
        
                LEFT JOIN Costo_Promedio I ON I.Id_Producto = PD.Id_Producto
                INNER JOIN ( SELECT Id_Paciente, Nit FROM Paciente ) PA ON D.Numero_Documento=PA.Id_Paciente
                
            WHERE
                DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND D.Estado_Facturacion!='Facturada'
            AND D.Estado_Dispensacion != 'Anulada' $condicion_nit GROUP BY PD.Id_Dispensacion 
            
            ORDER BY Fecha_Actual ASC";
            
            
        break;

        case 'Dispensacion_Cuotas':

            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $query = "SELECT Codigo, DATE(Fecha_Actual) AS Fecha_Solicitud, EPS, PT.Nombre AS Punto_Dispensacion, PT.Departamento, IF(Tipo NOT IN('Capita','Evento','Cohortes'),(SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Tipo_Servicio),Tipo) AS Tipo_Servicio, IF(D.Estado_Dispensacion != 'Anulada',IF(P.Id_Regimen = 1, Cuota, 0),0) AS Cuota_Moderadora, IF(D.Estado_Dispensacion != 'Anulada',IF(P.Id_Regimen = 2, Cuota, 0),0) AS Cuota_Recuperacion, D.Estado_Dispensacion AS Estado FROM Dispensacion D INNER JOIN (SELECT Id_Punto_Dispensacion, Nombre, (SELECT Nombre FROM Departamento WHERE Id_Departamento = PT2.Departamento) AS Departamento FROM Punto_Dispensacion PT2) PT ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion INNER JOIN (SELECT Id_Paciente, Id_Regimen, Nit FROM Paciente) P ON P.Id_Paciente = D.Numero_Documento WHERE DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        
            break;
        case 'Terceros':

            $query = "(SELECT Id_Proveedor AS Nit, Digito_Verificacion, Tipo AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Nombre AS Nombre_Comercial, Direccion, Telefono AS Telefono_Fijo, Celular AS Telefono_Celular, Correo, (SELECT Nombre FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Departamento, (SELECT Nombre FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Municipio, Regimen AS Tipo_Regimen, Tipo_Retencion, Animo_Lucro, Ley_1429_2010, (SELECT Descripcion FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = P.Id_Codigo_Ciiu) AS Actividad_Economica, Tipo_Reteica, Contribuyente AS Gran_Contribuyente, IF(Condicion_Pago IN (0,1), 'Contado', CONCAT(Condicion_Pago,' Días')) AS Plazo, Estado, Tipo_Tercero FROM Proveedor P)
            UNION
            (SELECT Id_Cliente AS Nit, Digito_Verificacion, Tipo AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Nombre AS Nombre_Comercial, Direccion, Telefono_Persona_Contacto AS Telefono_Fijo, Celular AS Telefono_Celular, Correo_Persona_Contacto AS Correo, (SELECT Nombre FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Departamento, (SELECT Nombre FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Municipio, Regimen AS Tipo_Regimen, '' AS Tipo_Retencion, Animo_Lucro, '' AS Ley_1429_2010, (SELECT Descripcion FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = P.Id_Codigo_Ciiu) AS Actividad_Economica, Tipo_Reteica, Contribuyente AS Gran_Contribuyente, IF(Condicion_Pago IN (0,1), 'Contado', CONCAT(Condicion_Pago,' Días')) AS Plazo, Estado, 'Cliente' FROM Cliente P)
            UNION
            (SELECT P.Identificacion_Funcionario AS Nit, '' AS Digito_Verificacion, 'Natural' AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, '' AS Razon_Social, '' AS Nombre_Comercial, Direccion_Residencia AS Direccion, Telefono AS Telefono_Fijo, Celular AS Telefono_Celular, Correo, (SELECT Nombre FROM Departamento WHERE Id_Departamento = M.Id_Departamento) AS Departamento, M.Nombre_Municipio AS Municipio, '' AS Tipo_Regimen, '' AS Tipo_Retencion, '' AS Animo_Lucro, '' AS Ley_1429_2010, '' AS Actividad_Economica, '' AS Tipo_Reteica, '' AS Gran_Contribuyente, '' AS Plazo, IF(Autorizado = 'Si','Activo','Inactivo') AS Estado, 'Funcionario' FROM Funcionario P INNER JOIN Contrato_Funcionario FC ON P.Identificacion_Funcionario = FC.Identificacion_Funcionario INNER JOIN (SELECT T.Id_Municipio, Nombre AS Nombre_Municipio, Id_Departamento FROM Municipio T) M ON FC.Id_Municipio = M.Id_Municipio)
            UNION
            (SELECT Nit, '' AS Digito_Verificacion, 'Juridico' AS Tipo_Persona, '' AS Primer_Nombre, '' AS Segundo_Nombre, '' AS Primero_Apellido, '' AS Segundo_Apellido, Nombre AS Razon_Social, Nombre AS Nombre_Comercial, '' AS Direccion, '' AS Telefono, '' AS Celular, '' AS Correo, '' AS Departamento, '' AS Nombre_Municipio, '' AS Tipo_Regimen, '' AS Tipo_Retencion, '' AS Animo_Lucro, '' AS Ley_1429_2010, '' AS Actividad_Economica, '' AS Tipo_Reteica, '' AS Gran_Contribuyente, '' AS Plazo, 'Activo' AS Estado, 'Caja_Compensacion' AS Tipo FROM Caja_Compensacion WHERE Nit IS NOT NULL)";
        
            break;
        case 'Reporte_Exentos':

        
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $query = 'SELECT F.Codigo, F.Fecha_Documento AS Fecha, P.Nombre_Comercial, P.Laboratorio_Comercial, PF.Cantidad, PF.Precio, PF.Descuento, PF.Impuesto, PF.Subtotal
            FROM Producto_Factura PF
            INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
            INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
            WHERE P.Laboratorio_Comercial LIKE "%Exentos%"
            AND PF.Impuesto = 0
            AND F.Fecha_Documento LIKE "%2020-03%"
            
            UNION ALL
            
            SELECT F.Codigo, F.Fecha_Documento AS Fecha, P.Nombre_Comercial, P.Laboratorio_Comercial, PF.Cantidad, PF.Precio_Venta, PF.Descuento, PF.Impuesto, PF.Subtotal
            FROM Producto_Factura_Venta PF
            INNER JOIN Factura_Venta F ON F.Id_Factura_Venta = PF.Id_Factura_Venta
            INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
            WHERE P.Laboratorio_Comercial LIKE "%Exentos%"
            AND PF.Impuesto = 0
            AND  DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND  "'.$fecha_fin.'"  
            ORDER BY Fecha';
        
            break;    
            
            
        case 'Nota_Credito_Global';
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            
            $query = 'SELECT 
                    NG.Codigo, NG.Fecha,
                    CONCAT( IFNULL(F.Nombres,CONCAT( F.Primer_Nombre, F.Segundo_Nombre ) ) , " ", 
                    IFNULL(F.Apellidos,CONCAT( F.Primer_Apellido, F.Segundo_Apellido ) )    ) AS  Funcionario_Nota,
                    NG.Codigo_Factura, REPLACE(NG.Tipo_Factura, "_" ," ")AS Tipo_Factura, 
                    NG.Id_Cliente as NIT,  NG.Valor_Total_Factura,
                    SUM(  ( (P.Impuesto)/100) * ( P.Cantidad * (P.Precio_Nota_Credito) )  )  AS Total_Iva, 
                    SUM(P.Valor_Nota_Credito) AS Valor_Nota_Credito , P.Observacion
                    FROM Nota_Credito_Global NG 
                    INNER JOIN Producto_Nota_Credito_Global P ON P.Id_Nota_Credito_Global = NG.Id_Nota_Credito_Global
                    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = NG.Id_Funcionario
                    WHERE  DATE(NG.Fecha) BETWEEN "'.$fecha_inicio.'" AND  "'.$fecha_fin.'"  
                    GROUP BY NG.Id_Nota_Credito_Global
                    ORDER BY  CONVERT(SUBSTRING(NG.Codigo, 6),UNSIGNED INTEGER)
                    ';
            
            
            break;
        case 'Compra_Pai':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $query = "
                SELECT 
                    AR.Codigo as Acta_Recepcion,  AR.Codigo,
                    DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Recepcion,
                    OCN.Id_Proveedor AS Nit, 
                    P.Nombre AS Proveedor, 
                    IF(AR.Id_Bodega = 0, 'Punto Dispensacion', 'Bodega') AS Tipo,
                    FAR.Factura, 
                    FAR.Fecha_Factura,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto=0)
                        END
                    ) as Valor_Excento,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                           
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                        END
                    ) as Valor_Gravado,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                        END
                    ) as Iva,
                    
                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                        END
                    ) AS Rte_Fuente,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                        END
                    ) AS Rte_Ica,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            ((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto=0))
                        END
                    ) as Total_Compra,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL( ( (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR 
                             WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto=0)
                            +
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                            FROM Producto_Acta_Recepcion PAR
                            
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                            ),0)
                        END
                    ) AS Neto_Factura,
                    

                    AR.Estado,
                    'Compras' AS Tipo_Reporte
                    
                        FROM Orden_Compra_Nacional OCN
                        INNER JOIN Acta_Recepcion AR  ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional 
                        INNER JOIN Factura_Acta_Recepcion FAR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
                        INNER JOIN Proveedor P ON P.Id_Proveedor = OCN.Id_Proveedor
                        WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$fecha_inicio."' AND '".$fecha_fin."'".$condicion_nit."
                        
                UNION ALL 
                (
                 SELECT 

                    ARI.Codigo as Acta_Recepcion,  NP.Codigo,
                    DATE_FORMAT(NP.Fecha_Registro,'%Y-%m-%d') as Fecha_Recepcion,
                    ARI.Id_Proveedor AS Nit, 
                    P.Nombre AS Proveedor, 
                    'Internacional' AS Tipo,
                    FAR.Factura, 
                    FAR.Fecha_Factura,    
                    
           
                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL( 
                                ROUND(SUM( IF(PNP.Total_Iva=0, PNP.Precio_Unitario_Pesos * PNP.Cantidad , 0 )  ),2), 0)
                        END
                    ) AS Valor_Excento,
                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(
                                ROUND(SUM( IF( PNP.Total_Iva>=0, PNP.Precio_Unitario_Pesos * PNP.Cantidad , 0 ) ),2),0)
                        END
                    ) AS Valor_Gravado,

                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(SUM(  PNP.Total_Iva  ),0)
                        END
                    ) AS Iva,

                    0 AS Rte_Fuente,
                    0 AS Rte_Ica,

                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(
                                ROUND(SUM( PNP.Precio_Unitario_Pesos * PNP.Cantidad  ),2),0)
                        END
                    ) AS Total_Compra,
                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(SUM( PNP.Subtotal  ),0)
                        END
                    ) AS Neto,
                    
                    NP.Estado,
                    'Parcial Internacional' AS Tipo_Reporte

                    
                 FROM Nacionalizacion_Parcial NP
                 INNER JOIN Producto_Nacionalizacion_Parcial PNP ON PNP.Id_Nacionalizacion_Parcial =  NP.Id_Nacionalizacion_Parcial 
                 INNER JOIN Producto PR ON PR.Id_Producto = PNP.Id_Producto
                 INNER JOIN Acta_Recepcion_Internacional ARI ON ARI.Id_Acta_Recepcion_Internacional = NP.Id_Acta_Recepcion_Internacional
                 #INNER JOIN Producto_Acta_Recepcion_Internacional PAN ON PAN.Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional
                
                 INNER JOIN Factura_Acta_Recepcion_Internacional FAR ON FAR.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
                 INNER JOIN Proveedor P ON P.Id_Proveedor = ARI.Id_Proveedor
                 GROUP BY NP.Id_Nacionalizacion_Parcial 
                )
                 


                        
                        
                        
                #ORDER BY  CONVERT(SUBSTRING(Codigo, 4),UNSIGNED INTEGER) ";
               # echo $query;exit;
            break;
    }

    return $query;
}

function ValidarKey($key){
    $datos=["Total_Impuesto","Nada","Excenta", "Valor_Excento", "Valor_Gravado",
     "Rte_Fuente", "Rte_Ica", "Total_Compra", "Iva","Descuentos", "Total_Venta", 
     "Neto_Factura", "Costo_Venta_Exenta", "Costo_Venta_Gravada", "Gravado", "Total", 
     "Excento", "Total_Factura", "Gravada", "Valorizado", "Tramite_Sia", "Formulario", 
     "Cargue", "Gasto_Bancario", "Descuento Arancelario", "Flete_Internacional_USD",
      "Seguro_Internacional_USD", "Total_Flete_Nacional", "Total_Licencia", "Total_Arancel",
       "Total_Iva", "Subtotal_Importacion", "Subtotal_Nacionalizacion", "Tasa","Costo_Total",
       "Rentabilidad","Total_Costo_Venta","Cuota_Moderadora","Cuota_Recuperacion","Neto","Total"
       ,"Valor_Total_Factura","Valor_Nota_Credito","Precio","Descuento","Subtotal"];
    $pos = array_search($key,$datos);	
    return strval($pos);
}


function ValidarCodigos($key){
$datos=["Ventas","DevolucionV","DevolucionC","Acta_Compra"/*,"Nota_Credito_Global"*/];
    $res = in_array($key,$datos);	
    return strval($res);
}
