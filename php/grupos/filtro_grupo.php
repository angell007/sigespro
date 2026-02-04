<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$condicion = '';

	if (isset($_REQUEST['nombre_grupo']) && $_REQUEST['nombre_grupo'] != "") {
	  $condicion .= " WHERE Nombre LIKE '%$_REQUEST[nombre_grupo]%'";
	}

	$query = 'SELECT COUNT(*) AS Total 
	          FROM  Grupo'.$condicion;

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

	$query = 'SELECT *
	          FROM Grupo'.$condicion.' LIMIT '.$limit.','.$tamPag;

	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($query);
	$resultado["Grupos"] = $oCon->getData();
	unset($oCon);

	$resultado["numReg"] = $numReg;

	echo json_encode($resultado);
?>