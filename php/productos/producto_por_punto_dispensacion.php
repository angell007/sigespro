<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    
    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    /*$condicion = '';

    $producto_id = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );

    if (isset($_REQUEST['nombre_punto']) && trim($_REQUEST['nombre_punto']) != "") {
        $condicion .= " AND T2.Nombre LIKE '%$_REQUEST[nombre_punto]%'";
    }
    
    if (isset($_REQUEST['cantidad']) && trim($_REQUEST['cantidad']) != "") {
        $condicion .= " AND T1.Cantidad = $_REQUEST[cantidad]";
    }
    
    if (isset($_REQUEST['costo']) && trim($_REQUEST['costo']) != "") {
        $condicion .= " AND T1.Costo = $_REQUEST[costo]";
    }
    
    if (isset($_REQUEST['lote']) && trim($_REQUEST['lote']) != "") {
        $condicion .= " AND T1.Lote LIKE '%$_REQUEST[lote]%'";
    }
    
    if (isset($_REQUEST['fecha_vencimiento']) && trim($_REQUEST['fecha_vencimiento']) != "") {
        $condicion .= " AND T1.Fecha_Vencimiento = '$_REQUEST[fecha_vencimiento]'";
    }

    try{

        $query = 'SELECT COUNT(T1.Id_Producto)  AS Total
                FROM Inventario T1
                INNER JOIN Punto_Dispensacion T2 ON T1.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion
                WHERE
                    T1.Id_Punto_Dispensacion <> 0 AND
                    T1.Id_Bodega = 0 AND
                    T1.Id_Producto = '.$producto_id.' '.$condicion;

        $oCon= new consulta();
        $oCon->setQuery($query);
        $total = $oCon->getData();
        unset($oCon);

        ####### PAGINACIÓN ######## 
        $tamPag = 10; 
        $numReg = $total["Total"]; 
        $paginas = ceil($numReg/$tamPag); 
        $limit = ""; 
        $paginaAct = "";

        if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
        $paginaAct = 1; 
        $limit = 0; 
        } else { 
        $paginaAct = $_REQUEST['pag']; 
        $limit = ($paginaAct-1) * $tamPag; 
        } 

        $query = 'SELECT 
                    T1.Id_Producto,
                    T2.Nombre,
                    T1.Cantidad,
                    T1.Lote,
                    T1.Costo,
                    T1.Fecha_Vencimiento
                FROM Inventario T1
                INNER JOIN Punto_Dispensacion T2 ON T1.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion
                WHERE
                    T1.Id_Punto_Dispensacion <> 0 AND
                    T1.Id_Bodega = 0 AND
                    T1.Id_Producto = '.$producto_id.' '.$condicion.' LIMIT '.$limit.','.$tamPag;

        $dbObj= new consulta();
        $dbObj->setQuery($query);
        $dbObj->setTipo('multiple');
        $result['producto_puntos'] = $dbObj->getData();
        unset($dbObj);

        $result['numero_registros'] = $numReg;

        // var_dump($result);
        // exit;
        
        echo json_encode($result);
    }catch(Exception $e){

    }*/

    $condicion = '';
    $condicion_punto_bodega = '';
    $completar_query = array('campo_tabla' => '',
                            'join_tabla' => '',
                            'condicion' => '');

    

    $producto_id = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
    $mostrar_puntos = $_REQUEST['mostrar_puntos'];
    $mostrar_bodegas = $_REQUEST['mostrar_bodegas'];

    if ($mostrar_puntos == 'true' && $mostrar_bodegas == 'false') {
        $condicion_punto_bodega = ' AND E.Id_Punto_Dispensacion <> 0 ';
        $completar_query['campo_tabla'] = ',T2.Nombre';
        $completar_query['join_tabla'] = 'INNER JOIN Estiba E on E.Id_Estiba= T1.Id_Estiba INNER JOIN Punto_Dispensacion T2 ON E.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion';
        $completar_query['condicion_tabla'] = ' HAVING Cantidad > 0';


    }else if($mostrar_puntos == 'false' && $mostrar_bodegas == 'true'){
        $condicion_punto_bodega = ' AND E.Id_Bodega_Nuevo <> 0';
        $completar_query['campo_tabla'] = ', T3.Nombre, (T1.Cantidad-T1.Cantidad_Apartada-T1.Cantidad_Seleccionada) AS Quantity';
        $completar_query['join_tabla'] = ' INNER JOIN Estiba E on E.Id_Estiba= T1.Id_Estiba  INNER JOIN Bodega_Nuevo T3 ON E.Id_Bodega_Nuevo = T3.Id_Bodega_Nuevo';
        $completar_query['condicion_tabla'] = ' HAVING Quantity > 0';

    }else if($mostrar_puntos == 'true' && $mostrar_bodegas == 'true'){
        $consulta_nombre_punto = 'SELECT Nombre FROM Punto_Dispensacion WHERE Nombre LIKE "%'.$_REQUEST['nombre_punto'].'%"';

        $consulta_nombre_bodega = 'SELECT Nombre FROM Bodega_Nuevo WHERE Nombre LIKE "%'.$_REQUEST['nombre_punto'].'%"';


        $condicion_punto_bodega = '';
        $completar_query['campo_tabla'] = ',IFNULL(T2.Nombre,CONCAT("Bodega: ", T3.Nombre)) AS Nombre';
        $completar_query['join_tabla'] = 'INNER JOIN Estiba E on  E.Id_Estiba= T1.Id_Estiba  LEFT JOIN Punto_Dispensacion T2 ON E.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion LEFT JOIN Bodega_Nuevo T3 ON E.Id_Bodega_Nuevo = T3.Id_Bodega_Nuevo';
        $completar_query['condicion_tabla'] = ' HAVING Cantidad > 0';
    }
    

    if (isset($_REQUEST['nombre_punto']) && trim($_REQUEST['nombre_punto']) != "") {
        
        if ($mostrar_puntos == 'true' && $mostrar_bodegas == 'false') {
            $condicion_punto_bodega = ' AND E.Id_Punto_Dispensacion <> 0 ';
            $completar_query['campo_tabla'] = ',T2.Nombre, T1.Cantidad';
            $completar_query['join_tabla'] = 'INNER JOIN Estiba E on E.Id_Estiba= T1.Id_Estiba INNER JOIN Punto_Dispensacion T2 ON E.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion';
            // $completar_query['join_tabla'] = 'INNER JOIN Punto_Dispensacion T2 ON T1.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion';
            $completar_query['condicion_tabla'] = " AND T2.Nombre LIKE '%$_REQUEST[nombre_punto]%' HAVING T1.Cantidad > 0";


        }else if($mostrar_puntos == 'false' && $mostrar_bodegas == 'true'){
            $condicion_punto_bodega = ' AND E.Id_Bodega_Nuevo <> 0';
            $completar_query['campo_tabla'] = ',T3.Nombre, (T1.Cantidad-T1.Cantidad_Apartada-T1.Cantidad_Seleccionada) AS Quantity';
             $completar_query['join_tabla'] = ' INNER JOIN Estiba E on E.Id_Estiba= T1.Id_Estiba  INNER JOIN Bodega_Nuevo T3 ON E.Id_Bodega_Nuevo = T3.Id_Bodega_Nuevo';
            $completar_query['condicion_tabla'] = " AND T3.Nombre LIKE '%$_REQUEST[nombre_punto]%' HAVING Quantity > 0";

        }else if($mostrar_puntos == 'true' && $mostrar_bodegas == 'true'){
            $consulta_nombre_punto = 'SELECT Nombre FROM Punto_Dispensacion WHERE Nombre LIKE "%'.$_REQUEST['nombre_punto'].'"%';

            $consulta_nombre_bodega = 'SELECT Nombre FROM Bodega_Nuevo WHERE Nombre LIKE "%'.$_REQUEST['nombre_punto'].'%"';


            $condicion_punto_bodega = '';
            $completar_query['campo_tabla'] = ',IFNULL(T2.Nombre,CONCAT("Bodega: ", T3.Nombre)) AS Nombre';
        $completar_query['join_tabla'] = 'INNER JOIN Estiba E on  E.Id_Estiba= T1.Id_Estiba  LEFT JOIN Punto_Dispensacion T2 ON E.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion LEFT JOIN Bodega_Nuevo T3 ON E.Id_Bodega_Nuevo = T3.Id_Bodega_Nuevo';
        // $completar_query['join_tabla'] = 'LEFT JOIN Punto_Dispensacion T2 ON T1.Id_Punto_Dispensacion = T2.Id_Punto_Dispensacion LEFT JOIN Bodega T3 ON T1.Id_Bodega = T3.Id_Bodega';
            $completar_query['condicion_tabla'] = " AND (T2.Nombre LIKE '%$_REQUEST[nombre_punto]%' OR T3.Nombre LIKE '%$_REQUEST[nombre_punto]%') HAVING Cantidad > 0";
        }
    }
    
    if (isset($_REQUEST['cantidad']) && trim($_REQUEST['cantidad']) != "") {
        $condicion .= " AND T1.Cantidad = $_REQUEST[cantidad]";
    }
    
    if (isset($_REQUEST['costo']) && trim($_REQUEST['costo']) != "") {
        $condicion .= " AND T1.Costo = $_REQUEST[costo]";
    }
    
    if (isset($_REQUEST['lote']) && trim($_REQUEST['lote']) != "") {
        $condicion .= " AND T1.Lote LIKE '%$_REQUEST[lote]%'";
    }
    
    if (isset($_REQUEST['fecha_vencimiento']) && trim($_REQUEST['fecha_vencimiento']) != "") {
        $condicion .= " AND T1.Fecha_Vencimiento = '$_REQUEST[fecha_vencimiento]'";
    }

    try{

        /*$query = 'SELECT COUNT(*)  AS Total
                FROM Inventario T1
                '.$completar_query['join_tabla'].'
                WHERE
                    T1.Id_Producto = '.$producto_id
                    .$condicion_punto_bodega
                    .$condicion
                    .$completar_query['condicion_tabla'];*/

        $query = 'SELECT 
                    T1.Id_Producto,
                    T1.Cantidad,
                    T1.Lote,
                    T1.Costo,
                    T1.Fecha_Vencimiento
                    '.$completar_query['campo_tabla'].'
                FROM Inventario_Nuevo T1
                '.$completar_query['join_tabla'].'
                WHERE
                    T1.Id_Producto = '.$producto_id
                    .$condicion_punto_bodega
                    .$condicion
                    .$completar_query['condicion_tabla'];

                    // echo $query; exit;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('multiple');
        $total = $oCon->getData();
        unset($oCon);

        ####### PAGINACIÓN ######## 
        $tamPag = 10; 
        //$numReg = $total["Total"]; 
        $numReg = count($total); 
        $paginas = ceil($numReg/$tamPag); 
        $limit = ""; 
        $paginaAct = "";

        if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
        $paginaAct = 1; 
        $limit = 0; 
        } else { 
        $paginaAct = $_REQUEST['pag']; 
        $limit = ($paginaAct-1) * $tamPag; 
        } 

        $query = 'SELECT 
                    T1.Id_Producto,
                    (T1.Cantidad-T1.Cantidad_Apartada-T1.Cantidad_Seleccionada) AS Cantidad,
                    T1.Lote,
                    T1.Costo,
                    T1.Fecha_Vencimiento
                    '.$completar_query['campo_tabla'].'
                FROM Inventario_Nuevo T1
                '.$completar_query['join_tabla'].'
                WHERE
                    T1.Id_Producto = '.$producto_id
                    .$condicion_punto_bodega
                    .$condicion
                    .$completar_query['condicion_tabla'].' LIMIT '.$limit.','.$tamPag;


        $dbObj= new consulta();
        $dbObj->setQuery($query);
        $dbObj->setTipo('multiple');
        $result['producto_puntos'] = $dbObj->getData();
        unset($dbObj);

        $result['numero_registros'] = $numReg;
        
        echo json_encode($result);
    }catch(Exception $e){

    }

?>