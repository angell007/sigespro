<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/* header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte No Pos.csv"'); */
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte Facturas.xls"');
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
                if($key!='Cantidad' ){                    
                        $contenido.= '<td>' . $dato[$key] . '</td>';
                }                
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
        $query=" SELECT 
        F.Codigo AS Factura,
        D.Codigo AS Dispensacion,
        D.Numero_Documento AS Paciente
        
    FROM
        Factura F
            INNER JOIN
        (SELECT 
            Id_Dispensacion, Codigo, Numero_Documento
        FROM
            Dispensacion
        WHERE
            Estado_Facturacion = 'Facturada'
                AND ((Tipo = 'Cohortes' OR Tipo_Servicio = 6) OR Tipo='Evento')) D ON F.Id_Dispensacion = D.Id_Dispensacion $condiciones";

       
        $queryObj->SetQuery($query);
       
        $productos = $queryObj->ExecuteQuery('multiple');

        

        return $productos;
}






function GetCondiciones(){
    $condicion='';
    if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != ""  ) {
        $condicion.=" WHERE F.Id_Dispensacion IN ($_REQUEST[dis])";
    }
    return $condicion;
}

