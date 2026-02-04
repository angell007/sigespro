<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../../class/class.querybasedatos.php');
    include_once('../../../class/class.paginacion.php');
    include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.utility.php');

    $util = new Utility();

    $id_parcial = ( isset( $_REQUEST['id_parcial'] ) ? $_REQUEST['id_parcial'] : '' );

    $query = '
        SELECT 
            NP.*,
            (SELECT 
                IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
             FROM Proveedor
            WHERE Id_Proveedor = ARI.Tercero_Flete_Nacional) AS Nombre_Tercero_Flete_Nacional,
            (SELECT 
                IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
             FROM Proveedor
            WHERE Id_Proveedor = NP.Tercero_Tramite_Sia) AS Nombre_Tercero_Tramite_Sia,
            (SELECT 
                IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
             FROM Proveedor
            WHERE Id_Proveedor = ARI.Tercero_Licencia_Importacion) AS Nombre_Tercero_Licencia_Importacion,
            (SELECT 
                IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
             FROM Proveedor
            WHERE Id_Proveedor = NP.Tercero_Formulario) AS Nombre_Tercero_Formulario,
            (SELECT 
                IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
             FROM Proveedor
            WHERE Id_Proveedor = NP.Tercero_Cargue) AS Nombre_Tercero_Cargue,
            (SELECT 
                IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
             FROM Proveedor
            WHERE Id_Proveedor = NP.Tercero_Gasto_Bancario) AS Nombre_Tercero_Gasto_Bancario
        FROM Nacionalizacion_Parcial NP
        INNEr JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
        WHERE
            NP.Id_Nacionalizacion_Parcial = '.$id_parcial;

    //var_dump($query);
    //var_dump($parcial);
    
    $queryObj = new QueryBaseDatos($query);
    $parcial = $queryObj->Consultar('simple');

    if ($parcial['query_result'] != '') {
        
        $parcial['productos'] = GetProductosParcial($id_parcial);
        //$acta['query_result']['total'] = GetTotalActa($acta['productos']);
    }


    echo json_encode($parcial);

    function GetProductosParcial($id_parcial){
        global $queryObj;

        $productos = array();

        $query_productos = '
            SELECT 
                PNP.*,
                P.Nombre_Comercial,
                IFNULL(P.Nombre_Listado, "No english name") AS Nombre_Ingles,
                P.Embalaje,
                IF(P.Gravado = "No", 0, 19) AS Gravado,
                PARI.Lote
            FROM Producto_Nacionalizacion_Parcial PNP
            INNER JOIN Producto P ON PNP.Id_Producto = P.Id_Producto
            INNER JOIN  Producto_Acta_Recepcion_Internacional PARI ON PNP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
            WHERE
                PNP.Id_Nacionalizacion_Parcial = '.$id_parcial;

        $queryObj->SetQuery($query_productos);
        $productos = $queryObj->ExecuteQuery('multiple');        

        return $productos;
    }
          
?>