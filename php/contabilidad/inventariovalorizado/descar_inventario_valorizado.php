<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/*
header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Inventario_Valorizado.csv"'); 
*/

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_'.$_REQUEST['tipo'].'.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');


$fecha = isset($_REQUEST['Fecha'])? $_REQUEST['Fecha'] : false;


  $fecha_emision = $fecha.'-01';
  $fecha_emision = strtotime($fecha_emision);
  $fecha_ultima_compra = date("Y,m,d", strtotime("+1 month", $fecha_emision));
  $fecha_ultima_compra = str_replace(',','-',$fecha_ultima_compra);
 

if($fecha){
    
    $query='SELECT  DATE_FORMAT(IV.Fecha_Documento,"%Y-%m-%d") Fecha_Documento,  
        IF (DI.Tipo_Origen = "Bodega_Nuevo", (SELECT Nombre From Bodega_Nuevo WHERE Id_Bodega_Nuevo = DI.Id_Origen),  (SELECT Nombre From Punto_Dispensacion WHERE Id_Punto_Dispensacion = DI.Id_Origen) )AS Origen,
         DI.Cantidad,
         DI.Costo_Promedio,
         (DI.Costo_Promedio* DI.Cantidad) AS Valor,
        P.Nombre_Comercial AS Nombre_Producto,
        P.Tipo,
        P.Laboratorio_Comercial,
        P.Codigo_Cum,
        I.Lote,
        I.Fecha_Vencimiento,
        I.Fecha_Carga,
        I.Lista_Ganancia,
       
        COALESCE( (SELECT CT.Nombre FROM Categoria_Nueva CT WHERE CT.Id_Categoria_Nueva= SUB.Id_Categoria_Nueva  ) , " ") AS Categoria_Nueva,
    	COALESCE( SUB.Nombre, " ") AS Subcategoria,
    	
    	COALESCE( (SELECT PA.Precio FROM Producto_Acta_Recepcion PA
    	            INNER JOIN Acta_Recepcion AR ON AR.Id_Acta_Recepcion = PA.Id_Acta_Recepcion
            	    WHERE PA.Id_Producto = P.Id_Producto
            	    AND DATE(AR.Fecha_Creacion) < "'.$fecha_ultima_compra.'"
                	AND AR.Estado != "Anulada"
                	ORDER BY PA.Id_Producto_Acta_Recepcion DESC LIMIT 1),
                	
                	(
                	SELECT PA.Precio_Unitario_Pesos FROM Producto_Nacionalizacion_Parcial PA
            	    INNER JOIN Nacionalizacion_Parcial AR ON AR.Id_Nacionalizacion_Parcial = PA.Id_Nacionalizacion_Parcial
                	WHERE PA.Id_Producto = P.Id_Producto
                	AND DATE(AR.Fecha_Registro) < "'.$fecha_ultima_compra.'"
                	AND AR.Estado != "Anulado"
                	ORDER BY PA.Id_Producto_Nacionalizacion_Parcial DESC LIMIT 1 
                	),
                	0
                	) AS Ultima_Compra
    	
        FROM Inventario_Valorizado IV 
        INNER JOIN Descripcion_Inventario_Valorizado DI
        ON DI.Id_Inventario_Valorizado = IV.Id_Inventario_Valorizado
        INNER JOIN Producto P 
        ON P.Id_Producto = DI.Id_Producto 
        INNER JOIN Inventario_Nuevo I 
        ON I.Id_Inventario_Nuevo = DI.Id_Inventario_Nuevo
        LEFT JOIN Subcategoria SUB ON SUB.Id_Subcategoria = P.Id_Subcategoria  
        WHERE IV.Fecha_Documento LIKE "'.$fecha.'%"
        AND IV.Estado = "Activo" and DI.Cantidad >0';
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');

    $valorizados = $oCon->getData();
    
   
    if($valorizados){
         $encabezado = array_keys($valorizados[0]);
    
        $contenido = '
        <table border="1">
           </thead>';
            
        foreach($encabezado as $dato){
            $contenido.='
                 <th style="background:#b7d7e8">'.$dato.' </th> ';
        }
            
        $contenido.='
            </thead>';
            
        $contenido.=
            '<tbody>';
            
            
        foreach($valorizados as $valorizado){
            $contenido.='
                 <tr>';
            foreach($valorizado as $key => $dato){
                $valor = $dato;
                  
                if(ValidarKey($key) !== false ){
          
                    $valor = $dato != '' ? $dato : 0;
                     $valor = (float)$valor;
                     $valor = number_format($valor,2,",","");
                    
                }
                $contenido.='
                     <td>'.$valor.' </td> ';
            }
           
           $contenido.='
                </tr>';
        }  
        
        $contenido.=
            '</tbody>
        </table>';
            
        
          
    }else{
         $contenido = '
          <table>
                <tr>
                    <td>NO EXISTE INFORMACION PARA MOSTRAR</td>
                </tr>
            </table>';
        
    }
    echo $contenido;
    
    
        
}


function ValidarKey($key){
    $datos=["Costo_Promedio", "Valor","Ultimo_Costo"];
    $pos = array_search($key,$datos);	
    return $pos;
}