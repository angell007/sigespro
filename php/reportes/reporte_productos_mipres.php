<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_productos_mipres.php.xls"');
    header('Cache-Control: max-age=0'); 

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();

    $condiciones = SetConditions();
    $productos = GetProductos($condiciones);
    ArmarTablaResultados($productos);

    function SetConditions(){
        $req = $_REQUEST;
        $condicion='';
        $condicion .= " AND D.Fecha_Actual BETWEEN '".$req[fini]."' AND '".$req[ffin]."'";
        

        return $condicion;
    }



    function SepararFechas($fechas){
        $fechas_separadas = explode(" - ", $fechas);
        return $fechas_separadas;
    }

    function GetProductos($condiciones){
        global $queryObj;

        $query = ' SELECT P.Nombre_Comercial,P.Codigo_Cum,D.Codigo as Dispensacion, (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida) as Nombre, DM.Fecha_Maxima_Entrega,DATE(D.Fecha_Actual) as Fecha_Solicitud,IFNULL((SELECT SUM(Cantidad-(Cantidad_Apartada+Cantidad_Seleccionada)) FROM Inventario WHERE Id_Bodega!=0 AND Id_Producto=PD.Id_Producto),0) as Cantidad_Inventario
        FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion INNER JOIN Dispensacion_Mipres DM ON D.Id_Dispensacion_Mipres=DM.Id_Dispensacion_Mipres INNER JOIn Producto P ON PD.Id_Producto=P.Id_Producto WHERE D.Pendientes!=0 AND D.Estado_Dispensacion!="Anulada"'.$condiciones.' HAVING Cantidad_Pendiente>0';


        $queryObj->SetQuery($query);
        $productos = $queryObj->ExecuteQuery('multiple');
        return $productos;
    }

    function ArmarTablaResultados($resultados){

        $contenido_excel = '';

        $contenido_excel = '
        <table border=1>
        <tr>
            <td align="center"><strong>Nombre Comercial</strong></td>
            <td align="center"><strong>Nombre</strong></td>
            <td align="center"><strong>Codigo Cum</strong></td>
            <td align="center"><strong>Dispensacion</strong></td>
            <td align="center"><strong>Fecha Maxima Entrega</strong></td>
            <td align="center"><strong>Fecha Solicitud</strong></td>
            <td align="center"><strong>Cantidad</strong></td>
            <td align="center"><strong>Cantidad Inventario</strong></td>
            
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Nombre_Comercial'].'</td>
                    <td>'.$r['Nombre'].'</td>
                    <td>'.$r['Codigo_Cum'].'</td>
                    <td>'.$r['Dispensacion'].'</td>
                    <td>'.$r["Fecha_Maxima_Entrega"].'</td>
                    <td>'.$r["Fecha_Solicitud"].'</td>
                    <td>'.$r["Cantidad_Pendiente"].'</td>
                    <td>'.$r["Cantidad_Inventario"].'</td>
                   
                </tr>';
            } 
        }else{
    
            $contenido_excel .= '
            <tr>
                <td colspan="8" align="center">SIN RESULTADOS PARA MOSTRAR</td>
            </tr>';
        }        
           
    
        $contenido_excel .= '
        </table>';

        echo $contenido_excel;
    }

?>