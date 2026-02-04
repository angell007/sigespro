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

    $query = "SELECT NC.*, F.Imagen, AR.Codigo as Codigo_Acta, P.Nombre, AR.Tipo, OCN.Codigo AS Codigo_Orden
    FROM No_Conforme NC
   INNER JOIN Funcionario F
   ON NC.Persona_Reporta=F.Identificacion_Funcionario
   INNER JOIN Acta_Recepcion AR
   ON NC.Id_Acta_Recepcion_Compra=AR.Id_Acta_Recepcion
   INNER JOIN Orden_Compra_Nacional OCN
   ON AR.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional
   INNER JOIN Proveedor P
   ON AR.Id_Proveedor=P.Id_Proveedor
    WHERE NC.Tipo = 'Compra' AND NC.Estado='Pendiente' ".$condicion ." Order By AR.Id_Acta_Recepcion DESC  ";

    $query_count = "SELECT   COUNT(NC.Id_No_Conforme) AS Total
    FROM No_Conforme NC
   INNER JOIN Funcionario F
   ON NC.Persona_Reporta=F.Identificacion_Funcionario
   INNER JOIN Acta_Recepcion AR
   ON NC.Id_Acta_Recepcion_Compra=AR.Id_Acta_Recepcion
   INNER JOIN Orden_Compra_Nacional OCN
   ON AR.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional
   INNER JOIN Proveedor P
   ON AR.Id_Proveedor=P.Id_Proveedor
    WHERE NC.Tipo = 'Compra' AND NC.Estado='Pendiente'".$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        $condicion = ''; 

        if (isset($req['codigo']) && $req['codigo']) {           
                $condicion .= " AND AR.Codigo LIKE '%".$req['codigo']."%'";
            
        }
        if (isset($req['orden']) && $req['orden']) {           
                $condicion .= " AND OCN.Codigo LIKE '%".$req['orden']."%'";
            
        }
        return $condicion;
    }
          
?>