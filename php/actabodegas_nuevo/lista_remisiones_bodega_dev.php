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
    $id_funcionario = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = 'SELECT R.*, (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision = R.Id_Remision) as Items, F.Imagen 
    FROM Remision R
    INNER JOIN  Funcionario F 
    On R.Identificacion_Funcionario=F.Identificacion_Funcionario
        '.$condicion;


    $query_count = '
        SELECT 
            COUNT(R.Id_Remision) AS Total
            FROM Remision R
            INNER JOIN  Funcionario F 
            On R.Identificacion_Funcionario=F.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        global $id_funcionario;

        $query='SELECT GROUP_CONCAT(FP.Id_Bodega_Nuevo) as Id_Bodega_Nuevo
        FROM Funcionario_Bodega_Nuevo FP
        WHERE FP.Identificacion_Funcionario='.$id_funcionario;
        $oCon= new consulta();
        $oCon->setQuery($query);
        //$oCon->setTipo('Multiple');
        $bodegas= $oCon->getData();
        
        unset($oCon);
 

        $condicion = " WHERE R.Estado_Alistamiento=2 and R.Tipo_Destino='Bodega' AND R.Estado='Enviada' AND R.Id_Destino IN ($bodegas[Id_Bodega_Nuevo]) "; 
       
        if (isset($req['rem']) && $req['rem']) {
            if ($condicion != "") {
                $condicion .= " AND R.Codigo LIKE '%".$req['rem']."%'";
            } else {
                $condicion .= " WHERE R.Codigo LIKE '%".$req['rem']."%'";
            }
        }

       

        return $condicion;
    }
          
?>