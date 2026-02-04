<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = '
        SELECT 
            AR.*,
            OCN.Codigo AS Codigo_Orden,(SELECT Nombre FROM Proveedor WHERE Id_Proveedor=AR.Id_Proveedor) as Proveedor
        FROM Acta_Recepcion AR
        INNER JOIN Orden_Compra_Nacional OCN ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional
        WHERE AR.Estado="Anulada" '.$condicion .' Order By AR.Id_Acta_Recepcion DESC  ';

    $query_count = '
        SELECT 
            COUNT(AR.Id_Acta_Recepcion) AS Total
            FROM Acta_Recepcion AR
            INNER JOIN Orden_Compra_Nacional OCN ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional
            WHERE AR.Estado="Anulada" '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        $condicion = ''; 

        if (isset($req['codigo']) && $req['codigo']) {           
                $condicion .= " AND AR.Codigo LIKE '%".$req['codigo']."%'";
            
        }
        return $condicion;
    }
          
?>