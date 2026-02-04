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
    $condicion .= " WHERE R.Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    $condicion .= " WHERE R.Tipo='$_REQUEST[tipo]'";
}

if (isset($_REQUEST['origen']) && $_REQUEST['origen'] != "") {
    $condicion .= " WHERE R.Nombre_Origen LIKE '%$_REQUEST[origen]%'";
}

if (isset($_REQUEST['destino']) && $_REQUEST['destino'] != "") {
    $condicion .= " WHERE R.Nombre_Destino LIKE '%$_REQUEST[destino]%'";
}

if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
    $condicion .= " WHERE R.Estado='$_REQUEST[est]'";
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);

    $condicion .= " WHERE DATE_FORMAT(R.Fecha,'%Y-%m-%d') BETWEEN  '$fecha_inicio' AND '$fecha_fin'";
}

$condicion_principal = "WHERE Estado_Alistamiento=2";

if (isset($_REQUEST['fases']) && $_REQUEST['fases'] == 1) {
    $condicion_principal = "WHERE Estado_Alistamiento=0";
} elseif (isset($_REQUEST['fases']) && $_REQUEST['fases'] == 2) {
    $condicion_principal = "WHERE Estado_Alistamiento=1";
}

$query = 'SELECT 
            COUNT(*) AS Total
          FROM Remision R
          '.$condicion_principal ;

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

$query = 'SELECT * FROM
            (   SELECT 
                R.Id_Remision, R.Codigo, R.Tipo,
                R.Tipo_Origen, R.Tipo_Destino, R.Tipo_Bodega,
                R.Guia, R.Empresa_Envio, R.Estado_Alistamiento,
                R.Fecha, R.Id_Origen, R.Id_Destino,
                R.Nombre_Origen,
                R.Nombre_Destino,
                R.Estado,
                (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision = R.Id_Remision) as Items
                FROM Remision R
                '.$condicion_principal.'
                UNION
                SELECT
                D.Id_Devolucion_Compra AS Id_Remision, 
                D.Codigo, "Devolucion" AS Tipo,
                "Bodega_Nuevo" AS Tipo_Origen , "Proveedor" , "Medicamentos" AS Tipo_Bodega,
                D.Guia, D.Empresa_Envio, 
                D.Estado_Alistamiento,
                D.Fecha, D.Id_Bodega_nuevo AS Id_Origen, 
                D.Id_Proveedor AS Id_Destino,
                (SELECT B.Nombre FROM Bodega_Nuevo B WHERE B.Id_Bodega_Nuevo = D.Id_Bodega_Nuevo) AS Nombre_Origen,
                (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor = D.Id_Proveedor) AS Nombre_Destino,
                D.Estado,
                (SELECT COUNT(*) FROM Producto_Devolucion_Compra PR WHERE PR.Id_Devolucion_Compra = D.Id_Devolucion_Compra) as Items
                FROM Devolucion_Compra D
               '.$condicion_principa.'
                
            ) AS R
            ' . $condicion . ' ORDER  BY Fecha DESC LIMIT ' . $limit . ',' . $tamPag  ;
// echo $query;
$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$remisiones['remisiones'] = $oCon->getData();

unset($oCon);

$i=-1;
foreach($remisiones['remisiones'] as $remision){ $i++;
   
    $fecha_nuevo_modelo = strtotime('2020-07-22');

    $pos = strtotime($remision["Fecha"]);
    //remisiones mayores del nuevo modelo
    if ($pos > $fecha_nuevo_modelo  && $remision['Tipo']!='Devolucion' ) {
        # code...
        if ( $remision["Tipo_Origen"] == 'Bodega') {
            # code...
            $remisiones['remisiones'][$i]["Tipo_Origen"]='Bodega_Nuevo';
        }else if($remision["Tipo_Destino"] == 'Bodega'){
            $remisiones['remisiones'][$i]["Tipo_Destino"]='Bodega_Nuevo';
        }
        
    }
    $oItem = new complex(  $remisiones['remisiones'][$i]["Tipo_Origen"],"Id_".$remisiones['remisiones'][$i]["Tipo_Origen"],$remision["Id_Origen"]);
    $or=$oItem->getData();
    unset($oItem);
    $remisiones['remisiones'][$i]["NombreOrigen"]=$or["Nombre"];
    
    $oItem = new complex( $remisiones['remisiones'][$i]["Tipo_Destino"],"Id_".$remisiones['remisiones'][$i]["Tipo_Destino"],$remision["Id_Destino"]);
    $or=$oItem->getData();
    unset($oItem);
    $remisiones['remisiones'][$i]["NombreDestino"]=$or["Nombre"];
}

$remisiones['numReg'] = $numReg;

echo json_encode($remisiones);
?>