<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= "WHERE Codigo LIKE '%$_REQUEST[cod]%'";
}

if ($condicion != "") {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= " AND Tipo='$_REQUEST[tipo]'";
    }
} else {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= "WHERE Tipo='$_REQUEST[tipo]'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['origen']) && $_REQUEST['origen'] != "") {
        $condicion .= " AND Nombre_Origen LIKE '%$_REQUEST[origen]%'";
    }
} else {
    if (isset($_REQUEST['origen']) && $_REQUEST['origen'] != "") {
        $condicion .= "WHERE Nombre_Origen LIKE '%$_REQUEST[origen]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['destino']) && $_REQUEST['destino'] != "") {
        $condicion .= " AND Nombre_Destino LIKE '%$_REQUEST[destino]%'";
    }
} else {
    if (isset($_REQUEST['destino']) && $_REQUEST['destino'] != "") {
        $condicion .= "WHERE Nombre_Destino LIKE '%$_REQUEST[destino]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['fase']) && $_REQUEST['fase'] != "") {
        $condicion .= " AND Estado_Alistamiento = $_REQUEST[fase]";
    }
} else {
    if (isset($_REQUEST['fase']) && $_REQUEST['fase'] != "") {
        $condicion .= "WHERE Estado_Alistamiento = $_REQUEST[fase]";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
        $condicion .= " AND Estado LIKE '%$_REQUEST[est]%'";
    }
} else {
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
        $condicion .= "WHERE Estado LIKE '%$_REQUEST[est]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND DATE_FORMAT(Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
} else {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= "WHERE DATE_FORMAT(Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
}
if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    if ($condicion != "") {
        $condicion .= " AND Identificacion_Funcionario=$_REQUEST[funcionario]";
    } else {
        $condicion .= " WHERE Identificacion_Funcionario=$_REQUEST[funcionario]";
    }
}



$query = 'SELECT COUNT(*) AS Total FROM Remision ' . $condicion;

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

$query = 'SELECT R.*, (CASE   
      WHEN R.Estado_Alistamiento = 0 THEN "1"   
      WHEN R.Estado_Alistamiento = 1 THEN "2"
      WHEN R.Estado_Alistamiento = 2 THEN "Listo"
   END ) as Fase, DATE_FORMAT(Fecha, "%d/%m/%Y") AS Fecha_Remision, (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision = R.Id_Remision) as Items FROM Remision R '.$condicion.' ORDER BY Codigo DESC, Fecha DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$remision['remisiones'] = $oCon->getData();
unset($oCon);

$remision['numReg'] = $numReg;

$i=-1;
foreach($remision['remisiones'] as $remisiones ){$i++;
$oItem=new complex($remisiones['Tipo_Origen'], 'Id_'.$remisiones['Tipo_Origen'], $remisiones['Id_Origen']);
$origen= $oItem->getData();
unset($oLista);
$oItem=new complex($remisiones['Tipo_Destino'], 'Id_'.$remisiones['Tipo_Destino'], $remisiones['Id_Destino']);
$destino=$oItem->getData();
unset($oItem);

$remision['remisiones'][$i]['Punto_Origen']=$origen['Nombre'];
$remision['remisiones'][$i]['Punto_Destino']=$destino['Nombre'];
    
}


echo json_encode($remision);
?>