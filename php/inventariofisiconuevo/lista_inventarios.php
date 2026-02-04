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

    $condicion = SetCondiciones();

    $query = 'SELECT I.Id_Inventario_Fisico, I.Fecha, I.Estado,
    CONCAT(FD.Nombres," ",FD.Apellidos) as Funcionario, 
    B.Nombre as Bodega, C.Nombre as Categoria, S.Nombre AS Subcategoria
    FROM Z_Inventario_Fisico_Nuevo I
    INNER JOIN Funcionario FD ON I.Identificacion_Funcionario = FD.Identificacion_Funcionario
    INNER JOIN Bodega B ON I.Id_Bodega = B.Id_Bodega
    LEFT JOIN Categoria C ON I.Id_Categoria = C.Id_Categoria
    LEFT JOIN Subcategoria S ON S.Id_Subcategoria = I.Id_Subcategoria
    '.$condicion.'
    ORDER BY I.Id_Inventario_Fisico DESC';

    $query_count = '
        SELECT 
            COUNT(I.Id_Inventario_Fisico) AS Total
            FROM Z_Inventario_Fisico_Nuevo I
            INNER JOIN Funcionario FD ON I.Identificacion_Funcionario = FD.Identificacion_Funcionario
            INNER JOIN Bodega B ON I.Id_Bodega = B.Id_Bodega
            LEFT JOIN Categoria C ON I.Id_Categoria = C.Id_Categoria
            LEFT JOIN Subcategoria S ON S.Id_Subcategoria = I.Id_Subcategoria
            '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones(){
        global $util;

        $condicion = ''; 

        if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
            $fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
            $fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
            $condicion .= " WHERE (DATE(I.Fecha_Inicio) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
        }

        if (isset($_REQUEST['bodega']) && $_REQUEST['bodega'] != "") {
            if ($condicion != "") {
                $condicion .= " AND I.Bodega = $_REQUEST[bodega]";
            } else {
                $condicion .= " WHERE I.Bodega = $_REQUEST[bodega]";
            }
        }
        
        if (isset($_REQUEST['categoria']) && $_REQUEST['categoria'] != "") {
            if ($condicion != "") {
                $condicion .= " AND I.Categoria = $_REQUEST[categoria]";
            } else {
                $condicion .= " WHERE I.Categoria = $_REQUEST[categoria]";
            }
        }
        
        if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
            if ($condicion != "") {
                $condicion .= " AND I.Estado = '$_REQUEST[estado]'";
            } else {
                $condicion .= " WHERE I.Estado = '$_REQUEST[estado]'";
            }
        }
       
        return $condicion;
    }
          
?>