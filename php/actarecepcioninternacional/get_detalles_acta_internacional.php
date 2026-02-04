<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $id_acta = ( isset( $_REQUEST['id_acta'] ) ? $_REQUEST['id_acta'] : '' );

    $query = '
        SELECT 
            ARI.*,
            BN.Nombre AS Bodega,
            P.Direccion,
            P.Telefono,
            OCI.Codigo AS Codigo_Orden,
            OCI.Fecha_Registro AS Fecha_Orden,
            CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Proveedor,
            (SELECT COUNT(Id_Producto_Orden_Compra_Internacional) FROM Producto_Orden_Compra_Internacional WHERE Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional) AS Productos_Ingresados
        FROM Acta_Recepcion_Internacional ARI
        INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
        INNER JOIN Bodega_Nuevo BN ON OCI.Id_Bodega_Nuevo = BN.Id_Bodega_Nuevo
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        WHERE
            ARI.Id_Acta_Recepcion_Internacional = '.$id_acta;
    
    $queryObj = new QueryBaseDatos($query);
    $acta = $queryObj->Consultar('simple');

    if ($acta['query_result'] != '') {
        
        $acta['facturas'] = GetFacturasActa($id_acta);
        $acta['productos'] = GetProductosActa($id_acta);
        $acta['query_result']['total'] = GetTotalActa($acta['productos']);
    }


    echo json_encode($acta);

    function GetFacturasActa($id_acta){
        global $queryObj;

        $facturas = array();

        $query = '
            SELECT 
                Factura,
                Fecha_Factura,
                Archivo_Factura
            FROM Factura_Acta_Recepcion_Internacional
            WHERE
                Id_Acta_Recepcion_Internacional = '.$id_acta;

        $queryObj->SetQuery($query);
        $facturas = $queryObj->ExecuteQuery('multiple');

        return $facturas;
    }

    function GetProductosActa($id_acta){
        global $queryObj;

        $productos = array();

        $query_productos = '
            SELECT 
                POCI.Id_Producto_Orden_Compra_Internacional,
                P.Nombre_Comercial,
                IFNULL(P.Nombre_Listado, "No english name") AS Nombre_Ingles,
                POCI.Cantidad AS Cantidad_Orden
            FROM Producto_Acta_Recepcion_Internacional PARI
            INNER JOIN Producto_Orden_Compra_Internacional POCI ON PARI.Id_Producto = POCI.Id_Producto AND PARI.Id_Producto_Orden_Compra_Internacional = POCI.Id_Producto_Orden_Compra_Internacional
            INNER JOIN Producto P ON PARI.Id_Producto = P.Id_Producto
            WHERE
                PARI.Id_Acta_Recepcion_Internacional = '.$id_acta.
            ' GROUP BY PARI.Id_Producto';

        $queryObj->SetQuery($query_productos);
        $productos = $queryObj->ExecuteQuery('multiple');

        $i = 0;
        foreach ($productos as $p) {
            
            $query_lotes = '
                SELECT 
                    POCI.Cantidad AS Cantidad_Orden,
                    PARI.Cantidad AS Cantidad_Acta,
                    PARI.Lote,
                    PARI.Fecha_Vencimiento,
                    PARI.Precio,
                    PARI.Impuesto,
                    PARI.Subtotal
                FROM Producto_Acta_Recepcion_Internacional PARI
                INNER JOIN Producto_Orden_Compra_Internacional POCI ON PARI.Id_Producto = POCI.Id_Producto AND PARI.Id_Producto_Orden_Compra_Internacional = POCI.Id_Producto_Orden_Compra_Internacional
                WHERE
                    PARI.Id_Producto_Orden_Compra_Internacional = '.$p['Id_Producto_Orden_Compra_Internacional'];

            $queryObj->SetQuery($query_lotes);
            $productos[$i]['Lotes'] = $queryObj->ExecuteQuery('multiple');

            $i++;
        }

        

        return $productos;
    }

    function GetTotalActa($productos){
        $total_acta = 0;

        foreach ($productos as $p) {
            
            foreach ($p['Lotes'] as $lote) {
                
                $total_acta += floatval($lote['Subtotal']);
            }
        }

        return $total_acta;
    }
          
?>