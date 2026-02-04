<?php
	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

    $tipo = isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : false;

    $q = "SELECT Codigo FROM Radicado WHERE Id_Radicado = $_REQUEST[id]";
    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($q);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $datos = $queryObj->Consultar('simple');
    unset($queryObj);

    $consecutivo_rips = consecutivoRips($datos['query_result']['Codigo']);

	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: text/plain; ');
    header('Content-Disposition: attachment; filename="'.$consecutivo_rips.'.txt"');
    
    switch ($tipo) {
        case 'AD':
            include('./RIPS/formato_ad.php');
            break;
        case 'AF':
            include('./RIPS/formato_af.php');
            break;
        case 'US':
            include('./RIPS/formato_us.php');
            break;
        case 'AM':
            include('./RIPS/formato_am.php');
            break;
        case 'AT':
            include('./RIPS/formato_at.php');
            break;
        case 'CT':
            include('./RIPS/formato_ct.php');
            break;
    }

    function consecutivoRips($consecutivo){
        global $tipo;
        $consecutivo = substr($consecutivo,3);
        $cons = $tipo . str_pad($consecutivo,6,'0',STR_PAD_LEFT);
        return $cons;
    }

    function isNumeric($columna) {
        $columnas_numericas = ["Total_Precio","Neto_Factura","Cuota_Moderadora","Total_Iva","Total_Descuentos","Total_Factura"];
        return in_array($columna, $columnas_numericas);
    }
?>