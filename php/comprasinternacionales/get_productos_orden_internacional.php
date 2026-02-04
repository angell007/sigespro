<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $codigo_orden = ( isset( $_REQUEST['codigo_orden'] ) ? $_REQUEST['codigo_orden'] : '' );

    $query = '
        SELECT 
            POCI.*,
            OCI.Codigo AS Codigo_Compra,
            PR.Nombre_Comercial,
            PR.Embalaje,
            PR.Imagen AS Foto,
            PR.Codigo_Barras,
            PR.Gravado,
            false AS Archivo_Producto,
            "" AS Id_No_Conforme,
            0 AS Cantidad_No_Conforme,
            true AS Habilitar_No_Conforme
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Producto_Orden_Compra_Internacional POCI ON OCI.Id_Orden_Compra_Internacional = POCI.Id_Orden_Compra_Internacional
        INNER JOIN Producto PR ON POCI.Id_Producto = PR.Id_Producto
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        WHERE
            OCI.COdigo = "'.$codigo_orden.'"';
    
    $queryObj = new QueryBaseDatos($query);
    $productos_orden = $queryObj->Consultar('Multiple');

    if (count($productos_orden['query_result']) > 0) {
        $i = 0;
        foreach ($productos_orden['query_result'] as $p) {
            $productos_orden['query_result'][$i]['Producto_Lotes'] = array();
            $productos_orden['query_result'][$i]['Producto_Lotes'][] = array('Id_Producto_Acta_Recepcion_Internacional' => '', 'Id_Producto_Orden_Compra_Internacional' => $p['Id_Producto_Orden_Compra_Internacional'], 'Id_Acta_Recepcion_Internacional' => '',  'Id_Producto' => $p['Id_Producto'], 'Cantidad' => '0', 'Precio' => $p['Costo'], 'Impuesto' => $p['Gravado'] == 'Si' ? '19' : '0', 'Subtotal' => '0', 'Lote' => '', 'Fecha_Vencimiento' => '', 'Codigo_Compra' => $p['Codigo_Compra'], 'Factura' => '', 'Required' => true, 'Id_No_Conforme' => '', 'Cantidad_No_Conforme' => '0');
            $i++;
        }
    }

    $productos_orden['orden_compra'] = GetOrdenCompra($codigo_orden);

    echo json_encode($productos_orden);

    function GetOrdenCompra($codigoOrden){
        global $queryObj;
 		$query = '
            SELECT 
                OCI.*,
                BN.Nombre As Bodega
            FROM Orden_Compra_Internacional OCI
         
            INNER JOIN Bodega_Nuevo BN ON OCI.Id_Bodega_Nuevo = BN.Id_Bodega_Nuevo
            WHERE
                COdigo = "'.$codigoOrden.'"';

        $queryObj->SetQuery($query);
        $orden = $queryObj->ExecuteQuery('simple');
        return $orden;
    }
          
?>