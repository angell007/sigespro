<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

    $query = '
    SELECT *, (SELECT Nombre FROM Regimen R WHERE R.Id_Regimen=P.Id_Regimen) as Regimen 
    FROM Dispensacion_Mipres D
    INNER JOIN (SELECT PA.Id_Paciente, Concat_ws(" ",PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido) as Nombre, PA.Direccion, PA.Id_Regimen FROM Paciente PA ) P ON D.Id_Paciente=P.Id_Paciente
    WHERE
        D.Id_Dispensacion_Mipres = '.$id;
    
    $queryObj = new QueryBaseDatos($query);
    $acta = $queryObj->Consultar('simple');

    if ($acta['query_result'] != '') {
        
        $acta['productos'] = GetProductosDireccionamiento($id);
    }


    echo json_encode($acta);

   

    function GetProductosDireccionamiento($id){
        global $queryObj;

        $productos = array();

        $query_productos = '
            SELECT 
            
                P.Nombre_Comercial,	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,
               P.Codigo_Cum, PD.Cantidad,P.Embalaje,PD.*
            FROM Producto_Dispensacion_Mipres PD 
            INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto
            WHERE
                PD.Id_Dispensacion_Mipres = '.$id;

        $queryObj->SetQuery($query_productos);
        $productos = $queryObj->ExecuteQuery('multiple');
        return $productos;
    }

   
          
?>