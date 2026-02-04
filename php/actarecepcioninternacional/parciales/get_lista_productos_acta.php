<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.paginacion.php');
	include_once('../../../class/class.http_response.php');
	include_once('../../../class/class.utility.php');

	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
	$util = new Utility();

    $id_acta = ( isset( $_REQUEST['id_acta'] ) ? $_REQUEST['id_acta'] : '' );

	// var_dump($_REQUEST);
	// exit;
	$condicion = SetCondiciones($_REQUEST, $id_acta);
    $having = SetHaving($_REQUEST);

	$fecha = date('Y-m-d');

    // $query = '
    //     SELECT 
    //         PARI.Id_Producto,
    //         PARI.Id_Producto_Acta_Recepcion_Internacional,
    //         IFNULL(P.Imagen, "") AS Imagen,
    //         P.Nombre_Comercial,
    //         IFNULL(P.Nombre_Listado, "No english name set") AS Nombre_Ingles,
    //         P.Laboratorio_Comercial,
    //         PARI.Lote,
    //         P.Embalaje,
    //         PARI.Precio AS Precio_Dolares,
    //         "0" AS FOT_Pesos,
    //         "0" AS Precio,
    //         (CASE
    //         	WHEN P.Gravado = "SI" THEN 19
    //         	WHEN P.Gravado = "No" THEN 0
    //          END) AS Gravado,
    //         PARI.Subtotal,
    //         (PARI.Cantidad - IFNULL(PNP.Cantidad, 0)) AS Cantidad_Disponible,
    //         P.Porcentaje_Arancel,
    //         false AS Seleccionado
    //     FROM Producto_Acta_Recepcion_Internacional PARI
    //     LEFT JOIN Producto_Nacionalizacion_Parcial PNP ON PARI.Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional
    //     INNER JOIN Producto P ON PARI.Id_Producto = P.Id_Producto'
    //     .$condicion
    //     .$having;

    $query = '
        SELECT 
            PARI.Id_Producto,
            PARI.Id_Producto_Acta_Recepcion_Internacional,
            IFNULL(P.Imagen, "") AS Imagen,
            P.Nombre_Comercial,
            IFNULL(P.Nombre_Listado, "No english name set") AS Nombre_Ingles,
            P.Laboratorio_Comercial,
            PARI.Lote,
            P.Embalaje,
            (PARI.Precio) AS Precio_Dolares,
            PARI.Cantidad,
            "0" AS FOT_Pesos,
            "0" AS Precio,
            (CASE
                WHEN P.Gravado = "SI" THEN 19
                WHEN P.Gravado = "No" THEN 0
             END) AS Gravado,
            PARI.Subtotal,
            P.Porcentaje_Arancel,
            PARI.Factura,
            false AS Seleccionado
        FROM Producto_Acta_Recepcion_Internacional PARI
        INNER JOIN Producto P ON PARI.Id_Producto = P.Id_Producto'
        .$condicion
        .$having;
	
    $queryObj->SetQuery($query);
    $productos = $queryObj->Consultar('Multiple');

    if (count($productos['query_result']) > 0) {
        
        $in_condition = ConcatenarIdProductos($productos['query_result']);

        $query_productos_parciales_acta = '
            SELECT 
                Id_Producto_Acta_Recepcion_Internacional,
                SUM(Cantidad) AS Cantidad
            FROM Producto_Nacionalizacion_Parcial PARI
            INNER JOIN Nacionalizacion_Parcial ARI ON PARI.Id_Nacionalizacion_Parcial = ARI.Id_Nacionalizacion_Parcial
            WHERE
                Id_Producto_Acta_Recepcion_Internacional IN ('.$in_condition.')
                AND ARI.Estado != "Anulado"
            GROUP BY Id_Producto_Acta_Recepcion_Internacional';
        
        $queryObj->SetQuery($query_productos_parciales_acta);
        $productos_parcial = $queryObj->ExecuteQuery('Multiple');

        //$productos['query_result'] = array();
        $productos['query_result'] = RestarCantidades($productos['query_result'], $productos_parcial);
    }

    unset($http_response);
    unset($queryObj);

	echo json_encode($productos);

	function SetCondiciones($req, $id_acta){
        global $util;

        if (isset($req['productos_excluir'])) {
            $req['productos_excluir'] =(array) json_decode($req['productos_excluir'], true);
        }

		$condicion = ' WHERE PARI.Id_Acta_Recepcion_Internacional = '.$id_acta.' ';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND P.Nombre_Comercial LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE P.Nombre_Comercial LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['lote']) && $req['lote']) {
            if ($condicion != "") {
                $condicion .= " AND PARI.Lote LIKE '%".$req['lote']."%'";
            } else {
                $condicion .= " WHERE PARI.Lote LIKE '%".$req['lote']."%'";
            }
        }

        if (isset($req['productos_excluir']) && count($req['productos_excluir']) > 0) {
            $in_condition = $util->ArrayToCommaSeparatedString($req['productos_excluir']);

            if ($condicion != "") {
                $condicion .= " AND PARI.Id_Producto_Acta_Recepcion_Internacional NOT IN (".$in_condition.")";
            } else {
                $condicion .= " WHERE PARI.Id_Producto_Acta_Recepcion_Internacional NOT IN (".$in_condition.")";
            }
        }

        return $condicion;
	}

    function SetHaving($req){

        $having = '';

        if (isset($req['cantidad']) && $req['cantidad'] != '') {
            $condicion .= " HAVING Cantidad_Disponible = ".$req['cantidad'];
        }

        return $condicion;
    }

    function ConcatenarIdProductos($productos){
        $cadena = '';

        foreach ($productos as $p) {
            
            $cadena .= $p['Id_Producto_Acta_Recepcion_Internacional'].', ';
        }

        return trim($cadena, ", ");
    }

    function RestarCantidades($productos_acta, $productos_parcial){
        //var_dump($productos_acta);
        $j = 0;
        foreach ($productos_acta as $pa) {
            $cantidad = 0;

            for ($i=0; $i < count($productos_parcial); $i++) { 
                if ($pa['Id_Producto_Acta_Recepcion_Internacional'] == $productos_parcial[$i]['Id_Producto_Acta_Recepcion_Internacional']) {
                    $cantidad = intval($productos_parcial[$i]['Cantidad']);
                    //break;
                }
            }

            if ($cantidad != 0) {
                $resta = intval($productos_acta[$j]['Cantidad']) - $cantidad;
                if ($resta == 0) {
                    unset($productos_acta[$j]);
                }else{
                    $productos_acta[$j]['Cantidad_Disponible'] = intval($productos_acta[$j]['Cantidad']) - $cantidad;
                }
            }else{
                $productos_acta[$j]['Cantidad_Disponible'] = $productos_acta[$j]['Cantidad'];
            }

            $j++;
        }

        //echo json_encode($productos_acta);
        //var_dump($productos_acta);
        // exit;
        return array_values($productos_acta);
    }

    function CalcularFOT($id_acta){
    	$calculos = array('flete' => 0, 'seguro' => 0);
    	$orden_internacional = GetOrdenCompra($id_acta);
    	$total = GetTotalOrdenCompra($orden_internacional['Id_Orden_Compra_Internacional']);

    	$calculo_flete_interancional = (floatval($orden_internacional['Flete_Internacional']) / $total);
    	$calculo_seguro_interancional = (floatval($orden_internacional['Seguro_Internacional']) / $total);
    	
    	$calculos['flete'] = number_format($calculo_flete_interancional, 6, ".", "");
    	$calculos['seguro'] = number_format($calculo_seguro_interancional,6,".","");

    	return $calculos;
    }

    function GetOrdenCompra($id_acta){
    	global $queryObj;

    	$query = '
	        SELECT 
	            OCI.*
	        FROM Acta_Recepcion_Internacional ARI
	        INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
	        WHERE
	        	ARI.Id_Acta_Recepcion_Internacional = '.$id_acta;

    	$queryObj->SetQuery($query);
    	$orden = $queryObj->ExecuteQuery('simple');

	    return $orden;
    }

    function GetTotalOrdenCompra($id_orden){
    	global $queryObj;

    	$query = '
	        SELECT 
	            SUM(Subtotal) AS Total_Orden
	        FROM Producto_Orden_Compra_Internacional
	        WHERE
	        	Id_Orden_Compra_Internacional = '.$id_orden;

    	$queryObj->SetQuery($query);
    	$total = $queryObj->ExecuteQuery('simple');

	    return $total['Total_Orden'];
    }

    function CalcularFotProductos($productos, $calculos){

    	$i = 0;
    	foreach ($productos as $p) {
    		
    		$productos[$i]['Flete_Porcentaje'] = ($calculos['flete']*100)." %";
    		$productos[$i]['Seguro_Porcentaje'] = ($calculos['seguro']*100)." %";
    		$productos[$i]['FOT_Dolares'] = (floatval($p['Precio_Dolares']) + (floatval($p['Precio_Dolares']) * $calculos['flete']) + (floatval($p['Precio_Dolares']) * $calculos['seguro']));
    		$i++;
    	}

    	return $productos;
    }


?>