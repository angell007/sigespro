<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_No_Pos.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.querybasedatos.php');


$fini = ( isset( $_REQUEST['fini'] ) ? $_REQUEST['fini'] : '' );
$ffin= ( isset( $_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : '' );
$queryObj = new QueryBaseDatos();
$http_response = new HttpResponse();
$response = array();

$condiciones=GetCondiciones();

$productos=ObtenerProductos();

ArmarReporte($productos);    

function ArmarReporte($productos){

    $encabezado=$productos[0];
    $contenido = '';
    
    if ($encabezado) {
        $contenido .= '<table ><tr>';
        foreach ($encabezado as $key => $value) {
            if($key!='Cantidad'){
                $contenido.='<td border="0.5"> <strong>'. str_replace("_"," ",$key).' </strong></td>';
            }
         
        }
        $contenido .= '</tr>';
    }

    if ($productos) {
        foreach ($productos as $i => $dato) {
            $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {
                if($key!='Cantidad' ){
                    if($key=='Compras'){
                        $compras=ObtenerCompras($dato[$key]);
                        $contenido.='<td>
                        <table>
                        <tr>
                        <td>Orden</td>
                        <td>Cantidad </td>
                        <td>Estado</td>
                        <td>Proveedor</td>                    
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
    
                    }else if($key=='Actas'){
                        $compras=ObtenerActas($dato[$key]);
                        $contenido.='<td>
                        <table>
                        <tr>
                        <td>Acta</td>
                        <td>Cantidad </td>
                        <td>Estado</td>
                        <td>Proveedor</td>                    
                        ';
    
    
                        foreach ($compras  as $item ) {
                            $contenido.='<tr>';
                            foreach ($item as $llave => $valor) {
                                $contenido.='<td>'.$item[$llave].'</td>';
                            }
                            $contenido.=' </tr>';
                          
                        }
    
                        if(count($compras)==0){
                            $contenido.='<tr><td colspan=4 >No Hay Actas </td></tr>'; 
                        }
                        $contenido.= '
                        </table>
                        
                        </td>';
    
                    } else{
                        $contenido.= '<td>' . $dato[$key] . '</td>';
                    }


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




function ObtenerEps(){
    global $queryObj;

    $query='SELECT GROUP_CONCAT(DISTINCT PA.Nit) as EPS
    FROM Producto_Dispensacion PD
    INNER JOIN (SELECT A.Id_Dispensacion, A.Id_Paciente FROM Auditoria A INNER JOIN Dispensacion D On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND D.Estado_Dispensacion!="Anulada" ) A ON PD.Id_Dispensacion=A.Id_Dispensacion INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto 
    INNER JOIN Paciente PA ON A.Id_Paciente=PA.Id_Paciente
    WHERE P.Id_Categoria!=12
    GROUP BY PA.Nit';

    $queryObj->SetQuery($query);
    $eps = $queryObj->ExecuteQuery('multiple');
    
    return $eps;
}

function ObtenerProductos(){
    global $queryObj,$condiciones;

        $campos=CrearSubconsultas();

        $query='SELECT SUM(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad, IFNULL(CONCAT(P.Nombre_Comercial, " (",P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, ") ", P.Cantidad," ",P.Unidad_Medida
        ), CONCAT(P.Nombre_Comercial)) as Nombre_Producto, P.Nombre_Comercial as "Nombre Comercial" , P.Embalaje,P.Laboratorio_Comercial as "Laboratorio Comercial ",P.Laboratorio_Generico as "Laboratorio Generico ",IFNULL((SELECT SUM(Cantidad-(Cantidad_Apartada+Cantidad_Seleccionada)) FROM Inventario WHERE Id_Producto=PD.Id_Producto AND Id_Bodega!=0),0) as Cantidad_Inventario,'.$campos.', PD.Id_Producto as Compras, PD.Id_Producto as Actas
        FROM Producto_Dispensacion PD
        INNER JOIN (SELECT A.Id_Dispensacion, A.Id_Paciente,( SELECT MAX(DATE(Fecha)) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Fecha_Preauditoria FROM Auditoria A INNER JOIN Dispensacion D On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND D.Estado_Dispensacion!="Anulada" ) A ON PD.Id_Dispensacion=A.Id_Dispensacion INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto 
        INNER JOIN Paciente PA ON A.Id_Paciente=PA.Id_Paciente
        WHERE P.Id_Categoria!=12 '.$condiciones.'
        GROUP BY PD.Id_Producto
        HAVING Cantidad>Cantidad_Inventario Order BY Nombre_Producto';

        $queryObj->SetQuery($query);
        $productos = $queryObj->ExecuteQuery('multiple');

        return $productos;
}

function CrearSubconsultas(){
    global $condiciones;
    $eps=ObtenerEps();
    $campos='';

    foreach ($eps as  $value) {
        if ($value['EPS'] != '') {
            $campos.='IFNULL((SELECT SUM(PDR.Cantidad_Formulada-PDR.Cantidad_Entregada) 
            FROM Producto_Dispensacion PDR
            INNER JOIN (SELECT A.Id_Dispensacion, A.Id_Paciente,( SELECT MAX(DATE(Fecha)) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Fecha_Preauditoria  FROM Auditoria A INNER JOIN Dispensacion D On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND D.Estado_Dispensacion!="Anulada" ) A ON PDR.Id_Dispensacion=A.Id_Dispensacion 
            INNER JOIN Paciente PA ON A.Id_Paciente=PA.Id_Paciente
            WHERE PDR.Id_Producto=PD.Id_Producto AND PA.Nit='.$value['EPS'].$condiciones.'  ),0) as "'.ObtenerNombreEps($value['EPS']).'",';
        }        
    }


   return trim($campos,',');
   
}

function ObtenerNombreEps($nit){
    global $queryObj;

    $query='SELECT Nombre FROM Eps WHERE Nit='.$nit;

    $queryObj->SetQuery($query);
    $eps = $queryObj->ExecuteQuery('simple');
  

    return $eps['Nombre'];
}

function GetCondiciones(){
    $condicion='';
    if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin']) ) {
        $condicion.=" AND   DATE(A.Fecha_Preauditoria)>='".$_REQUEST['fini']."' AND DATE(A.Fecha_Preauditoria)<='".$_REQUEST['ffin']."'";


    }

  

    return $condicion;
}

function ObtenerCompras($id){
    global $ffin,$fini;
    $condicion=" WHERE OCN.Fecha>=$fini AND OCN.Fecha<= $ffin AND OCN.Estado='Pendiente' ";    
    $condicion_producto=" AND POCN.Id_Producto=$id ";

    $query='SELECT  OCN.Codigo, 
    POCN.Cantidad as Cantidad,OCN.Estado,
    (SELECT Nombre FROM Proveedor WHERE Id_Proveedor=OCN.Id_Proveedor) as Proveedor
    FROM Producto_Orden_Compra_Nacional POCN        
    INNER JOIN Orden_Compra_Nacional OCN ON POCN.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional'.$condicion.$condicion_producto;


    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos= $oCon->getData();
    unset($oCon);
    return $datos;
    
}

function ObtenerActas($id){
    global $ffin,$fini;
    $condicion=" WHERE AR.Fecha_Creacion>='$fini' AND AR.Fecha_Creacion<= '$ffin' AND AR.Estado='Pendiente' ";    

    $condicion_producto=" AND P.Id_Producto=$id ";

    $query='SELECT  AR.Codigo, 
    P.Cantidad , AR.Estado,
    (SELECT Nombre FROM Proveedor WHERE Id_Proveedor=AR.Id_Proveedor) as Proveedor
    FROM Producto_Acta_Recepcion P        
    INNER JOIN Acta_Recepcion AR ON P.Id_Acta_Recepcion=AR.Id_Acta_Recepcion'.$condicion.$condicion_producto;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos= $oCon->getData();
    unset($oCon);
    return $datos;
    
}


