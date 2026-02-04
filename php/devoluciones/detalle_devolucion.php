<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');

    $id_devolucion = ( isset( $_REQUEST['id_devolucion'] ) ? $_REQUEST['id_devolucion'] : '' );

    $queryObj = new QueryBaseDatos();

    $query_no_conforme = 'SELECT 
        IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ",
        PRD.Cantidad," ", PRD.Unidad_Medida, " "), CONCAT(PRD.Nombre_Comercial, " LAB-", PRD.Laboratorio_Comercial)) as Nombre_Producto,
        POCN.* , PRD.Embalaje, PRD.Nombre_Comercial   ,
        (SELECT CONCAT(G.Nombre, " - ", E.Nombre)
             FROM Estiba E 
             INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
             WHERE E.Id_Estiba = I.Id_Estiba
        ) AS Ubicacion 


        
        FROM Producto_Devolucion_Compra POCN 
        INNER JOIN Producto PRD
        ON PRD.Id_Producto = POCN.Id_Producto 
        LEFT JOIN Inventario_Nuevo I 
        ON I.Id_Inventario_Nuevo = POCN.Id_Inventario_Nuevo
        
        WHERE POCN.Id_Devolucion_Compra ='.$id_devolucion ;

    $query_no_conforme_encabezado = 'SELECT D.*, CONCAT_WS(" ", f.Nombres, f.Apellidos) AS Nombre_Funcionario, 
    p.Nombre as Proveedor, p.Id_Proveedor,
        COALESCE((SELECT B.Nombre FROM Bodega B WHERE B.Id_Bodega = D.Id_Bodega),
                 (SELECT B.Nombre FROM Bodega_Nuevo B WHERE B.Id_Bodega_Nuevo = D.Id_Bodega_Nuevo)) AS Bodega
        FROM Devolucion_Compra D
        INNER JOIN Proveedor p ON D.Id_Proveedor=p.Id_Proveedor
        INNER JOIN Funcionario f ON D.Identificacion_Funcionario=f.Identificacion_Funcionario
        

        WHERE D.Id_Devolucion_Compra='.$id_devolucion ;

    $queryObj->setQuery($query_no_conforme);
    $productos_no_conforme = $queryObj->Consultar('Multiple', false);

    $queryObj->setQuery($query_no_conforme_encabezado);
    $productos_no_conforme_encabezado = $queryObj->ExecuteQuery('simple');

    $result['encabezado'] = $productos_no_conforme_encabezado;
    $result['no_conformes'] = $productos_no_conforme['query_result'];

    unset($queryObj);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>