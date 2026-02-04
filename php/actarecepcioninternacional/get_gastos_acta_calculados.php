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
	$util = new Utility();
    $queryObj = new QueryBaseDatos();

    $id_acta = ( isset( $_REQUEST['id_acta'] ) ? $_REQUEST['id_acta'] : '' );

	// var_dump($_REQUEST);
	// exit;

    $calculos = CalcularFOT($id_acta);
    $http_response->SetRespuesta(0, 'Consulta Exitosa!', 'Se han encontrado datos de los gastos del acta!');
    $http_response->SetDatosRespuesta($calculos);
    $response = $http_response->GetRespuesta();

    unset($http_response);
    unset($queryObj);

	echo json_encode($response);

    function CalcularFOT($id_acta){
    	$calculos = array('flete' => 0, 'seguro' => 0);
    	$acta = GetActa($id_acta);
    	$total = GetTotalOrdenCompra($acta['Id_Orden_Compra_Internacional']);
        $tasa = GetTasaOrdenCompra($acta['Id_Orden_Compra_Internacional']);
        $total_pesos = (float)$tasa * (float)$total;
        $conteo_productos_acta = GetConteoProductosActa($id_acta);
        
        
        $total_dolares_orden= $total + floatval($acta['Flete_Internacional']) + floatval($acta['Seguro_Internacional']);
        $total_pesos_orden= $total_dolares_orden * $tasa;

    	$calculo_flete_interancional = number_format((floatval($acta['Flete_Internacional']) / $total),5,".",""); 
    	$calculo_seguro_interancional = number_format((floatval($acta['Seguro_Internacional']) / $total),5,".",""); 
    	
        //$calculo_flete_nacional = (floatval($acta['Flete_Nacional']) / $conteo_productos_acta); // Agregado Augusto Carrillo 26 Julio 2024
        //$calculo_licencia_importacion = (floatval($acta['Licencia_Importacion']) / $conteo_productos_acta); // Agregado Augusto Carrillo 26 Julio 2024
        //$calculo_gasto_bancario = (floatval($acta['Gasto_Bancario']) / $conteo_productos_acta); // Agregado Augusto Carrillo 26 Julio 2024
        //$calculo_cargue = (floatval($acta['Cargue']) / $conteo_productos_acta); // Agregado Augusto Carrillo 26 Julio 2024
        
        $calculo_flete_nacional = (floatval($acta['Flete_Nacional']) / $total_pesos_orden) ; // Agregado Augusto Carrillo 26 Julio 2024
        $calculo_licencia_importacion = (floatval($acta['Licencia_Importacion']) / $total_pesos_orden) ; // Agregado Augusto Carrillo 26 Julio 2024
        $calculo_gasto_bancario = (floatval($acta['Gasto_Bancario']) / $total_pesos_orden) ; // Agregado Augusto Carrillo 26 Julio 2024
        $calculo_cargue = (floatval($acta['Cargue']) / $total_pesos_orden) ; // Agregado Augusto Carrillo 26 Julio 2024
        

        // var_dump($tasa);
        // var_dump($total);
        // var_dump($total_pesos);
    	
    	// $calculos['flete'] = number_format($calculo_flete_interancional, 6, ".", "");
    	// $calculos['seguro'] = number_format($calculo_seguro_interancional,6,".","");
     //    $calculos['flete_nac'] = number_format($calculo_flete_nacional,2,".","");
     //    $calculos['licencia'] = number_format($calculo_licencia_importacion,2,".","");

        $calculos['flete'] = $calculo_flete_interancional;
        $calculos['seguro'] = $calculo_seguro_interancional;
        $calculos['flete_nac'] = $calculo_flete_nacional;
        $calculos['licencia'] = $calculo_licencia_importacion;
        $calculos['gasto_bancario'] = $calculo_gasto_bancario;
        $calculos['cargue'] = $calculo_cargue;

    	return $calculos;
    }

    function GetActa($id_acta){
    	global $queryObj;

    	$query = '
	        SELECT 
	            *
	        FROM Acta_Recepcion_Internacional
	        WHERE
	        	Id_Acta_Recepcion_Internacional = '.$id_acta;

    	$queryObj->SetQuery($query);
    	$acta = $queryObj->ExecuteQuery('simple');

	    return $acta;
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

    function GetTasaOrdenCompra($id_orden){
        global $queryObj;

        $query = '
            SELECT 
                Tasa_Dolar
            FROM Orden_Compra_Internacional
            WHERE
                Id_Orden_Compra_Internacional = '.$id_orden;

        $queryObj->SetQuery($query);
        $total = $queryObj->ExecuteQuery('simple');

        return $total['Tasa_Dolar'];
    }

    function GetConteoProductosActa($id_acta){
        global $queryObj;

        $query = '
            SELECT 
                SUM(Cantidad) AS Total
            FROM Producto_Acta_Recepcion_Internacional
            WHERE
                Id_Acta_Recepcion_Internacional = '.$id_acta;

        $queryObj->SetQuery($query);
        $conteo = $queryObj->ExecuteQuery('simple');

        if ($conteo['Total']) {
        	return floatval($conteo['Total']);
        }else{
        	return 0;
        }
    }

?>