<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
	$util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones($_REQUEST);
    //$having = SetHaving($_REQUEST);

    $query = "SELECT 
            IMP.*,
            P.Nombre_Comercial,
            IFNULL(P.Nombre_Listado, 'English name not set') AS Nombre_Ingles,
            P.Imagen,
            IFNULL(P.Invima, 'No Registrado') AS Invima,
            P.Codigo_Cum,
            IFNULL((SELECT SUM(PNP.Cantidad)  FROM Producto_Nacionalizacion_Parcial PNP INNER JOIN Nacionalizacion_Parcial NP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial WHERE PNP.Id_Producto_Acta_Recepcion_Internacional = IMP.Id_Producto_Acta_Recepcion_Internacional AND NP.Estado !='Anulado'), 0) AS Cantidad_Parciales,
            OCI.Codigo AS Codigo_Orden,
            P.Gravado,
            P.Embalaje
        FROM Importacion IMP
        INNER JOIN Producto P ON IMP.Id_Producto = P.Id_Producto
        INNER JOIN Producto_Acta_Recepcion_Internacional PARI ON IMP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
        INNER JOIN Acta_Recepcion_Internacional ARI ON PARI.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
        INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
        $condicion ORDER BY ARI.Id_Acta_Recepcion_Internacional desc";

    $query_count = "SELECT 
            COUNT(Id_Importacion) AS Total
        FROM Importacion IMP
        INNER JOIN Producto P ON IMP.Id_Producto = P.Id_Producto
        INNER JOIN Producto_Acta_Recepcion_Internacional PARI ON IMP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
        INNER JOIN Acta_Recepcion_Internacional ARI ON PARI.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
        INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
        $condicion";
	

    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj->SetQuery($query);
    $productos = $queryObj->Consultar('Multiple', true, $paginationData);
    unset($http_response);
    unset($queryObj);

	echo json_encode($productos);

	function SetCondiciones($req){
        global $util;
        $condicion = "";
        $reglas=[];

        if (isset($req['nombre']) && $req['nombre']) {
            $req['nombre'] = str_replace(" ", "%", $req['nombre']);
            array_push($reglas, "AND P.Nombre_Comercial LIKE '%$req[nombre]%'");
        }

        if (isset($req['orden_compra']) && $req['orden_compra']) {
            array_push($reglas, " AND OCI.Codigo LIKE '%$req[orden_compra]%'");
        }

        if (isset($req['codigo_cum']) && $req['codigo_cum']) {
            array_push($reglas,  " AND P.Codigo_Cum LIKE '%$req[codigo_cum]%'");
        }

        if (isset($req['lote']) && $req['lote']) {
            array_push($reglas,  " AND IMP.Lote LIKE '%$req[lote]%'");
        }

        if (isset($req['fecha_vencimiento']) && $req['fecha_vencimiento']) {
            $fechas = $util->SepararFechas($req['fecha_vencimiento']);
            array_push($reglas, " AND IMP.Fecha_Vencimiento BETWEEN '$fechas[0]' AND '$fechas[1]' ");
        }
        $condicion = "WHERE 1 " .implode("", $reglas);
        return $condicion;
	}


?>