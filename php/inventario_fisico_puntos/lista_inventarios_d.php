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

    $query = ' SELECT I.Id_Inventario_Fisico_Punto, I.Id_Punto_Dispensacion, I.Fecha_Inicio, I.Fecha_Fin, I.Estado,
    CONCAT(FD.Nombres," ",FD.Apellidos) as Funcionario_Digita, CONCAT(FC.Nombres," ",FC.Apellidos) as Funcionario_Cuenta, I.Comparar,
    PD.Nombre as Punto, I.Conteo_Productos , I.Inventario
    FROM Inventario_Fisico_Punto I
    INNER JOIN Funcionario FD
    ON I.Funcionario_Digita = FD.Identificacion_Funcionario
    INNER JOIN Funcionario FC
    ON I.Funcionario_Cuenta = FC.Identificacion_Funcionario
    INNER JOIN Punto_Dispensacion PD
    On I.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion '.$condicion .' Order By I.Id_Inventario_Fisico_Punto DESC  ';

    $query_count = "SELECT   COUNT(I.Id_Inventario_Fisico_Punto) AS Total
     FROM Inventario_Fisico_Punto I
    INNER JOIN Funcionario FD
    ON I.Funcionario_Digita = FD.Identificacion_Funcionario
    INNER JOIN Funcionario FC
    ON I.Funcionario_Cuenta = FC.Identificacion_Funcionario
    INNER JOIN Punto_Dispensacion PD
    On I.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion ".$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        $queryObj = new QueryBaseDatos();

        $id = ( isset( $req['funcionario'] ) ? $req['funcionario'] : '' );

        $query = 'SELECT  GROUP_CONCAT(FP.Id_Punto_Dispensacion) as punto
        FROM Funcionario_Punto FP
        WHERE FP.Identificacion_Funcionario='.$id;

        $queryObj->SetQuery($query);
        $puntos=$queryObj->ExecuteQuery('simple');

        $condicion="WHERE I.Id_Punto_Dispensacion IN ($puntos[punto]) ";
   
        return $condicion;
    }
          
?>