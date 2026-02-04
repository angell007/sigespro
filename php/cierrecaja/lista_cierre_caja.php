<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

	
	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
	$filtros = array("nombre_funcionario" => "", "nombre_punto" => "");
	$condiciones = '';

	$filtros = AsignarFiltros($_REQUEST, $filtros);
	$condiciones = ArmarCondiconalConsulta($filtros, $condiciones);

	try {
		
		$query = 'SELECT GROUP_CONCAT(PD.Id_Punto_Dispensacion) as Id_Punto
		FROM Funcionario_Punto FP
		INNER JOIN Punto_Dispensacion PD 
		ON FP.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
		WHERE FP.Identificacion_Funcionario='.$funcionario;

		$oCon= new consulta();
		//$oCon->setTipo('Multiple');
		$oCon->setQuery($query);
		$puntos = $oCon->getData();
		unset($oCon);

		$query='SELECT COUNT(*) AS Total
				FROM Diario_Cajas_Dispensacion DC
				INNER JOIN Funcionario F
				ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario
				INNER JOIN Punto_Dispensacion PD 
				On DC.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
				WHERE DC.Id_Punto_Dispensacion IN ('.$puntos["Id_Punto"].')'
				.$condiciones;

		$oCon= new consulta();
		$oCon->setQuery($query);
		$total = $oCon->getData();
		unset($oCon);

		####### PAGINACIÓN ######## 
		$tamPag = 20; 
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

		$resultado['numReg'] = $numReg;

		$query='SELECT DC.*, PD.Nombre as Punto, CONCAT_WS(" ",F.Nombres,F.Apellidos) AS Funcionario, DATE_FORMAT(DC.Fecha, "%Y-%m-%d") as Fecha, DATE_FORMAT(DC.Fecha_Inicio, "%Y-%m-%d") as Fecha_Inicio,DATE_FORMAT(DC.Fecha_Fin, "%Y-%m-%d") as Fecha_Fin,IFNULL((SELECT Soporte FROM Soporte_Consignacion WHERE Id_Soporte_Consignacion =DC.Id_Soporte_Consignacion ),"") as Soporte
				FROM Diario_Cajas_Dispensacion DC
				INNER JOIN Funcionario F
				ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario
				INNER JOIN Punto_Dispensacion PD 
				On DC.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
				WHERE DC.Id_Punto_Dispensacion IN ('.$puntos["Id_Punto"].')'
				.$condiciones
				." ORDER BY DC.Id_Diario_Cajas_Dispensacion DESC LIMIT ".$limit.",".$tamPag;

		$oCon= new consulta();
		$oCon->setTipo('Multiple');
		$oCon->setQuery($query);
		$resultado['cajas'] = $oCon->getData();
		unset($oCon);

		echo json_encode($resultado);

	} catch (Exception $e) {

		echo json_encode($e);
	}


	function AsignarFiltros($req, $filtros){

		if(isset($req['nombre_funcionario']) && $req['nombre_funcionario'] != ''){

			$filtros["nombre_funcionario"] = $req['nombre_funcionario'];
		}

		if(isset($req['nombre_punto']) && $req['nombre_punto'] != ''){

			$filtros["nombre_punto"] = $req['nombre_punto'];
		}

		return $filtros;
	}

	function ArmarCondiconalConsulta($filtros, $condiciones){

		if($filtros['nombre_funcionario'] != ''){
			$condiciones .= ' AND CONCAT_WS(" ", F.Nombres, F.Apellidos) LIKE "%'.$filtros['nombre_funcionario'].'%"';
		}

		if($filtros['nombre_punto'] != ''){
			$condiciones .= ' AND PD.Nombre LIKE "%'.$filtros['nombre_punto'].'%"';
		}

		return $condiciones;
	}
?>