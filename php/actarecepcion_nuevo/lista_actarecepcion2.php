<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';
$condicion2 = '';
$buscarActaRecepcionRemision = true; //validar si no hay campos de busqueda únicos de Acta Recepcion 
//el estado de la acta recepcion (aprobada-acomodada)
if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
    $estado =$_REQUEST['estado'];
}


if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND ARC.Codigo LIKE '%$_REQUEST[cod]%'";
    $condicion2.=  " AND ARC.Codigo LIKE '%$_REQUEST[cod]%'";
    $condicion3.=  " AND AI.Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['compra']) && $_REQUEST['compra'] != "") {
    $condicion .= " AND (OCN.Codigo LIKE '%$_REQUEST[compra]%')";
    $buscarActaRecepcionRemision = false;
}

if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != "") {
    $condicion .= " AND P.Nombre LIKE '%$_REQUEST[proveedor]%'";
    $buscarActaRecepcionRemision = false;

}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND DATE_FORMAT(ARC.Fecha_Creacion, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";

    $condicion2 .= " AND DATE_FORMAT(ARC.Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

if (isset($_REQUEST['fecha2']) && $_REQUEST['fecha2'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha2'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha2'])[1]);
    $condicion .= " AND ((OCN.Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'))";

    $buscarActaRecepcionRemision = false;
}

if (isset($_REQUEST['fact']) && $_REQUEST['fact'] != "") {
    $condicion .= " HAVING Facturas LIKE '%$_REQUEST[fact]%'";
    $buscarActaRecepcionRemision = false;
}

$query = 'SELECT COUNT(*) AS Total, ( SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = ARC.Id_Acta_Recepcion ) as Facturas
        FROM Acta_Recepcion ARC
        LEFT JOIN Funcionario F
        ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
        LEFT JOIN Orden_Compra_Nacional OCN
        ON OCN.Id_Orden_Compra_Nacional = ARC.Id_Orden_Compra_Nacional
        INNER JOIN Bodega_Nuevo B
        ON B.Id_Bodega_Nuevo = ARC.Id_Bodega_Nuevo
        INNER JOIN Proveedor P
        ON P.Id_Proveedor = ARC.Id_Proveedor
        WHERE ARC.Estado = "'.$estado.'" AND ARC.Tipo_Acta = "Bodega" '.$condicion;



$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

$total_acta_recepcion_remision["Total"]=0;

//buscar la cantidad de las actas recepcion remision
if ($estado=='Aprobada' && $buscarActaRecepcionRemision) {
 
$query='SELECT COUNT(*) AS Total
        FROM Acta_Recepcion_Remision ARC
        INNER JOIN Remision R
        ON ARC.Id_Remision=R.Id_Remision
        WHERE ARC.Tipo="Bodega" AND ARC.Id_Bodega_Nuevo IS NOT NULL AND  ARC.Estado = "'.$estado.'" '.$condicion2;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $total_acta_recepcion_remision = $oCon->getData();
        unset($oCon);
}


if ($estado=='Aprobada' && $buscarActaRecepcionRemision) {
 
    $query='SELECT COUNT(*) AS Total
            FROM Ajuste_Individual AI
            WHERE Estado_Entrada_Bodega  = "'.$estado.'" '.$condicion3;
            $oCon= new consulta();
            $oCon->setQuery($query);
            $total_Ajuste_Individual = $oCon->getData();
            unset($oCon);
}


####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $total_acta_recepcion_remision["Total"]  + $total['Total'] + $total_Ajuste_Individual['Total']; 
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
        


//buscar  las actas de recepcion remision
if ($estado=='Aprobada' && $buscarActaRecepcionRemision) {
    

            $query = 'SELECT ARC.Id_Acta_Recepcion_Remision, ARC.Codigo,  ARC.Fecha AS "Fecha_Creacion",
              "INTERNA" Fecha_Compra_N , "INTERNA" Codigo_Compra_N, "INTERNA" Proveedor, "INTERNA" Facturas,
              F.Imagen, R.Codigo as Codigo_Remision
            FROM Acta_Recepcion_Remision ARC 
            INNER JOIN Funcionario F
            ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
            INNER JOIN Remision R
            ON ARC.Id_Remision=R.Id_Remision
            WHERE ARC.Tipo="Bodega" ' .$condicion2.' AND ARC.Estado = "'.$estado.'" AND ARC.Id_Bodega_Nuevo IS NOT NULL  ORDER BY Fecha_Creacion DESC, Codigo DESC LIMIT '.$limit.','.$tamPag;
          /* WHERE ARC.Tipo="Bodega" AND ARC.Id_Bodega IN ('.$bodegas['Id_Bodega'].')' .$condicion.' ORDER BY Fecha DESC, Codigo DESC LIMIT '.$limit.','.$tamPag; */
           
            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $Actas_Recepcion_Remision = $oCon->getData();
            unset($oCon);
    
}

//buscar  los ajustes individuales

if ($estado=='Aprobada' && $buscarActaRecepcionRemision) {
    

    $query = 'SELECT AI.Id_Ajuste_Individual, AI.Codigo,  AI.Fecha AS "Fecha_Creacion",
      "INTERNA" Fecha_Compra_N , "INTERNA" Codigo_Compra_N, "INTERNA" Proveedor, "INTERNA" Facturas,
      F.Imagen, "AJUSTE INDIVIDUAL" as Codigo_Remision
    FROM Ajuste_Individual AI 
    INNER JOIN Funcionario F
    ON F.Identificacion_Funcionario = AI.Identificacion_Funcionario
    WHERE AI.Tipo="Entrada" AND AI.Origen_Destino = "Bodega" ' .$condicion3.' AND AI.Estado_Entrada_Bodega = "'.$estado.'" AND AI.Id_Origen_Destino IS NOT NULL 
     ORDER BY Fecha_Creacion DESC, Codigo DESC LIMIT '.$limit.','.$tamPag;
   
     
     
         $oCon= new consulta();
         $oCon->setTipo('Multiple');
         $oCon->setQuery($query);
         $Ajuste_Individual = $oCon->getData();
         unset($oCon);
     
}


$query = 'SELECT ARC.Id_Acta_Recepcion, ARC.Codigo, ARC.Fecha_Creacion, F.Imagen, B.Nombre as Bodega,
OCN.Codigo as Codigo_Compra_N, P.Nombre as Proveedor,
OCN.Fecha as Fecha_Compra_N, 
(
    CASE 
        WHEN ARC.Tipo = "Nacional" THEN ARC.Id_Orden_Compra_Nacional
        ELSE ARC.Id_Orden_Compra_Internacional
    END
) AS Id_Orden_Compra,
( SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = ARC.Id_Acta_Recepcion ) as Facturas,
ARC.Tipo
FROM Acta_Recepcion ARC 
LEFT JOIN Funcionario F
ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
LEFT JOIN Orden_Compra_Nacional OCN
ON OCN.Id_Orden_Compra_Nacional = ARC.Id_Orden_Compra_Nacional
INNER JOIN Bodega_nuevo B
ON B.Id_Bodega_Nuevo = ARC.Id_Bodega_Nuevo
INNER JOIN Proveedor P
ON P.Id_Proveedor = ARC.Id_Proveedor
WHERE ARC.Estado =  "'.$estado.'" AND ARC.Tipo_Acta = "Bodega"
'.$condicion.' ORDER BY Fecha_Creacion DESC, Codigo DESC LIMIT '.$limit.','.$tamPag;

//echo $query;

//echo $query;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$actarecepcion['actarecepciones'] = $oCon->getData();
unset($oCon);
          
$actarecepcion['numReg'] = $numReg;
if ($Actas_Recepcion_Remision) {
    # code...
    $actarecepcion['actarecepciones']=array_merge($actarecepcion['actarecepciones'],$Actas_Recepcion_Remision,$Ajuste_Individual);
}
echo json_encode($actarecepcion);



function bodega_funcionario($id_funcionario){
    $query_bodegas_funcionario = 'SELECT GROUP_CONCAT(Id_Bodega_Nuevo) AS Id_Bodega_Nuevo
                                FROM Funcionario_Bodega_Nuevo FB
                                WHERE FB.Identificacion_Funcionario ='.$id_funcionario;
    $oCon= new consulta();
    $oCon->setQuery($query_bodegas_funcionario);
    $bodegas = $oCon->getData();
    unset($oCon); 

    return $bodegas;
          
}
?>