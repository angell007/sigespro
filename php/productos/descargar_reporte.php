<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_productos.php.xls"');
    header('Cache-Control: max-age=0');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();


    $productos = GetProductos();
    ArmarTablaResultados($productos);
 
    function GetProductos(){
        global $queryObj;

        $query_productos = 'SELECT Nombre_Comercial, CONCAT_WS(" ",Principio_Activo,Presentacion,Concentracion,Cantidad,Unidad_Medida) as Nombre, Embalaje, Codigo_Cum, Laboratorio_Generico, Laboratorio_Comercial, Invima FROM Producto WHERE Estado="Inactivo"';

        $queryObj->SetQuery($query_productos);
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
            <td align="center"><strong>Laboratorio Comercial </strong></td>
            <td align="center"><strong>Laboratorio Generico</strong></td>
            <td align="center"><strong>Embalaje</strong></td>
            <td align="center"><strong>Codigo Cum</strong></td>
            <td align="center"><strong>Invima</strong></td>
            
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Nombre_Comercial'].'</td>
                    <td>'.$r['Nombre'].'</td>
                    <td>'.$r['Laboratorio_Comercial'].'</td>
                    <td>'.$r["Laboratorio_Generico"].'</td>
                    <td>'.$r["Embalaje"].'</td>
                    <td>'.$r["Codigo_Cum"].'</td>
                    <td>'.$r["Invima"].'</td>
                   
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