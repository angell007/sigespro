<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.complex.php');
    include_once('../../../class/class.consulta.php');
    require('../../contabilidad/funciones.php');

    $condiciones = setCondiciones();

    $terceros = getListaTerceros(null, $condiciones);
    $total = count($terceros);

    ####### PAGINACIÓN ######## 
    $tamPag = 20; 
    $numReg = $total; 
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

    $strLimit = " LIMIT $limit,$tamPag";

    $terceros = getListaTerceros(null, $condiciones, $strLimit);

    $response = [
        "Terceros" => $terceros,
        "numReg" => $numReg
    ];

    echo json_encode($response);

	function setCondiciones() {
        $condiciones = '';

        if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != '') {
            $condiciones .= " WHERE r.Nit LIKE '%$_REQUEST[nit]%'";
        }

        if (isset($_REQUEST['tercero']) && $_REQUEST['tercero'] != '') {
            $condiciones .= $condiciones != '' ? " AND r.Nombre_Comercial LIKE '%$_REQUEST[tercero]%'" : " WHERE r.Nombre_Comercial LIKE '%$_REQUEST[tercero]%'";
        }

        return $condiciones;
    }
?>