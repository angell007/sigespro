<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$condicion = '';

	if (isset($_REQUEST['nombre_cargo']) && $_REQUEST['nombre_cargo'] != "") {
	  $condicion .= " WHERE C.Nombre LIKE '%$_REQUEST[nombre_cargo]%'";
	}

	if ($condicion != "") {
	  if (isset($_REQUEST['nombre_dependencia']) && $_REQUEST['nombre_dependencia'] != "") {
	    $condicion .= " AND D.Nombre LIKE '%$_REQUEST[nombre_dependencia]%'";
	  }
	} else {
	  if (isset($_REQUEST['nombre_dependencia']) && $_REQUEST['nombre_dependencia'] != "") {
	    $condicion .= " AND D.Nombre LIKE '%$_REQUEST[nombre_dependencia]%'";
	  }
	}

	$query = 'SELECT COUNT(*) AS Total 
	          FROM  Cargo C
	          INNER JOIN Dependencia D
	          on C.Id_Dependencia = D.Id_Dependencia '.$condicion;

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

	$query = 'SELECT C.*, D.Nombre as NombreDependencia
	          FROM  Cargo C
	          INNER JOIN Dependencia D
			    on C.Id_Dependencia = D.Id_Dependencia '.$condicion.' LIMIT '.$limit.','.$tamPag;

	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($query);
	$resultado["Cargos"] = $oCon->getData();
	unset($oCon);

	$resultado["numReg"] = $numReg;

	echo json_encode($resultado);
?>