<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Ultimas_Compras.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion='';
$query=CrearQuery();
ArmarReporte($query);





function ArmarReporte($query){

    $encabezado=GetEncabezado($query);
    $datos=GetDatos($query);
    $contenido = '';
    
    if ($encabezado) {
        $contenido .= '<table border="1"><tr>';
        foreach ($encabezado as $key => $value) {
          $contenido.='<td>'.$key.'</td>';
        }
        $contenido .= '</tr>';
    }

    if ($datos) {
        foreach ($datos as $i => $dato) {
            $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {
           
              
                if($key=='Compras'){
                    $compras=ObtenerCompras($dato[$key]);
                    $contenido.='<td>
                    <table>
                    <tr>
                    <td>Acta</td>
                    <td>Fecha </td>
                    <td>Proveedor</td>
                    <td>Precio</td>                    
                    ';


                    foreach ($compras  as $item ) {
                        $contenido.='<tr>';
                        foreach ($item as $llave => $valor) {
                            $contenido.='<td>'.$item[$llave].'</td>';
                        }
                        $contenido.=' </tr>';
                      
                    }

                    if(count($compras)==0){
                        $contenido.='<tr><td colspan=4>No Hay Compras </td></tr>'; 
                    }
                    $contenido.= '
                    </table>
                    
                    </td>';

                }else{
                    $contenido.= '<td>' . $dato[$key] . '</td>';
                }
               
            }
    
            $contenido .= '</tr>';
        }
    
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
function CrearQuery(){
    global $condicion;
    if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin']) ) {
        $condicion.=" WHERE  DATE(AR.Fecha_Creacion)>='".$_REQUEST['fini']."' AND DATE(AR.Fecha_Creacion)<='".$_REQUEST['ffin']."'";
    }

      $query='SELECT  PRD.Nombre_Comercial,  IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
      IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
      PRD.Embalaje,
      PRD.Codigo_Cum, 
      (SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto=P.Id_Producto ) as Costo_Promedio, P.Id_Producto as Compras
     FROM Producto_Acta_Recepcion P
     INNER JOIN Producto PRD
     ON P.Id_Producto=PRD.Id_Producto
     INNER JOIN Acta_Recepcion AR ON P.Id_Acta_Recepcion=AR.Id_Acta_Recepcion
    '.$condicion.' GROUP BY P.Id_Producto';
           
        

    return $query;
}

function ValidarKey($key){
    $datos=["Nada","Excenta", "Iva","Descuentos", "Total_Venta", "Neto_Factura", "Costo_Venta_Exenta", "Costo_Venta_Gravada", "Gravado", "Total", "Excento", "Total_Factura", "Gravada", "Valorizado", "Costo_unitario", "PrecioVenta","Subtotal","Valor_Final","Valor_Inicial", "Costo_Promedio_Unitario", "Flete_Internacional_USD", "Seguro_Internacional_USD", "Flete_Nacional", "Licencia_Importacion", "Precio_USD", "Subtotal_USD", "Tasa" ];
    $pos = array_search($key,$datos);	
    return strval($pos);
}

function ObtenerCompras($id){
    global $condicion;    
    $condicion_producto=" AND P.Id_Producto=$id ";

    $query='SELECT  AR.Codigo, 
    DATE(AR.Fecha_Creacion) as Fecha, 
    (SELECT Nombre FROM Proveedor WHERE Id_Proveedor=AR.Id_Proveedor limit 1) as Proveedor,
    P.Precio 
    FROM Producto_Acta_Recepcion P        
    INNER JOIN Acta_Recepcion AR ON P.Id_Acta_Recepcion=AR.Id_Acta_Recepcion'.$condicion.$condicion_producto;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $datos= $oCon->getData();
        unset($oCon);
        return $datos;
    
}


