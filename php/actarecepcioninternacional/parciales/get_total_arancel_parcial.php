<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../../class/class.querybasedatos.php');
    include_once('../../../class/class.paginacion.php');
    include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.utility.php');

    $util = new Utility();
    $queryObj = new QueryBaseDatos();

    $id_parcial = ( isset( $_REQUEST['id_parcial'] ) ? $_REQUEST['id_parcial'] : '' );

    //var_dump($query);
    //var_dump($parcial);
        
    $arancel = GetArancelParcial($id_parcial);

    echo json_encode($arancel);

    function GetArancelParcial($id_parcial){
        global $queryObj;

        $productos = array();

        $query_productos = '
            SELECT 
                SUM(Total_Arancel) AS Total_Arancel
            FROM Producto_Nacionalizacion_Parcial
            WHERE
                Id_Nacionalizacion_Parcial = '.$id_parcial;

        $queryObj->SetQuery($query_productos);
        $result = $queryObj->Consultar('simple');        

        return $result;
    }
          
?>