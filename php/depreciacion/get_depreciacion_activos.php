<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $anio = ( isset( $_REQUEST['anio'] ) ? $_REQUEST['anio'] : '' );
    $mes = ( isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '' );
    $tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

    $fecha_inicio = $anio."-".$mes."-01";
    $fecha_tope = $anio."-".$mes."-".date("d",(mktime(0,0,0,$mes+1,1,$anio)-1));
    var_dump($fecha_tope);
    $having = 'HAVING (Fecha_Inicio <= "'.$fecha_inicio.'" AND  Fecha_Fin_depreciacion >= "'.$fecha_tope.'") OR (Fecha_Inicio LIKE "'.$anio.'-'.$mes.'%") OR (Fecha_Fin_depreciacion LIKE "'.$anio.'-'.$mes.'%")';

    $vida_util_tipo= '';

    if (strtolower($tipo) == 'pcga') {
        
        $vida_util_tipo = 'TAF.Vida_Util_PCGA';
    }else{
    	$vida_util_tipo = 'TAF.Vida_Util';
    }

    $query = '
        SELECT 
            TAF.Id_Tipo_Activo_Fijo,
            TAF.Nombre_Tipo_Activo,
            TAF.Vida_Util AS Vida_Util_Niif,
            TAF.Vida_Util_PCGA,
            TAF.Sin_Depreciacion_Niff,
            (CONCAT_WS(" - ", PCDN.Codigo, PCDN.Nombre)) AS Cuenta_Depreciacion_Niif,
            (CONCAT_WS(" - ", PCDP.Codigo, PCDP.Nombre)) AS Cuenta_Depreciacion_Pcga,
            (CONCAT_WS(" - ", PCCDP.Codigo, PCCDP.Nombre)) AS Cuenta_Depreciacion_Credito_Niif,
            (CONCAT_WS(" - ", PCCDN.Codigo, PCCDN.Nombre)) AS Cuenta_Depreciacion_Credito_Pcga,
            DATE(Fecha) AS Fecha_Inicio,
            IF(AF.Tipo_Depreciacion = 0, DATE((DATE_ADD(AF.Fecha, INTERVAL '.$vida_util_tipo.' MONTH ))), DATE((DATE_ADD(AF.Fecha, INTERVAL 1 MONTH ))) ) AS Fecha_Fin_depreciacion
        FROM Activo_Fijo AF
        INNER JOIN Tipo_Activo_Fijo TAF ON AF.Id_Tipo_Activo_Fijo = TAF.Id_Tipo_Activo_Fijo
        INNER JOIN Plan_Cuentas PCDN ON TAF.Id_Plan_Cuenta_Depreciacion_NIIF = PCDN.Id_Plan_Cuentas
        INNER JOIN Plan_Cuentas PCDP ON TAF.Id_Plan_Cuenta_Depreciacion_PCGA = PCDP.Id_Plan_Cuentas
        INNER JOIN Plan_Cuentas PCCDP ON TAF.Id_Plan_Cuenta_Credito_Depreciacion_PCGA = PCCDP.Id_Plan_Cuentas
        INNER JOIN Plan_Cuentas PCCDN ON TAF.Id_Plan_Cuenta_Credito_Depreciacion_NIIF = PCCDN.Id_Plan_Cuentas
        GROUP BY AF.Id_Tipo_Activo_Fijo '
        .$having;

    $queryObj = new QueryBaseDatos($query);
    $tipos_activo = $queryObj->ExecuteQuery('Multiple');

    $query = '
        SELECT 
            AF.*,
            DATE(Fecha) AS Fecha_Inicio,
            IF(AF.Tipo_Depreciacion = 0, DATE((DATE_ADD(AF.Fecha, INTERVAL '.$vida_util_tipo.' MONTH ))), DATE((DATE_ADD(AF.Fecha, INTERVAL 1 MONTH ))) ) AS Fecha_Fin_depreciacion
        FROM Activo_Fijo AF 
        INNER JOIN Tipo_Activo_Fijo TAF ON AF.Id_Tipo_Activo_Fijo = TAF.Id_Tipo_Activo_Fijo '
        .$having;

    $queryObj = new QueryBaseDatos($query);
    $activos_fijos = $queryObj->ExecuteQuery('Multiple');

    $result = array();

    $result['tipos_activos'] = $tipos_activo;
    $result['activos_fijos'] = $activos_fijos;
    
    $result['activos_depreciacion'] = ArmarDepreciacionMensual($tipos_activo, $activos_fijos);

    echo json_encode($result);

    function ArmarDepreciacionMensual($tipos_activo, $activos_fijos){
        global $tipo;

        $activos_depreciados = array();

        $i = 0;
        foreach ($tipos_activo as $ta) {
            foreach ($activos_fijos as $af) {
	        	if ($af['Id_Tipo_Activo_Fijo'] == $ta['Id_Tipo_Activo_Fijo']) {
	            	$activo_depreciado = array('Id_Activo_Fijo'=> '', 'Id_Tipo_Activo_Fijo'=> '', 'Fecha_Activo' => '', 'Fecha_Fin_Depreciacion' => '', 'Monto_Depreciacion' => 0, 'Meses_Depreciacion' => 0, 'Depreciacion_Total' => 0, 'Total_Depreciacion_Acumulada_Anual' => 0, 'Depreciacion_Total_Acumulada' => 0, 'Restante_Por_Depreciar' => 0);

	                $activo_depreciado['Id_Activo_Fijo'] = $af['Id_Activo_Fijo'];
	                $activo_depreciado['Id_Tipo_Activo_Fijo'] = $ta['Id_Tipo_Activo_Fijo'];
	                $activo_depreciado['Fecha_Activo'] = $af['Fecha'];                
	                $activo_depreciado['Fecha_Fin_Depreciacion'] = $af['Fecha_Fin_depreciacion'];
	                $monto_depreciacion_activo = 0;

	                if ($tipo == 'PCGA') {
	                	$vida_util = $af['Tipo_Depreciacion'] == "0" ? intval($ta['Vida_Util_PCGA']) : 1;
	                    $porcentaje_depreciacion = CalcularPorcentajeDepreciacion($vida_util);

	                    //$fecha_fin_depreciacion_activo = GetFechaFinDepreciacion($af['Fecha'], $ta['Vida_Util_PCGA']);
	                    $monto_depreciacion_activo = CalcularDepreciacion(floatval($af["Costo_PCGA"]), $porcentaje_depreciacion);
	                    $meses_depreciacion_anual = GetMesesDepreciacionAnual($af['Fecha_Inicio'], $af['Fecha_Fin_depreciacion']);
	                    $meses_depreciacion_historica = GetMesesDepreciacionAcumulada($af['Fecha']);
	                    $depreciacion_acumulada_anual = CalcularDepreciacionAcumulada($meses_depreciacion_anual, $monto_depreciacion_activo);
	                    $depreciacion_historica = CalcularDepreciacionAcumulada($meses_depreciacion_historica, $monto_depreciacion_activo);

	                    $activo_depreciado['Total_Depreciacion_Acumulada_Anual'] = number_format($depreciacion_acumulada_anual, 2, ",", "");
	                    $activo_depreciado['Monto_Depreciacion'] = number_format($monto_depreciacion_activo, 2, ",", "");
	                    $activo_depreciado['Depreciacion_Total_Acumulada'] = number_format($depreciacion_historica, 2, ",", "");
	                    $activo_depreciado['Depreciacion_Total'] = round($monto_depreciacion_activo * $vida_util);
	                    $activo_depreciado['Meses_Depreciacion'] = $vida_util;
	                    $activo_depreciado['Restante_Por_Depreciar'] = number_format((round($monto_depreciacion_activo * $vida_util) - $depreciacion_historica), 2, ",", "");
	                    $valores_depreciacion_meses_anio = AsignarMontoDepreciarMeses($activo_depreciado);
	                    $activo_depreciado = array_merge($activo_depreciado, $valores_depreciacion_meses_anio);

	                    $activos_depreciados[] = $activo_depreciado;
	                }else{
	                	$vida_util = $af['Tipo_Depreciacion'] == "0" ? intval($ta['Vida_Util_Niif']) : 1;
	                    $porcentaje_depreciacion = CalcularPorcentajeDepreciacion($vida_util);
	                        
						//$fecha_fin_depreciacion_activo = GetFechaFinDepreciacion($af['Fecha'], $ta['Vida_Util_Niif']);
	                    $monto_depreciacion_activo = CalcularDepreciacion(floatval($af["Costo_NIIF"]), $porcentaje_depreciacion);
	                    $meses_depreciacion_anual = GetMesesDepreciacionAnual($af['Fecha_Inicio'], $af['Fecha_Fin_depreciacion']);
	                    $meses_depreciacion_historica = GetMesesDepreciacionAcumulada($af['Fecha']);
	                    $depreciacion_acumulada_anual = CalcularDepreciacionAcumulada($meses_depreciacion_anual, $monto_depreciacion_activo);
	                    $depreciacion_historica = CalcularDepreciacionAcumulada($meses_depreciacion_historica, $monto_depreciacion_activo);

	                    $activo_depreciado['Total_Depreciacion_Acumulada_Anual'] = number_format($depreciacion_acumulada_anual, 2, ",", "");
	                    $activo_depreciado['Monto_Depreciacion'] = number_format($monto_depreciacion_activo, 2, ",", "");
	                    $activo_depreciado['Depreciacion_Total_Acumulada'] = number_format($depreciacion_historica, 2, ",", "");
	                    $activo_depreciado['Depreciacion_Total'] = round($monto_depreciacion_activo * $vida_util);
	                    $activo_depreciado['Meses_Depreciacion'] = $vida_util;
	                    $activo_depreciado['Restante_Por_Depreciar'] = number_format((round($monto_depreciacion_activo * $vida_util) - $depreciacion_historica), 2, ",", "");
	                    $valores_depreciacion_meses_anio = AsignarMontoDepreciarMeses($activo_depreciado);
	                    $activo_depreciado = array_merge($activo_depreciado, $valores_depreciacion_meses_anio);

	                    $activos_depreciados[] = $activo_depreciado;
	                }	    
	            }
            }

            $i++;
        }

        return $activos_depreciados;
    }

    function GetMesesDepreciacionAnual($fecha_activo, $fecha_fin_depreciacion){
        global $util, $anio, $mes;

        $meses_depreciacion_anual = 0;
        
        $fecha_activo_separada = $util->SepararFecha($fecha_activo);
        $fecha_fin_depreciacion_separada = $util->SepararFecha($fecha_fin_depreciacion);

        if (intval($fecha_activo_separada[0]) < intval($anio) && intval($fecha_fin_depreciacion_separada[0]) > intval($anio)) {//LA DEPRECIACION DEL ACTIVO OCUPA TODO EL AÑO CONSULTADO
            
            $meses_depreciacion_anual = 1 - intval($mes);

        }elseif (intval($fecha_activo_separada[0]) == intval($anio) && intval($fecha_fin_depreciacion_separada[0]) > intval($anio)) {//LA DEPRECIACION DEL ACTIVO COMIENZA EN EL AÑO CONSULTADO

            if (intval($fecha_activo_separada[1]) < intval($mes)) {//LA DEPRECIACION DEL ACTIVO COMIENZA EN UN MES INFERIOR AL CONSULTADO
            	$meses_depreciacion_anual = intval($fecha_activo_separada[1]) - intval($mes);
            }elseif(intval($fecha_activo_separada[1]) >= intval($mes)){//LA DEPRECIACION DEL ACTIVO COMIENZA EN UN MES IGUAL O SUPERIOR AL CONSULTADO
				$meses_depreciacion_anual = 0;
            }

        }elseif (intval($fecha_activo_separada[0]) == intval($anio) && $fecha_fin_depreciacion_separada[0] == intval($anio)) {//LA DEPRECIACION DEL ACTIVO COMIENZA Y TERMINA EN EL AÑO CONSULTADO
            
            if (intval($fecha_activo_separada[1]) < intval($mes) && $fecha_fin_depreciacion_separada[1] < intval($mes)) {//LA DEPRECIACION DEL ACTIVO COMIENZa Y TERMINO EN UN MES INFERIOR AL CONSULTADO
            	$meses_depreciacion_anual = intval($fecha_activo_separada[1]) - intval($fecha_fin_depreciacion_separada[1]);
            }elseif (intval($fecha_activo_separada[1]) < intval($mes) && $fecha_fin_depreciacion_separada[1] > intval($mes)) {//LA DEPRECIACION DEL ACTIVO COMIENZA EN UN MES INFERIOR AL CONSULTADO PERO TERMINA MESES DESPUES DEL CONSULTADO
            	$meses_depreciacion_anual = intval($fecha_activo_separada[1]) - intval($mes);
            }elseif(intval($fecha_activo_separada[1]) >= intval($mes)){
				$meses_depreciacion_anual = 0;
            }

        }elseif (intval($fecha_activo_separada[0]) < intval($anio) && $fecha_fin_depreciacion_separada[0] < intval($anio)) {//LA DEPRECIACION DEL ACTIVO TERMINo AÑOS ANTES DEL CONSULTADO
            
            $meses_depreciacion_anual = 0;

        }elseif (intval($fecha_activo_separada[0]) > intval($anio) && $fecha_fin_depreciacion_separada[0] > intval($anio)) {//LA DEPRECIACION DEL ACTIVO COMIENZA AÑOS DESPUES DEL CONSULTADO
            
            $meses_depreciacion_anual = 0;
        }
 
        return abs($meses_depreciacion_anual);
    }

    function GetMesesDepreciacionAcumulada($fecha_activo){
        global $util, $anio, $mes;
        $meses_depreciacion = 0;

        $fecha_tope = $anio."-".$mes."-".date("d",(mktime(0,0,0,$mes+1,1,$anio)-1))." 23:59:59";
        $meses_depreciacion = $util->GetDiferenciaFechas($fecha_activo, $fecha_tope);

        return $meses_depreciacion;
    }

    function CalcularDepreciacion($total_activo, $porcentaje_depreciacion){
        $depreciacion = $total_activo * $porcentaje_depreciacion;
        return $depreciacion;
    }

    function CalcularDepreciacionAcumulada($meses_depreciacion, $monto_depreciacion){
        $depreciacion_acumulada = $meses_depreciacion * $monto_depreciacion;
        return $depreciacion_acumulada;
    }

    function CalcularPorcentajeDepreciacion($vida_util_activo){
        $porcentaje_depreciacion = (100 / $vida_util_activo)/100;
        return $porcentaje_depreciacion;
    }

    function GetFechaFinDepreciacion($fecha_activo, $vida_util){
        $fecha_fin_depreciacion = date('Y-m-d', strtotime($fecha_activo.' + '.$vida_util.' month'));
        return $fecha_fin_depreciacion;
    }

    function AsignarMontoDepreciarMeses($activo_depreciado){
        global $util, $anio, $mes;
		
		$cantidad_recorrido = 0;
		$meses = ['Enero' => 0, 'Febrero' => 0, 'Marzo' => 0, 'Abril' => 0, 'Mayo' => 0, 'Junio' => 0, 'Julio' => 0, 'Agosto' => 0, 'Septiembre' => 0, 'Octubre' => 0, 'Noviembre' => 0, 'Diciembre' => 0];

		$fecha_activo_separada = $util->SepararFecha($activo_depreciado['Fecha_Activo']);
        $fecha_fin_depreciacion_separada = $util->SepararFecha($activo_depreciado['Fecha_Fin_Depreciacion']);

        if (intval($fecha_activo_separada[0]) < intval($anio) && intval($fecha_fin_depreciacion_separada[0]) > intval($anio)) {//LA DEPRECIACION DEL ACTIVO OCUPA TODO EL AÑO CONSULTADO
            $cantidad_recorrido = count($meses);

            
        	for ($i=0; $i < $cantidad_recorrido; $i++) { 
	        	if (($i+1) <= $mes) {
	        		$meses[$util->ObtenerMesString($i+1)] = floatval($activo_depreciado['Monto_Depreciacion']);
	        	}
	        }
            

        }elseif (intval($fecha_activo_separada[0]) == intval($anio) && intval($fecha_fin_depreciacion_separada[0]) > intval($anio)) {//LA DEPRECIACION DEL ACTIVO COMIENZA EN EL AÑO CONSULTADO

            if (intval($fecha_activo_separada[1]) <= intval($mes)) {//LA DEPRECIACION DEL ACTIVO COMIENZA EN UN MES INFERIOR AL CONSULTADO

            	 $cantidad_recorrido = abs(intval($fecha_activo_separada[1]) - 12);
            
	            for ($i=intval($fecha_activo_separada[1]); $i <= 12; $i++) {
	        		$meses[$util->ObtenerMesString($i)] = floatval($activo_depreciado['Monto_Depreciacion']);
		        }

            }

        }elseif (intval($fecha_activo_separada[0]) == intval($anio) && $fecha_fin_depreciacion_separada[0] == intval($anio)) {//LA DEPRECIACION DEL ACTIVO COMIENZA Y TERMINA EN EL AÑO CONSULTADO
            
            if (intval($fecha_activo_separada[1]) < intval($mes) && $fecha_fin_depreciacion_separada[1] < intval($mes)) {//LA DEPRECIACION DEL ACTIVO COMIENZA Y TERMINA EN UN MES INFERIOR AL CONSULTADO
            	for ($i=intval($fecha_activo_separada[1]); $i <= intval($fecha_fin_depreciacion_separada[1]); $i++) {
	        		$meses[$util->ObtenerMesString($i)] = floatval($activo_depreciado['Monto_Depreciacion']);
		        }
            	
            }elseif (intval($fecha_activo_separada[1]) < intval($mes) && $fecha_fin_depreciacion_separada[1] > intval($mes)) {//LA DEPRECIACION DEL ACTIVO COMIENZA EN UN MES INFERIOR AL CONSULTADO PERO TERMINA MESES DESPUES DEL CONSULTADO
            	for ($i=intval($fecha_activo_separada[1]); $i <= intval($mes); $i++) {
	        		$meses[$util->ObtenerMesString($i)] = floatval($activo_depreciado['Monto_Depreciacion']);
		        }
            	
            }elseif(intval($fecha_activo_separada[1]) == intval($mes)){//LA DEPRECIACION DEL ACTIVO COMIENZA EN UN MES IGUAL AL CONSULTADO
				$meses[$util->ObtenerMesString($fecha_activo_separada[1])] = floatval($activo_depreciado['Monto_Depreciacion']);
            }

        }
        
        return $meses;
    }
          
?>