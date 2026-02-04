<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/* header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte No Pos.csv"'); */
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte No Pos.xls"');
header('Cache-Control: max-age=0');  

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.querybasedatos.php');

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

    //$contenido = getStrEncabezado();

    if ($productos) {
        foreach ($productos as $i => $dato) {
            $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {
                if($key!='Cantidad' ){
                    if($key=='EPS'){
                       /*  $contenido.= '<td>
                        <table>
                        <tr>
                        <td>EPS</td>
                        <td>Cantidad</td>
                        <tr>' ;
                        $eps=$dato['EPS'];
                        foreach ($eps as $k) {
                            $contenido.='<tr>';
                            foreach ($k as $llave => $valor) {
                                $contenido.='<td>'.$k[$llave].'</td>';
                            }
                            $contenido.=' </tr>';
                        }
                        if(count($eps)==0){
                            $contenido.='<tr><td colspan=4>No Hay Eps </td></tr>'; 
                        }
                        $contenido.= '       </table>
                        
                        </td>' */;

                    }else{
                        $contenido.= '<td>' . $dato[$key] . '</td>';
                    }
                    
                    //$contenido.= $dato[$key] . ';';
                } 

               

               
            }
    
             $contenido .= '</tr>';
            //$contenido .= "\r\n";
        }
    
      $contenido .= '</table>';
    }

    if ($contenido == '') {
        $contenido .= 'NO EXISTE INFORMACION PARA MOSTRAR';
    }

 echo $contenido;

}

function getStrEncabezado() {
    $str = "Nombre Producto;Nombre Comercial;Embalaje;Laboratorio Comercial;Laboratorio Generico;Cantidad Inventario;";

    $eps = ObtenerEps();

    foreach ($eps as $i => $value) {
        if ($value['EPS'] != '') {
            $str .= ObtenerNombreEps($value['EPS']) . ";";
        }
    }

    $str .= "\r\n";

    return $str;
}

function ObtenerEps(){
    global $queryObj;

    $query='SELECT GROUP_CONCAT(DISTINCT PA.Nit) as EPS
    FROM Producto_Dispensacion PD
    INNER JOIN (SELECT A.Id_Dispensacion, A.Id_Paciente FROM Auditoria A INNER JOIN Dispensacion D On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND D.Estado_Dispensacion!="Anulada" ) A ON PD.Id_Dispensacion=A.Id_Dispensacion INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto 
    INNER JOIN Paciente PA ON A.Id_Paciente=PA.Id_Paciente
    WHERE P.Id_Categoria!=12 AND PA.Nit!=830074184
    GROUP BY PA.Nit';

    $queryObj->SetQuery($query);
    $eps = $queryObj->ExecuteQuery('multiple');

    foreach ($eps as $key => $value) {
        $t=explode('-',$value['EPS']);
         $eps[$key]['EPS']=$t[0];
    }


    
    return $eps;
}

function ObtenerProductos(){
    global $queryObj,$condiciones;

      

        /*$query='SELECT  IFNULL(CONCAT(P.Nombre_Comercial, " (",P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, ") ", P.Cantidad," ",P.Unidad_Medida
        ), CONCAT(P.Nombre_Comercial)) as Nombre_Producto, P.Nombre_Comercial as "Nombre Comercial" , P.Embalaje,P.Laboratorio_Comercial as "Laboratorio Comercial ",P.Laboratorio_Generico as "Laboratorio Generico ",P.Codigo_Cum, IFNULL((SELECT SUM(Cantidad-(Cantidad_Apartada+Cantidad_Seleccionada)) FROM Inventario WHERE Id_Producto=PD.Id_Producto AND Id_Bodega!=0),0) as Cantidad_Inventario,SUM(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Requerida,PA.Eps
        FROM Producto_Dispensacion PD
        INNER JOIN (SELECT A.Id_Dispensacion, A.Id_Paciente,( SELECT MAX(DATE(Fecha)) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Fecha_Preauditoria FROM Auditoria A INNER JOIN Dispensacion D On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND D.Estado_Dispensacion!="Anulada" AND A.Punto_Pre_Auditoria!=148 ) A ON PD.Id_Dispensacion=A.Id_Dispensacion INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto 
        INNER JOIN Paciente PA ON A.Id_Paciente=PA.Id_Paciente
        WHERE P.Id_Categoria!=12 AND PA.Nit!=830074184 '.$condiciones.'
        GROUP BY PD.Id_Producto,PA.Nit
        HAVING Cantidad_Requerida>Cantidad_Inventario Order BY Nombre_Producto '*/;

        $query='SELECT  IFNULL(CONCAT(P.Nombre_Comercial, " (",P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, ") ", P.Cantidad," ",P.Unidad_Medida
    ), CONCAT(P.Nombre_Comercial)) as Nombre_Producto, P.Nombre_Comercial as "Nombre Comercial" , P.Embalaje,P.Laboratorio_Comercial as "Laboratorio Comercial ",P.Laboratorio_Generico as "Laboratorio Generico ",P.Codigo_Cum, IFNULL((SELECT SUM(Cantidad-(Cantidad_Apartada+Cantidad_Seleccionada)) FROM Inventario WHERE Id_Producto=PD.Id_Producto AND Id_Bodega!=0),0) as Cantidad_Inventario,SUM(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Requerida,PA.Eps, IFNULL((SELECT SUM(Cantidad) FROM Inventario WHERE Id_Punto_Dispensacion=A.Id_Punto_Dispensacion AND Id_Producto=PD.Id_Producto  ),0) as Cantidad_Inventario_Punto, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=A.Id_Punto_Dispensacion) as Punto_Dispensacion
    FROM Producto_Dispensacion PD
    INNER JOIN (SELECT A.Id_Dispensacion,D.Id_Punto_Dispensacion, A.Id_Paciente,( SELECT MAX(DATE(Fecha)) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Fecha_Preauditoria FROM Auditoria A INNER JOIN Dispensacion D On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND D.Estado_Dispensacion!="Anulada" AND A.Punto_Pre_Auditoria!=148 ) A ON PD.Id_Dispensacion=A.Id_Dispensacion INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto 
    INNER JOIN Paciente PA ON A.Id_Paciente=PA.Id_Paciente
    WHERE P.Id_Categoria!=12 AND PA.Nit!=830074184'.$condiciones.'
        GROUP BY  A.Id_Punto_Dispensacion,PD.Id_Producto,PA.Nit
        HAVING Cantidad_Requerida>Cantidad_Inventario AND Cantidad_Requerida>Cantidad_Inventario_Punto Order BY Nombre_Producto ';

        $queryObj->SetQuery($query);
       
        $productos = $queryObj->ExecuteQuery('multiple');
      
    foreach ($productos as $key => $value) {
       
    }

        

        return $productos;
}

function AsignarEps($productos){
    global $condiciones,$queryObj;    

    foreach ($productos as $key => $pr) {
       
                    $query=' SELECT PA.EPS, SUM(PDR.Cantidad_Formulada-PDR.Cantidad_Entregada) as Cantidad
                    FROM Producto_Dispensacion PDR
                    INNER JOIN (SELECT A.Id_Dispensacion, A.Id_Paciente,( SELECT MAX(DATE(Fecha)) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Fecha_Preauditoria                  
                    FROM Auditoria A INNER JOIN (SELECT Id_Dispensacion FROM Dispensacion WHERE Tipo!="Capita" AND Estado_Dispensacion!="Anulada") D  On A.Id_Dispensacion=D.Id_Dispensacion  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") ) A ON PDR.Id_Dispensacion=A.Id_Dispensacion 
                    INNER JOIN (SELECT Id_Paciente,Nit,EPS FROm Paciente ) PA ON A.Id_Paciente=PA.Id_Paciente
                    WHERE PDR.Id_Producto='.$pr['Id_Producto'].$condiciones.'
                    GROUP BY PA.Nit ';
                               
                    $queryObj->SetQuery($query);

             
                        
                    $p = $queryObj->ExecuteQuery('Multiple');
    
                    $productos[$key]['EPS']=$p;
                  
        
    }

  return $productos;
   
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

