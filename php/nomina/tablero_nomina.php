<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $mes_actual = date('m');
    $anio_actual = date('Y');
    $dia_actual = date('d');
    $fecha_actual = date("Y-m-d");
    $query = '';
    $meses_calcular = array();
    $meses_tabla = array();
    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    $totales_nomina_quincena = array();

    $numero_personas = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_salarios = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_seguridad_social = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_extras_recargos = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_vacaciones = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_incapacidades = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_ingresos_constitutivos = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_ingresos_no_constitutivos = array('anterior' => 0, 'actual' => 0, 'variacion' => 0, 'resultado' => '');
    $total_gastado = 0;
    $totales_por_grupo = array();
    $totales_por_dependencia = array();
    $total_actual = 0;

    $costos_nomina1 = array();
    $costos_gastos_personales = array();
    $costos_centros_de_costos = array();
    $costos_sedes = array();
    $costos_dependencias = array();
    $id_funcionarios = array();

    $result = array();

    $oCon= new consulta();

    ConsultarFuncionarios();

    CalculoTablaSemaforo($dia_actual, $mes_actual, $anio_actual);

    $total_gastado = floatval($total_salarios['actual']) + 
    				floatval($total_extras_recargos['actual']) + 
    				floatval($total_vacaciones['actual']) + 
    				floatval($total_incapacidades['actual']) + 
    				floatval($total_ingresos_constitutivos['actual']) + 
    				floatval($total_ingresos_no_constitutivos['actual']);

	CalcularQuincenasPasadas();

	$costos_nomina1 = CalcularCostosNomina();
	CalcularCostosPorGrupo();
	CalcularCostosPorDependencia();

    $result['tabla_semaforo'] = array('personas' => $numero_personas, 
    	'salarios' => $total_salarios, 
    	'seguridad' => $total_seguridad_social, 
    	'extras' => $total_extras_recargos, 
    	'vacaciones' => $total_vacaciones, 
    	'ingresos_constitutivos' => $total_ingresos_constitutivos, 
    	'ingresos_no_constitutivos' => $total_ingresos_no_constitutivos, 
    	'incapacidades' => $total_incapacidades, 
    	'otros_ingresos' => $total_vacaciones + $total_ingresos_constitutivos + $total_ingresos_no_constitutivos + $total_incapacidades );

    $result['totales_quincenas'] = $totales_nomina_quincena;

    $result['meses_tabla'] = $meses_tabla;
    $result['costos_nomina'] = $costos_nomina1;
    $result['costos_nomina_por_grupos'] = $totales_por_grupo;
    $result['costos_nomina_por_dependencias'] = $totales_por_dependencia;

    CalcularCostosNomina();
   //var_dump($result['tabla_semaforo']);
   //var_dump($totales_por_grupo);

    //var_dump($result);

    echo json_encode($result);
    

    //TODOS

    //Consulta el total de nomina hasta el dia actual
    //Consulta el total de seguridad social hasta el dia actual
    //Consulta el total de prima de servicios hasta el dia actual
    //Consulta el total funcionarios registrados
    //Consulta el total de los salarios hasta el dia actual
    //Consulta el total de extras y recargos hasta el dia actual
    //Consulta el total de vacaciones hasta el dia actual
    //Consulta el total de incapacidades hasta el dia actual
    //Consulta el total de ingresos constitutivos de salarios hasta el dia actual
    //Consulta el total de ingresos no constitutivos hasta el dia actual
    //Consulta costos y gastos de personal
    //Consulta costos por centros de costos
    //Consulta costos por sedes
    //Consulta costos por áreas(dependencias)

    //DATOS TABLA SEMÁFORO
    function CalculoTablaSemaforo($dia_actual, $mes_actual, $anio_actual){
        global $fecha_actual, $meses_tabla;

        $oItem = new complex("Configuracion","Id_Configuracion",1);
        $config = $oItem->getData();
        unset($oItem);

        $salario_minimo = $config["Salario_Base"];
        $auxilio_transporte = $config["Subsidio_Transporte"];
        $maximo_liquidacion = $config["Maximo_Cotizacion"];
        $salario_maximo = $salario_minimo * $maximo_liquidacion;

        $primera_fecha_quincena = '';
        $segunda_fecha_quincena = '';

        if ($dia_actual > 15) {

            $fechas = ArmarFecha($mes_actual, $anio_actual);
            $primera_fecha_quincena = $fechas['quincena1'];
            $segunda_fecha_quincena = $fechas['quincena2'];
        }else{

            $mes_anio_anterior = CalcularMes($mes_actual, 1, $anio_actual);
            $mes_anio_actual = CalcularMes($mes_actual, 0, $anio_actual);

            $fechas = ArmarFecha($mes_anio_anterior['mes'], $mes_anio_anterior['anio']);
            $fechas2 = ArmarFecha($mes_anio_actual['mes'], $mes_anio_actual['anio']);

            $primera_fecha_quincena = $fechas['quincena2'];
            $segunda_fecha_quincena = $fechas2['quincena1'];
        }

        $meses_tabla = GetMesFromFecha($primera_fecha_quincena['inicio'], $segunda_fecha_quincena['inicio']);

        NumeroPersonas($primera_fecha_quincena, $segunda_fecha_quincena);
        CalcularTotales($primera_fecha_quincena, $segunda_fecha_quincena);
        //TotalExtrasRecargos($primera_fecha_quincena, $segunda_fecha_quincena);
        CalculoVariaciones();
    }


    //Número de personas
    function NumeroPersonas($fechas_primera_quincena, $fechas_segunda_quincena){
        global $numero_personas, $query, $fecha_actual;

        $personas_mes_anterior;
        $personas_mes_actual;
        $variacion_personas = '';

        $query = 
            'SELECT 
                count(*) AS Total_Quincena_Anterior
            FROM Funcionario
            WHERE
                Fecha_Ingreso <= "'.$fechas_primera_quincena['fin'].'" AND Fecha_Retiro >= "'.$fechas_primera_quincena['fin'].'" AND Tipo="Propio"';

        $oCon = new Consulta();
        $oCon->setQuery($query);
        $personas_mes_anterior = $oCon->getData();
        $personas_mes_anterior = $personas_mes_anterior["Total_Quincena_Anterior"];
        unset($oCon);

        $query = 
            'SELECT 
                count(*) AS Total_Quincena_Actual
            FROM Funcionario
            WHERE
                Fecha_Ingreso <= "'.$fechas_segunda_quincena['fin'].'" AND Fecha_Retiro >= "'.$fechas_segunda_quincena['fin'].'" AND Tipo="Propio"';

        $oCon = new Consulta();
        $oCon->setQuery($query);
        $personas_mes_actual = $oCon->getData();
        $personas_mes_actual = $personas_mes_actual["Total_Quincena_Actual"];
        unset($oCon);

        $variacion = CalcularVariacion($personas_mes_anterior, $personas_mes_actual);

        $numero_personas = array('anterior' => $personas_mes_anterior, 'actual' => $personas_mes_actual, 'variacion' => $variacion['variacion'], 'resultado' => $variacion['resultado']);
    }

    //Calcula el total de salarios, ingresos, vacaciones e incapacidades de las ultimas 2 quincenas de cada funcionario
    function CalcularTotales($fechas_primera_quincena, $fechas_segunda_quincena){
        global $fecha_actual, $oCon, $total_salarios, $total_vacaciones, $total_incapacidades, $total_ingresos_constitutivos, $total_ingresos_no_constitutivos, $query, $id_funcionarios, $totales_por_grupo, $totales_por_dependencia, $total_extras_recargos, $total_actual;

        $lista_novedades_mes_anterior;
        $lista_novedades_mes_actual;

        $recargos_y_extras;
        $extras_recargos_mes_anterior = 0;
        $extras_recargos_mes_actual = 0;
        $variacion_extras_recargos = 0;

        $salarios_primera_quincena = 0;
        $salarios_segunda_quincena = 0;
        $variacion_salarios = '';

        $datos_funcionario_actual;
        $bonos_funcionario = array();

        $quincena1 = array();
        $quincena2 = array();

        if (count($id_funcionarios) > 0)  {
            
            foreach ($id_funcionarios as $key => $value) {
				
				$bono = GetBonosFuncionario($value);
                array_push($bonos_funcionario, $bono);

            	//CALCULOS DE LA PRIMERA QUINCENA
            	//se consultan los datos del funcionario para conocer los salarios e ingresos y extras de cada uno
                $datos_funcionario_actual = ConsultarDatosCompletosFuncionarios($value, $fechas_primera_quincena['inicio']);

                //CALCULO DE LOS EXTRAS
                $recargos_y_extras = CalcularExtrasFuncionario2($value, $fechas_primera_quincena, $fechas_segunda_quincena, $datos_funcionario_actual['Salario']);
                $extras_recargos_mes_anterior += floatval($recargos_y_extras[0]);
                $extras_recargos_mes_actual += floatval($recargos_y_extras[1]);

                //CALCULO DE OTROS VALORES
                $total_ingresos_constitutivos['anterior'] += floatval($datos_funcionario_actual['Ingresos_S']);
                $total_ingresos_no_constitutivos['anterior'] += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
                $lista_novedades_mes_anterior = ConsultarNovedadesFuncionario($value, $fechas_primera_quincena['inicio'], $fechas_primera_quincena['fin']);
                $salario1 = CalcularSalarioQuincena($lista_novedades_mes_anterior, $fechas_primera_quincena['inicio'], $fechas_primera_quincena['fin'], (float)$datos_funcionario_actual['Salario'], (INT)$datos_funcionario_actual['Ingresos_S']);

                array_push($quincena1, $salario1['salario']);
                $salarios_primera_quincena += $salario1['salario']; 

                $total_vacaciones['anterior'] += $salario1['vacaciones'];
                $total_incapacidades['anterior'] += $salario1['incapacidades'];


                //CALCULOS DE LA SEGUNDA QUINCENA
                $datos_funcionario_actual = ConsultarDatosCompletosFuncionarios($value, $fechas_segunda_quincena['inicio']);

                $total_ingresos_constitutivos['actual'] += $datos_funcionario_actual['Ingresos_S'];  
                $total_ingresos_no_constitutivos['actual'] += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
                $lista_novedades_mes_actual = ConsultarNovedadesFuncionario($value, $fechas_segunda_quincena['inicio'], $fechas_segunda_quincena['fin']);
                $salario2 = CalcularSalarioQuincena($lista_novedades_mes_actual, $fechas_segunda_quincena['inicio'], $fechas_segunda_quincena['fin'], (float)$datos_funcionario_actual['Salario'], (INT)$datos_funcionario_actual['Ingresos_S']);

                array_push($quincena2, $salario2['salario']);

                $salarios_segunda_quincena += $salario2['salario'];
                $total_vacaciones['actual'] += $salario2['vacaciones'];
                $total_incapacidades['actual'] += $salario2['incapacidades'];

                //ASIGNACION POR GRUPO
                $nombre_grupo = QueryNombreGrupo($datos_funcionario_actual['Id_Grupo']);
            	/*$totales_por_grupo[$nombre_grupo]['ing_c'] += floatval($datos_funcionario_actual['Ingresos_S']);
            	$totales_por_grupo[$nombre_grupo]['ing_nc'] += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
            	$totales_por_grupo[$nombre_grupo]['salarios'] += $salario2['salario'];
            	$totales_por_grupo[$nombre_grupo]['vac'] += $salario2['vacaciones'];
            	$totales_por_grupo[$nombre_grupo]['inc'] += $salario2['incapacidades'];
            	$totales_por_grupo[$nombre_grupo]['ext'] += floatval($recargos_y_extras[1]);*/

            	$totales_por_grupo[$nombre_grupo]['Total'] += floatval($datos_funcionario_actual['Ingresos_S']);
            	$totales_por_grupo[$nombre_grupo]['Total'] += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
            	$totales_por_grupo[$nombre_grupo]['Total'] += $salario2['salario'];
            	$totales_por_grupo[$nombre_grupo]['Total'] += $salario2['vacaciones'];
            	$totales_por_grupo[$nombre_grupo]['Total'] += $salario2['incapacidades'];
            	$totales_por_grupo[$nombre_grupo]['Total'] += floatval($recargos_y_extras[1]);

            	$nombre_dependencia = QueryNombreDependencia($datos_funcionario_actual['Id_Dependencia']);

            	$totales_por_dependencia[$nombre_dependencia]['Total'] += floatval($datos_funcionario_actual['Ingresos_S']);
            	$totales_por_dependencia[$nombre_dependencia]['Total'] += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
            	$totales_por_dependencia[$nombre_dependencia]['Total'] += $salario2['salario'];
            	$totales_por_dependencia[$nombre_dependencia]['Total'] += $salario2['vacaciones'];
            	$totales_por_dependencia[$nombre_dependencia]['Total'] += $salario2['incapacidades'];
            	$totales_por_dependencia[$nombre_dependencia]['Total'] += floatval($recargos_y_extras[1]);

            	$total_actual += floatval($datos_funcionario_actual['Ingresos_S']);
            	$total_actual += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
            	$total_actual += $salario2['salario'];
            	$total_actual += $salario2['vacaciones'];
            	$total_actual += $salario2['incapacidades'];
            	$total_actual += floatval($recargos_y_extras[1]);
            }
        }

        $variacion_extras_recargos = CalcularVariacion($extras_recargos_mes_anterior, $extras_recargos_mes_actual);
        $total_extras_recargos = array('anterior' => round($extras_recargos_mes_anterior, 2), 'actual' => round($extras_recargos_mes_actual, 2), 'variacion' => $variacion_extras_recargos['variacion'], 'resultado' => $variacion_extras_recargos['resultado']);

        $variacion = CalcularVariacion($salarios_primera_quincena, $salarios_segunda_quincena);

        $total_salarios = array('anterior' => $salarios_primera_quincena, 'actual' => $salarios_segunda_quincena, 'variacion' => $variacion['variacion'], 'resultado' => $variacion['resultado']);

        /*var_dump($totales_por_grupo);
        //var_dump("total salarios grupos =".($totales_por_grupo['Administracion']['salarios'] + $totales_por_grupo['Dispensacion']['salarios'] + $totales_por_grupo['Sin_Grupo']['salarios']));
        //var_dump("total ingresos const grupos =".($totales_por_grupo['Administracion']['ing_c'] + $totales_por_grupo['Dispensacion']['ing_c'] + $totales_por_grupo['Sin_Grupo']['ing_c']));
        var_dump("total de grupos = ".array_sum($totales_por_grupo));
        var_dump("totales sumados =".($total_salarios['actual'] + $total_ingresos_constitutivos['actual'] + $total_ingresos_no_constitutivos['actual'] + $total_vacaciones['actual'] + $total_incapacidades['actual'] + $total_extras_recargos['actual']));*/

        /*var_dump($quincena1);
        var_dump($quincena2);

        var_dump("total quincena 1: ".array_sum($quincena1));
        var_dump("total quincena 2: ".array_sum($quincena2));*/
    }

    function QueryNombreGrupo($id_grupo){
    	if ($id_grupo == '' || is_null($id_grupo)) {
    		return 'Sin Grupo';
    	}

    	$query_grupo = 
    		'SELECT
    			Nombre
			FROM
				Grupo
			WHERE
				Id_Grupo = '.$id_grupo;

		$oCon= new consulta();
        $oCon->setQuery($query_grupo);
        $grupo = $oCon->getData();
        unset($oCon);

        if (is_array($grupo)) {
        	return $grupo['Nombre'];
        }else{
        	return 'Sin Grupo';
        }
    }

    function QueryNombreDependencia($id_dependencia){
    	if ($id_dependencia == '' || is_null($id_dependencia)) {
    		return 'Sin Dependencia';
    	}

    	$query_depenedencia = 
    		'SELECT
    			Nombre
			FROM
				Dependencia
			WHERE
				Id_Dependencia = '.$id_dependencia;

		$oCon= new consulta();
        $oCon->setQuery($query_depenedencia);
        $dependencia = $oCon->getData();
        unset($oCon);

        if (is_array($dependencia)) {
        	return $dependencia['Nombre'];
        }else{
        	return 'Sin Dependencia';
        }
    }

    //Calcula el total de extras de las ultimas 2 quincenas de cada funcionario
    function TotalExtrasRecargos($fechas_primera_quincena, $fechas_segunda_quincena){
        global $fecha_actual, $oCon, $total_extras_recargos, $query, $id_funcionarios;

        $recargos_y_extras;
        $extras_recargos_mes_anterior = 0;
        $extras_recargos_mes_actual = 0;
        $variacion_extras_recargos = '';

        if (count($id_funcionarios) > 0)  {
            
            foreach ($id_funcionarios as $key => $value) {
                
                $recargos_y_extras = CalcularExtrasFuncionario($value, $fechas_primera_quincena, $fechas_segunda_quincena);

                $extras_recargos_mes_anterior += floatval($recargos_y_extras[0]);
                $extras_recargos_mes_actual += floatval($recargos_y_extras[1]);
            }
        }

        $variacion = CalcularVariacion($extras_recargos_mes_anterior, $extras_recargos_mes_actual);

        $datos_recargos = array('anterior' => round($extras_recargos_mes_anterior, 2), 'actual' => round($extras_recargos_mes_actual, 2), 'variacion' => $variacion['variacion'], 'resultado' => $variacion['resultado']);

        $total_extras_recargos = $datos_recargos;

    }

    //Calcula las fechas de inicio y fin de cada quincena segun los parametros
    function CalcularFechas($mes_actual, $anio_actual){
        global $meses_calcular;

        $anio_restar = 1;
        $meses_restar = 2;
        $mes_maximo = 12;

        for ($i=0; $i <= $meses_restar; $i++) {

            $ciclo = 0;
            $mes = ($meses_restar - $i) - $mes_actual;
            $anio = $anio_actual;

            if ($mes <= 0) {
                $anio = $anio_actual - $anio_restar;
                $mes = $mes + $mes_maximo;          
            }else{
                $anio = $anio_actual;
                $mes = $mes; 
            }

            while ($ciclo < 1) {

                $meses_calcular[MesString($mes)] = ArmarFecha(($ciclo + 1), $mes, $anio);

                $ciclo++;
            }
        }
    }

    function ArmarFecha($mes, $anio, $ColocarCeroAlMes = false){
        $fechas = array();

        if ($ColocarCeroAlMes) {
            
            $mes = MesDosDigitos($mes);
        }else{
            $mes = $mes;
        }

        $fechas['quincena1'] = array('inicio' => $anio."-".$mes."-01", 'fin' => $anio."-".$mes."-15");
        $fechas['quincena2'] = array('inicio' => $anio."-".$mes."-16", 'fin' => $anio."-".$mes."-". date("d",(mktime(0,0,0,date($mes)+1,1,date($anio))-1)));

        return $fechas;
    }

    function GetMesFromFecha($fecha1, $fecha2){
    	$m1 = ConocerQuincena($fecha1);
    	$m2 = ConocerQuincena($fecha2);

    	$mes1 = MesString($m1['mes']);
    	$mes2 = MesString($m2['mes']);

    	return array('mes1' => $mes1, 'mes2' => $mes2);
    }

    function CalcularMes($mes_actual, $restar_meses, $anio){

        $mes = $mes_actual - $restar_meses;
        $anio = $anio;

        if ($mes <= 0) {
            $mes = $mes + 12;
            $anio = $anio - 1;      
        }else{
            $mes = $mes;
        }

        return array('anio' => $anio, 'mes' => MesDosDigitos($mes));
    }

    function FechasMes($anio, $mes){
        $fechas = array();

        $fechas['fecha_ini'] = $anio."-".$mes."-01";
        $fechas['fecha_fin'] = $anio."-".$mes."-". date("d",(mktime(0,0,0,date($mes)+1,1,date($anio))-1));

        return $fechas;
    }

    function MesString($mes_index){
        global $meses;

        return  $meses[($mes_index-1)];
    }

    function MesDosDigitos($mes){
        if ($mes < 10) {
            return "0".$mes;
        }

        return $mes;
    }

    function CalcularVariacion($valor_anterior, $valor_actual){
        $variacion = 0;

        if ($valor_anterior == 0 && $valor_actual == 0 ) {
        	//return $variacion;
        	return array('variacion' => round($variacion, 2), 'resultado' => 'positivo');
        }else if($valor_actual == 0){
        	$variacion = 100;
        	return array('variacion' => round($variacion, 2), 'resultado' => 'negativo');
        	//return $variacion;
        }else{
        	$variacion = ((floatval($valor_actual) - floatval($valor_anterior))/floatval($valor_actual))*100;
        	//return round($variacion, 3);
        	if ($variacion >= 0) {
	        	return array('variacion' => round($variacion, 2), 'resultado' => 'positivo');
	        }else if ($variacion < 0) {
	        	return array('variacion' => round($variacion, 2), 'resultado' => 'negativo');
	        }
        }
        
        return array('variacion' => round($variacion, 2), 'resultado' => 'negativo');
    }

    function ConsultarFuncionarios(){
        global $query, $id_funcionarios, $oCon, $fecha_actual;

        $query = 
            'SELECT 
                Identificacion_Funcionario
            FROM Funcionario
            WHERE
                Fecha_Ingreso <= "'.$fecha_actual.'" AND Fecha_Retiro >= "'.$fecha_actual.'" AND Tipo="Propio"';

        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $result = $oCon->getData();

        if (count($result) > 0) {
            
            foreach ($result as $key => $value) {
                array_push($id_funcionarios, $value['Identificacion_Funcionario']);
            }
        }

        //var_dump($id_funcionarios);
    }

    function StringIdFuncionarios(){
        global $id_funcionarios;

        $cadena_funcionarios = '';
        $count = count($id_funcionarios) - 1;
        $ciclo = 0;

        foreach ($id_funcionarios as $key => $value) {

            if ($ciclo == $count) {
                $cadena_funcionarios .= $value;
                exit;  
            }

            $cadena_funcionarios .= $value.", ";
            $ciclo++;
        }

        return $cadena_funcionarios;
    }

    //Acumula el total de extras de las fechas quincenales pasadas como parametros
    function CalcularExtrasFuncionario($id_funcionario, $fechas_quincena_mes_anterior, $fechas_quincena_mes_actual){

        $total_mes_anterior_funcionario = 0;
        $total_mes_actual_funcionario = 0;

        $salario_funcionario = ConsultarDatosFuncionarios($id_funcionario);

        $ciclo = 2;

        for ($i=0; $i < $ciclo ; $i++) { 
           
           if ($i == 0) {
                $total_mes_anterior_funcionario = ExtrasFuncionario($id_funcionario, $fechas_quincena_mes_anterior['inicio'], $fechas_quincena_mes_anterior['fin'], $total_mes_anterior_funcionario, $salario_funcionario['Salario']);
           }else if($i == 1){
                $total_mes_actual_funcionario = ExtrasFuncionario($id_funcionario, $fechas_quincena_mes_actual['inicio'], $fechas_quincena_mes_actual['fin'], $total_mes_actual_funcionario, $salario_funcionario['Salario']);
           }
        }

        return array(0 => $total_mes_anterior_funcionario, 1 => $total_mes_actual_funcionario);
    }

    function CalcularExtrasFuncionario2($id_funcionario, $fechas_quincena_mes_anterior, $fechas_quincena_mes_actual, $salario){

        $total_mes_anterior_funcionario = 0;
        $total_mes_actual_funcionario = 0;

        if (is_null($salario)) {
        	return array(0 => $total_mes_anterior_funcionario, 1 => $total_mes_actual_funcionario);
        }

        $ciclo = 2;

        for ($i=0; $i < $ciclo ; $i++) { 
           
           if ($i == 0) {
                $total_mes_anterior_funcionario = ExtrasFuncionario($id_funcionario, $fechas_quincena_mes_anterior['inicio'], $fechas_quincena_mes_anterior['fin'], $total_mes_anterior_funcionario, $salario);
           }else if($i == 1){
                $total_mes_actual_funcionario = ExtrasFuncionario($id_funcionario, $fechas_quincena_mes_actual['inicio'], $fechas_quincena_mes_actual['fin'], $total_mes_actual_funcionario, $salario);
           }
        }

        /*if ($total_mes_actual_funcionario > 0) {
        	var_dump($id_funcionario);
        	var_dump($fechas_quincena_mes_actual);
        	var_dump($total_mes_actual_funcionario);
        }*/

        return array(0 => $total_mes_anterior_funcionario, 1 => $total_mes_actual_funcionario);
    }

    function CalcularExtraFuncionario($id_funcionario, $fechas_quincena, $salario){

        $total_extra_quincena = 0;

        if (is_null($salario)) {
        	return $total_extra_quincena;
        }
        
        $total_extra_quincena = ExtrasFuncionario($id_funcionario, $fechas_quincena['inicio'], $fechas_quincena['fin'], $total_extra_quincena, $salario);

        /*if ($total_mes_actual_funcionario > 0) {
        	var_dump($id_funcionario);
        	var_dump($fechas_quincena_mes_actual);
        	var_dump($total_mes_actual_funcionario);
        }*/

        return $total_extra_quincena;
    }

    //Consulta la lista de extras de un funcionario y en base al resultado acumula el valor del total de extras
    function ExtrasFuncionario($id_funcionario, $fechaIni, $fechaFin, $acumladorDeTotal, $salario_funcionario){

        $query_quincena = 
            'SELECT 
                TN.*,
                IFNULL((SELECT SUM(Tiempo) 
                            FROM Novedad 
                            WHERE Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
                            AND Identificacion_Funcionario='.$id_funcionario
                            .' AND CAST(Fecha_Inicio AS DATE) >= "'.$fechaIni.'" 
                            AND CAST(Fecha_Fin AS DATE) <= "'.$fechaFin.'"),0) as Tiempo
        FROM Tipo_Novedad TN
        WHERE TN.Tipo_Novedad="Hora_Extra" OR TN.Tipo_Novedad="Recargo"';

        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query_quincena);
        $lista_extras = $oCon->getData();
        unset($oCon);


           //var_dump($query_quincena);

        $total_extras = 0;
        foreach($lista_extras as $extra){
           $val_extra = (intval($salario_funcionario)*$extra["Valor"]*$extra["Tiempo"])/(30*8);
           $total_extras+=$val_extra;

           /*echo $salario_funcionario."-".$extra['Valor']."-".$extra['Tiempo']."- ";
           echo $val_extra."\n";*/
        }

        

        $acumladorDeTotal += floatval($total_extras);

        return $acumladorDeTotal;
    }

    //Consulta el salario de un funcionario
    function ConsultarDatosFuncionarios($id_funcionario){

        $q = 'SELECT 
            F.Salario
            FROM  Funcionario F
            WHERE F.Identificacion_Funcionario = '.$id_funcionario ;

        $oCon= new consulta();
        $oCon->setQuery($q);
        $funcionario = $oCon->getData();
        unset($oCon);

        return $funcionario;
    }

    //Consulta todos los datos de un funcionario
    function ConsultarDatosCompletosFuncionarios($id_funcionario, $fechaIni){

        $quincena = ConocerQuincena($fechaIni);

        $query = 
            'SELECT 
                F.*, 
                (Select SUM(Valor) 
                FROM Movimiento_Funcionario ME 
                INNER JOIN Tipo_Ingreso TI ON ME.Id_Tipo=TI.Id_Tipo_Ingreso 
                WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Ingreso" AND TI.Tipo="Prestacional" AND ME.Quincena="'.$quincena["anio"]."-".$quincena["mes"].";".$quincena["quincena"].'") as Ingresos_S,
                (Select SUM(Valor) 
                FROM Movimiento_Funcionario ME 
                INNER JOIN Tipo_Ingreso TI ON ME.Id_Tipo=TI.Id_Tipo_Ingreso 
                WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Ingreso" AND TI.Tipo="No_Prestacional" AND ME.Quincena="'.$quincena["anio"]."-".$quincena["mes"].";".$quincena["quincena"].'") as Ingresos_N,
                (Select SUM(Valor) 
                FROM Movimiento_Funcionario ME 
                WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Egreso" AND ME.Quincena="'.$quincena["anio"]."-".$quincena["mes"].";".$quincena["quincena"].'") as Egresos,
            C.Nombre as Cargo
            FROM  Funcionario F
            INNER JOIN Cargo C
            ON F.Id_Cargo = C.Id_Cargo
            WHERE F.Identificacion_Funcionario = '.$id_funcionario
            .' AND F.Tipo = "Propio"';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $funcionario = $oCon->getData();
            unset($oCon);

        return $funcionario;
    }

    //Consulta la lista de novedades de un funcionario
    function ConsultarNovedadesFuncionario($id_funcionario, $fechaIni, $fechaFin){

        $query_novedades_funcionario = 
            'SELECT 
                TN.*, 
                N.*
            FROM Tipo_Novedad TN
            INNER JOIN Novedad N On TN.Id_Tipo_Novedad = N.Id_Tipo_Novedad AND TN.Tipo_Novedad!="Hora_Extra" AND TN.Tipo_Novedad!="Recargo" AND N.Identificacion_Funcionario='.$id_funcionario.' 
            AND ((N.Fecha_Inicio>="'.$fechaIni.'" AND N.Fecha_Inicio<="'.$fechaFin.'") OR (N.Fecha_Fin>="'.$fechaIni.'" AND N.Fecha_Fin<="'.$fechaFin.'") OR (N.Fecha_Inicio<="'.$fechaIni.'" AND N.Fecha_Fin>="'.$fechaFin.'"))
            ';     

        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query_novedades_funcionario);
        $lista_novedades = $oCon->getData();
        unset($oCon);

        return $lista_novedades;
    }

    //Consulta los totales de salario quincenal, vacaciones, incapacidades y licencias de un funcionario
    function CalcularSalarioQuincena($lista_novedades, $fechaIni, $fechaFin, $salario, $ingreso_s){

    	$y=-1;
		$dias_ausente = 0;

		$lista_vacaciones=[];
		$lista_incapacidades=[];
		$lista_licencias=[];
		$total_vacaciones = 0;
		$total_incapacidades=0;
		$total_licencias=0;

        foreach($lista_novedades as $nov){ 
        	$y++;

            if($nov["Fecha_Inicio"]<=$fechaIni){
                $ini_nov = $fechaIni;
            }else{
                $ini_nov=$nov["Fecha_Inicio"];
            }
            if($nov["Fecha_Fin"]>=$fechaFin){
                $fin_nov = $fechaFin;
            }else{
                $fin_nov=$nov["Fecha_Fin"];
            }
            $dias_nov = round((strtotime($fin_nov) - strtotime($ini_nov))/ 86400);
            $lista_novedades[$y]["Dias"]=$dias_nov;
            $dias_ausente += $dias_nov;
            
            if($nov["Tipo_Novedad"]=="Vacaciones"){
                $lista_vacaciones[]=$lista_novedades[$y];
                $total_vacaciones+=($salario*$lista_novedades[$y]["Dias"])/30;
            }
            if($nov["Tipo_Novedad"]=="Incapacidad"){
                $lista_incapacidades[]=$lista_novedades[$y];
                $total_incapacidades+=($salario*$lista_novedades[$y]["Dias"])/30;
            }
            if($nov["Tipo_Novedad"]=="Licencia"){
                $lista_licencias[]=$lista_novedades[$y];
                if($nov["Id_Tipo_Novedad"]<7){
                    $total_licencias+=($salario*$lista_novedades[$y]["Dias"])/30;
                }                
            }            
        }

        $dias=15;
        $dias_laborados = $dias - $dias_ausente;

        $aux_trans = 0;
        $sueldo_dia = $salario/30;
        $salario_quincena= $sueldo_dia*$dias_laborados;


        $total_ibc = $salario_quincena+$total_extras+$total_vacaciones+$total_incapacidades+$total_licencias+$ingreso_s;

        /*if($salario != 0){
        	var_dump($dias_laborados);
	        var_dump($sueldo_dia);
	        var_dump($salario_quincena);
	        var_dump($total_ibc);
	        exit;
        }*/

        $resultado = array('salario' => round($salario_quincena, 2), 'constitutivos' => round($total_ibc, 2), 'vacaciones' => round($total_vacaciones, 2), 'incapacidades' => round($total_incapacidades, 2));

        return $resultado;
    }

    function GetBonosFuncionario($idFuncionario){

    	$quincena = ConocerQuincena($fecha1);
    	$quincena2 = ConocerQuincena($fecha2);

    	$query_movimientos_funcionario = 
    		'SELECT 
    			SUM(Bonos) AS TotalBonos
			FROM
				Funcionario
			WHERE
				Identificacion_Funcionario = '.$idFuncionario;

		$oCon= new consulta();
        $oCon->setQuery($query_movimientos_funcionario);
        $bonos = $oCon->getData();
        $bonos = $bonos['TotalBonos'];
        unset($oCon);

		return $bonos;   
    }

    function ConocerQuincena($fecha){

        $splittedDate = explode("-", $fecha);

        if (intval($splittedDate[2]) > 15) {
            return array('anio' => $splittedDate[0], 'mes' => $splittedDate[1], 'quincena' => 2);
        }else{
            return array('anio' => $splittedDate[0], 'mes' => $splittedDate[1], 'quincena' => 1);
        }
    }

    function CalculoVariaciones(){
    	global $total_vacaciones, $total_incapacidades, $total_ingresos_constitutivos, $total_ingresos_no_constitutivos;

    	$v1 = CalcularVariacion($total_ingresos_constitutivos['anterior'], $total_ingresos_constitutivos['actual']);
    	$v2 = CalcularVariacion($total_ingresos_no_constitutivos['anterior'], $total_ingresos_no_constitutivos['actual']);
    	$v3 = CalcularVariacion($total_vacaciones['anterior'], $total_vacaciones['actual']);
    	$v4 = CalcularVariacion($total_incapacidades['anterior'], $total_incapacidades['actual']);

    	$total_ingresos_constitutivos['variacion'] = $v1['variacion'];
    	$total_ingresos_constitutivos['resultado'] = $v1['resultado'];

    	$total_ingresos_no_constitutivos['variacion'] = $v2['variacion'];
    	$total_ingresos_no_constitutivos['resultado'] = $v2['resultado'];

    	$total_vacaciones['variacion'] = $v3['variacion'];
    	$total_vacaciones['resultado'] = $v3['resultado'];

    	$total_incapacidades['variacion'] = $v4['variacion'];
    	$total_incapacidades['resultado'] = $v4['resultado'];
    }

    function CalcularCostosNomina(){
    	global $total_gastado, $total_salarios, $total_extras_recargos, $total_ingresos_constitutivos, $total_ingresos_no_constitutivos, $total_vacaciones, $total_incapacidades;

    	$porc_salarios = PorcentajePorReglaDeTres(floatval($total_gastado),floatval($total_salarios['actual']));
    	$porc_extras = PorcentajePorReglaDeTres(floatval($total_gastado),floatval($total_extras_recargos['actual']));
    	$porc_constitutivos = PorcentajePorReglaDeTres(floatval($total_gastado),floatval($total_ingresos_constitutivos['actual']));
    	$porc_no_constitutivos = PorcentajePorReglaDeTres(floatval($total_gastado),floatval($total_ingresos_no_constitutivos['actual']));
    	$porc_vacaciones = PorcentajePorReglaDeTres(floatval($total_gastado),floatval($total_vacaciones['actual']));
    	$porc_incapacidades = PorcentajePorReglaDeTres(floatval($total_gastado),floatval($total_incapacidades['actual']));

    	return array('porc_salarios' => $porc_salarios, 'porc_extras' => $porc_extras, 'otros' => $porc_constitutivos + $porc_no_constitutivos + $porc_vacaciones + $porc_incapacidades);
    }

    function PorcentajePorReglaDeTres($total, $calcular){
    	$result = 0;

    	if ($calcular == 0) {
    		$result = 0;
    	}else{
    		$result = (($calcular * 100)/$total);
    	}
    	
    	return number_format($result, 0, "", "");
    }

    function CalcularCostosPorGrupo(){
    	global $totales_por_grupo, $total_actual;

    	$total = 0;

    	foreach ($totales_por_grupo as $key => $value) {
    		$porcentaje = PorcentajePorReglaDeTres($total_actual, $value['Total']);
    		$totales_por_grupo[$key]['Porcentaje'] = $porcentaje;
    	}
    }

    function CalcularCostosPorDependencia(){
    	global $totales_por_dependencia, $total_actual;

    	$total = 0;

    	foreach ($totales_por_dependencia as $key => $value) {
    		$porcentaje = PorcentajePorReglaDeTres($total_actual, $value['Total']);
    		$totales_por_dependencia[$key]['Porcentaje'] = $porcentaje;
    	}
    }

    function CalcularQuincenasPasadas(){
    	global $fecha_actual;

    	$separacion_fecha = GetFechaSeparada($fecha_actual);

    	$fechas_quincenas = FechasConsultaNomina($separacion_fecha);

    	if(count($fechas_quincenas) > 0){

    		$i = 1;
    		foreach ($fechas_quincenas as $valor) {
    	
    			CalcularTotalesNominaQuincenas($valor, $i);
    			$i++;
    		}
    	}
    }

    function GetFechaSeparada($fecha){

        $splittedDate = explode("-", $fecha);

        if (intval($splittedDate[2]) > 15) {
            return array('anio' => $splittedDate[0], 'mes' => $splittedDate[1], 'dia' => $splittedDate[2], 'quincena' => 2);
        }else{
            return array('anio' => $splittedDate[0], 'mes' => $splittedDate[1], 'dia' => $splittedDate[2], 'quincena' => 1);
        }
    }

    function FechasConsultaNomina($fecha){
    	$quincenas = array();

    	if ($fecha['quincena'] = 1) {
    		$primera_quincena = CalcularFechaQuincenaUno($fecha);
    		$fechas_mes_anterior1 = CalcularFechaMes($fecha['anio'], $fecha['mes'], 2);
    		$fechas_mes_anterior2 = CalcularFechaMes($fecha['anio'], $fecha['mes'], 1);
    		$ultima_quincena = CalcularFechaQuincenaFinal($fecha);

    		$quincenas['quincena_1'] = $primera_quincena;
    		$quincenas['quincena_2'] = $fechas_mes_anterior1['quincena1'];
    		$quincenas['quincena_3'] = $fechas_mes_anterior1['quincena2'];
    		$quincenas['quincena_4'] = $fechas_mes_anterior2['quincena1'];
    		$quincenas['quincena_5'] = $fechas_mes_anterior2['quincena2'];
    		$quincenas['quincena_6'] = $ultima_quincena;

    		return  $quincenas;

    	}else if($fecha['quincena'] = 2){
    		$fechas_mes_anterior1 = CalcularFechaMes($fecha['anio'], $fecha['mes'], 2);
    		$fechas_mes_anterior2 = CalcularFechaMes($fecha['anio'], $fecha['mes'], 1);
    		$fechas_mes_actual['quincena1']['inicio'] = $fecha['anio']."-".$fecha['mes']."-01";
    		$fechas_mes_actual['quincena1']['fin'] = $fecha['anio']."-".$fecha['mes']."-15";
    		$fechas_mes_actual['quincena2']['inicio'] = $fecha['anio']."-".$fecha['mes']."-16";
    		$fechas_mes_actual['quincena2']['fin'] = $fecha['anio']."-".$fecha['mes']."-".$fecha['dia'];

    		$quincenas['quincena1'] = $fechas_mes_anterior1['quincena1'];
    		$quincenas['quincena2'] = $fechas_mes_anterior1['quincena2'];
    		$quincenas['quincena3'] = $fechas_mes_anterior2['quincena1'];
    		$quincenas['quincena4'] = $fechas_mes_anterior2['quincena2'];
    		$quincenas['quincena5'] = $fechas_mes_actual['quincena1'];
    		$quincenas['quincena6'] = $fechas_mes_actual['quincena2'];

    		return  $quincenas;
    	}
    }

    function CalcularFechaQuincenaUno($fecha){
    	$mes_anio = CalcularMes($fecha['mes'], 3, $fecha['anio']);
    	$fechas = array();

    	$fechas['inicio'] = $mes_anio['anio']."-".$mes_anio['mes']."-16";
    	$fechas['fin'] = $mes_anio['anio']."-".$mes_anio['mes']."-". date("d",(mktime(0,0,0,date($mes_anio['mes'])+1,1,date($mes_anio['anio']))-1));

    	return $fechas;
    }

    function CalcularFechaQuincenaFinal($fecha){
    	$fechas = array();

    	$fechas['inicio'] = $fecha['anio']."-".$fecha['mes']."-01";
    	$fechas['fin'] = $fecha['anio']."-".$fecha['mes']."-".$fecha['dia'];

    	return $fechas;
    }

    function CalcularFechaMes($anio, $mes, $restar_mes){
    	$mes = $mes - $restar_mes;
        $anio = $anio;

        if ($mes <= 0) {
            $mes = $mes + 12;
            $anio = $anio - 1;      
        }else{
            $mes = $mes;
        }

        return ArmarFecha($mes, $anio);
    }

    function CalcularTotalesNominaQuincenas($fechas_quincena, $quincena){
        global $id_funcionarios, $totales_nomina_quincena;

        $total_salarios = 0;
        $extras_recargos = 0;
        $total_vacaciones = 0;
        $total_incapacidades = 0;
        $total_ingresos_constitutivos = 0;
        $total_ingresos_no_constitutivos = 0;

        if (count($id_funcionarios) > 0)  {
            
            foreach ($id_funcionarios as $key => $value) {
				
				$bono = GetBonosFuncionario($value);
                //array_push($bonos_funcionario, $bono);

            	//CALCULOS DE LA PRIMERA QUINCENA
            	//se consultan los datos del funcionario para conocer los salarios e ingresos y extras de cada uno
                $datos_funcionario_actual = ConsultarDatosCompletosFuncionarios($value, $fechas_quincena['inicio']);

                //CALCULO DE LOS EXTRAS
                $recargos_y_extras = CalcularExtraFuncionario($value, $fechas_quincena, $datos_funcionario_actual['Salario']);
                $extras_recargos += floatval($recargos_y_extras);

                //CALCULO DE OTROS VALORES
                $total_ingresos_constitutivos += floatval($datos_funcionario_actual['Ingresos_S']);
                $total_ingresos_no_constitutivos += floatval($datos_funcionario_actual['Ingresos_N']) + floatval($bono);
                $lista_novedades_mes_anterior = ConsultarNovedadesFuncionario($value, $fechas_quincena['inicio'], $fechas_quincena['fin']);
                $salario1 = CalcularSalarioQuincena($lista_novedades_mes_anterior, $fechas_quincena['inicio'], $fechas_quincena['fin'], (float)$datos_funcionario_actual['Salario'], (INT)$datos_funcionario_actual['Ingresos_S']);

                //array_push($quincena1, $salario1['salario']);
                $total_salarios += $salario1['salario']; 

                $total_vacaciones += $salario1['vacaciones'];
                $total_incapacidades += $salario1['incapacidades'];
            }
        }

        $totales_nomina_quincena['quincena'.$quincena]['fechas'] = $fechas_quincena;
        $totales_nomina_quincena['quincena'.$quincena]['salarios'] = $total_salarios;
        $totales_nomina_quincena['quincena'.$quincena]['extras'] = $extras_recargos;
        $totales_nomina_quincena['quincena'.$quincena]['vacaciones'] = $total_vacaciones;
        $totales_nomina_quincena['quincena'.$quincena]['incapacidades'] = $total_incapacidades;
        $totales_nomina_quincena['quincena'.$quincena]['ingresos_constitutivos'] = $total_ingresos_constitutivos;
        $totales_nomina_quincena['quincena'.$quincena]['ingresos_no_constitutivos'] = $total_ingresos_no_constitutivos;
    }
?>