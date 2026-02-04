<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/* header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte No Pos.csv"'); */
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte Entrega Pendientes.xls"');
header('Cache-Control: max-age=0');  
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

    if ($productos) {
        foreach ($productos as $i => $dato) {
            $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {                                
                        $contenido.= '<td>' . $dato[$key] . '</td>';                
            }
    
             $contenido .= '</tr>';
        }
    
      $contenido .= '</table>';
    }

    if ($contenido == '') {
        $contenido .= 'NO EXISTE INFORMACION PARA MOSTRAR';
    }

 echo $contenido;

}





function ObtenerProductos(){
    global $queryObj,$condiciones;

      
    $query = ' SELECT D.Codigo as Dispensacion,D.Estado_Dispensacion,D.Estado_Facturacion,PD.Id_Paciente,CONCAT_WS(" ",PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido )  as Paciente,
    IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto,P.Nombre_Comercial,P.Codigo_Cum, PD.Lote,PD.Cantidad, R.Codigo as Remision
      
    FROM Descarga_Pendiente_Remision DP
    INNER JOIN Producto_Descarga_Pendiente_Remision PD
    ON DP.Id_Descarga_Pendiente_Remision=PD.Id_Descarga_Pendiente_Remision 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto   
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Paciente PA ON PD.Id_Paciente=PA.Id_Paciente
    INNER JOIN Remision R ON PD.Id_Remision=R.Id_Remision 
    WHERE PD.Entregado="No" AND R.Estado="Recibida"
    '.$condiciones.' Order BY P.Nombre_Comercial ';

        $queryObj->SetQuery($query);
       
        $productos = $queryObj->ExecuteQuery('multiple');
        return $productos;
}

function GetCondiciones(){
    $condicion = '';
  
    if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {        
            $condicion .= "AND  DP.Id_Punto_Dispensacion=".$_REQUEST['punto']."";
    
    }
    return $condicion;
}

