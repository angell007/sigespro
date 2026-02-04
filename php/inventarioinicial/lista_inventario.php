<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    $condicion .= "WHERE PRD.Principio_Activo LIKE '%$_REQUEST[nom]%' OR PRD.Presentacion LIKE '%$_REQUEST[nom]%' OR PRD.Concentracion LIKE '%$_REQUEST[nom]%' OR PRD.Nombre_Comercial LIKE '%$_REQUEST[nom]%'";
}

if ($condicion != "") {
    if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != "") {
        $condicion .= " AND PRD.Laboratorio_Comercial LIKE '%$_REQUEST[lab]%'";
    }
} else {
    if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != "") {
        $condicion .= "WHERE PRD.Laboratorio_Comercial LIKE '%$_REQUEST[lab]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['lote']) && $_REQUEST['lote'] != "") {
        $condicion .= " AND Lote LIKE '%$_REQUEST[lote]%'";
    }
} else {
    if (isset($_REQUEST['lote']) && $_REQUEST['lote'] != "") {
        $condicion .= "WHERE Lote LIKE '%$_REQUEST[lote]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
        $condicion .= " AND b.Nombre LIKE '%$_REQUEST[bod]%'";
    }
} else {
    if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
        $condicion .= "WHERE b.Nombre LIKE '%$_REQUEST[bod]%'";
    }
}

$query='SELECT COUNT(*) AS Total
FROM Inventario_Inicial I
INNER JOIN Producto PRD
On I.Id_Producto=PRD.Id_Producto 
INNER JOIN Bodega b ON I.Id_Bodega=b.Id_Bodega ' . $condicion;

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

$query='SELECT I.*, PRD.Laboratorio_Generico ,
                    CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ", PRD.Cantidad," ", PRD.Unidad_Medida, " ") as Nombre_Producto, PRD.Tipo,
                    PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, 
                    FORMAT(ROUND(I.Cantidad/PRD.Cantidad_Presentacion), 0) AS etiquetas, 
                    ROUND((I.Cantidad/PRD.Cantidad_Presentacion)/2) AS copias
FROM Inventario_Inicial I
INNER JOIN Producto PRD
On I.Id_Producto=PRD.Id_Producto 
INNER JOIN Bodega b ON I.Id_Bodega=b.Id_Bodega '.$condicion.' LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$inventario['inventarios'] = $oCon->getData();
unset($oCon);
$i=-1;
foreach($inventario['inventarios'] as $inventarios){$i++;
    //echo $inventarios["Nombre_Comercial"];
    if($inventarios["Tipo"]=="Material"){
        
        $inventario['inventarios'][$i]["Nombre_Producto"]=$inventarios["Nombre_Comercial"];
    }

    if($inventarios["Id_Bodega"]==0){
        $query2='SELECT B.Nombre as Nombre_Bodega
        FROM Punto_Dispensacion B
        WHERE Id_Punto_Dispensacion='.$inventarios["Id_Punto_Dispensacion"];
        //echo $query2;
        $oCon= new consulta();
        $oCon->setQuery($query2);
        $nombre = $oCon->getData();
        //var_dump( $nombre);
        unset($oCon);
        $inventario['inventarios'][$i]["Nombre_Bodega"]=$nombre["Nombre_Bodega"];
        
        
       
    }elseif($inventarios["Id_Punto_Dispensacion"]==0){
         $query='SELECT B.Nombre as Nombre_Bodega
        FROM Bodega B
        WHERE Id_Bodega='.$inventarios["Id_Bodega"];
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $nombre = $oCon->getData();
    unset($oCon);
    $inventario['inventarios'][$i]["Nombre_Bodega"]=$nombre["Nombre_Bodega"]; 
    }
}

$inventario['numReg'] = $numReg;

echo json_encode($inventario);
?>