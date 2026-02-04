<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= "WHERE F.Codigo LIKE '%$_REQUEST[cod]%'";
}

if ($condicion != "") {
    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
        $condicion .= " AND C.Nombre LIKE '%$_REQUEST[cliente]%'";
    }
} else {
    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
        $condicion .= "WHERE C.Nombre LIKE '%$_REQUEST[cliente]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['remision']) && $_REQUEST['remision'] != "") {
        $condicion .= " AND F.Remisiones LIKE '%$_REQUEST[remision]%'";
    }
} else {
    if (isset($_REQUEST['remision']) && $_REQUEST['remision'] != "") {
        $condicion .= "WHERE F.Remisiones LIKE '%$_REQUEST[remision]%'";
    } 
}


if ($condicion != "") {
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
        $condicion .= " AND F.Estado LIKE '%$_REQUEST[est]%'";
    }
} else {
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
        $condicion .= "WHERE F.Estado LIKE '%$_REQUEST[est]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND F.Fecha_Documento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
} else {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= "WHERE F.Fecha_Documento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
}





$query = 'SELECT COUNT(*) AS Total 
FROM Factura_Venta F
INNER JOIN Cliente C
ON C.Id_Cliente = F.Id_Cliente 
' . $condicion;


$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 15; 
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



$query = 'SELECT 
            F.Fecha_Documento as Fecha , 
            F.Codigo as Codigo, 
            F.Id_Factura_Venta as Id_Factura, 
            F.Estado,
            F.Procesada,
            F.Id_Resolucion,
            C.Nombre as NombreCliente, 
            F.Remisiones,
            F.Nota_Credito
          FROM Factura_Venta F
          INNER JOIN Cliente C
          ON F.Id_Cliente = C.Id_Cliente 
          '.$condicion.' 
          ORDER BY F.Fecha_Documento DESC LIMIT '.$limit.','.$tamPag  ;   

      
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Facturas"] = $oCon->getData();
unset($oCon);
/*
$i = -1;
foreach ($resultado["Facturas"] as $fact) {$i++;
    $query2 = 'SELECT Id_Remision, Codigo FROM Remision WHERE Id_Remision IN ('.$fact["Remisiones"].')';
echo $query2;
    $oCon= new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
    $resultado["Facturas"][$i]["Remisiones"] = $oCon->getData();
    unset($oCon);    
}*/

$resultado['numReg'] = $numReg;

//var_dump($resultado);
echo json_encode($resultado);

?>