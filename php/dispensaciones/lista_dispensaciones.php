<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$hoy = date('Y-m-d');
$ultimos_dos_meses = strtotime('-2 month', strtotime($hoy));
$ultimos_dos_meses = date('Y-m-01', $ultimos_dos_meses);

$condicion_fechas = '';

$condicion = '';
// exit;
$orden ="ORDER BY Id_Dispensacion DESC";
$condiciones = [];
// $condiciones_dispensacion = [];

if (isset($_REQUEST['orden']) && $_REQUEST['orden'] != "") {
    if($_REQUEST['orden'] =="Fecha"){
        $orden ="ORDER BY Id_Dispensacion DESC";
    }
    elseif($_REQUEST['orden'] =="Platino"){
        $orden ="ORDER BY Platino DESC, Id_Dispensacion DESC";
    }
    elseif($_REQUEST['orden'] =="Tutela"){
        $orden ="ORDER BY Tiene_Tutela DESC, Id_Dispensacion DESC";
    }
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);

    $condicion .= " WHERE DATE_FORMAT(Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    array_push($condiciones, "(DATE_FORMAT(D.Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin')  ");
} else {
    $fecha_fin = date('Y-m-d');
    $fecha_inicio = date('Y-m-d');
}

$condicion1 .= " WHERE DATE_FORMAT(Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
$condicion2 .= " (DATE_FORMAT(Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin') AND ";

if ($condicion != '') {
    $condicion1 = '';
    $condicion2 = '';
}
if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    array_push($condiciones, "D.Codigo LIKE '%$_REQUEST[cod]%'");
    // array_push($condiciones_dispensacion, "D.Codigo LIKE '%$_REQUEST[cod]%'");

}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo']) {
    if ($_REQUEST['tipo'] != 'todos') {
        array_push($condiciones, "D.Id_Tipo_Servicio=$_REQUEST[tipo]");
        // array_push($condiciones_dispensacion, "D.Id_Tipo_Servicio=$_REQUEST[tipo]");
    }
    
}

if (isset($_REQUEST['pers']) && $_REQUEST['pers']) {
    $numero= (int)$_REQUEST['pers'];
    if($numero!==0){
        array_push($condiciones, "D.Numero_Documento like '%$_REQUEST[pers]%'");
        // array_push($condiciones_dispensacion, "D.Numero_Documento like '%$_REQUEST[pers]%'");
    }
    
    array_push($condiciones, "(CONCAT(PC.Primer_Nombre, ' ',  PC.Primer_Apellido) LIKE '%$_REQUEST[pers]%' or D.Numero_Documento='$_REQUEST[pers]')");
}

if (isset($_REQUEST['punto']) && $_REQUEST['punto']) {
    array_push($condiciones, "(P.Nombre LIKE '%$_REQUEST[punto]%' OR PROPH.Nombre LIKE '%$_REQUEST[punto]%' )");
}

if (isset($_REQUEST['dep']) && $_REQUEST['dep']) {
    array_push($condiciones, "L.Nombre LIKE '%$_REQUEST[dep]%'");

}

if (isset($_REQUEST['fact']) && $_REQUEST['fact']) {
    array_push($condiciones, " D.Estado_Facturacion='$_REQUEST[fact]'");
    // array_push($condiciones_dispensacion, " D.Estado_Facturacion='$_REQUEST[fact]'");
}

$auditoria = (isset($_REQUEST['auditoria']) ? $_REQUEST['auditoria'] : '');
if ($auditoria) {
    if ($auditoria == "Sin Auditar") {
        array_push($condiciones, "D.Estado_Auditoria like '$auditoria'");
    } else {
        array_push($condiciones, "(A.Estado like '$auditoria' OR A1.Estado like '$auditoria')");
    }
}


    if (isset($_REQUEST['est']) && $_REQUEST['est']) {
        array_push($condiciones, "D.Estado_Dispensacion='$_REQUEST[est]'");
        // array_push($condiciones_dispensacion, "D.Estado_Dispensacion='$_REQUEST[est]'");
    }

if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    // array_push($condiciones_dispensacion, "D.Identificacion_Funcionario=$_REQUEST[funcionario]");    
    array_push($condiciones, "D.Identificacion_Funcionario=$_REQUEST[funcionario]");    
}

if (isset($_REQUEST['id_punto']) && $_REQUEST['id_punto'] != "") {
    array_push($condiciones, "(D.Id_Punto_Dispensacion=$_REQUEST[id_punto] OR P.Id_Propharmacy=$_REQUEST[id_punto] )");
}

if (isset($_REQUEST['eps']) && $_REQUEST['eps'] != "") {
    array_push($condiciones, "PC.Nit=$_REQUEST[eps]");
}


if (isset($_REQUEST['pend']) && $_REQUEST['pend'] != "") {
    // array_push($condiciones_dispensacion, $_REQUEST['pend'] == "Si" ? "D.Pendientes>0" : " D.Pendientes=0");
    array_push($condiciones, $_REQUEST['pend'] == "Si" ? "D.Pendientes>0" : " D.Pendientes=0");
}

if (count($_REQUEST) == 0) {
    $condicion_fechas .= " AND  DATE(D.Fecha_Actual) BETWEEN '$ultimos_dos_meses' AND '$hoy'";
} elseif (count($_REQUEST) == 1) {
    $condicion_fechas .= " AND DATE(D.Fecha_Actual) BETWEEN '$ultimos_dos_meses' AND '$hoy'";
}

$condicion = count($condiciones)>0? "WHERE ". implode(" AND ", $condiciones):'';
// $condicion_dis  = count($condiciones_dispensacion)>0? implode(" AND ", $condiciones_dispensacion)." AND ":'';

if ($condicion != '') {
    $condicion1 = '';
    $condicion2 = '';
}

// $condicion_dis .= $condicion2;
// echo $condicion_dis; exit;
$query = 'SELECT COUNT(*) AS Total
FROM Dispensacion D
Left JOIN Auditoria A ON A.Id_Dispensacion=D.Id_Dispensacion
LEFT JOIN Auditoria A1 ON A1.Id_Auditoria = D.Id_Auditoria
STRAIGHT_JOIN Paciente PC
on D.Numero_Documento=PC.Id_Paciente
STRAIGHT_JOIN Punto_Dispensacion P
on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
STRAIGHT_JOIN Departamento L
ON P.Departamento = L.Id_Departamento
Left Join Punto_Dispensacion PROPH On PROPH.Id_Punto_Dispensacion= P.Id_Propharmacy
' . $condicion . $condicion1;

$oCon = new consulta();

$oCon->setQuery($query);
$dispensaciones = $oCon->getData();
// $dispensaciones = [];
unset($oCon);

####### PAGINACIÓN ########
$tamPag = 20;
$numReg = $dispensaciones["Total"];
// $numReg = 350000;
$paginas = ceil($numReg / $tamPag);
$limit = "";
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') {
    $paginaAct = 1;
    $limit = 0;
} else {
    $paginaAct = $_REQUEST['pag'];
    $limit = ($paginaAct - 1) * $tamPag;
}

$query = "SELECT
        D.Codigo,
        D.Fecha_Actual,
        D.Id_Punto_Dispensacion,
        D.Numero_Documento, 
        DATE_FORMAT(D.Fecha_Actual, '%d/%m/%Y') AS Fecha_Dis,
        TS.Nombre AS Nombre_Tipo_Servicio,
        CONCAT_WS(' ',  PC.Primer_Nombre,  PC.Segundo_Nombre,  PC.Primer_Apellido,  PC.Segundo_Apellido) AS Paciente,
        CONCAT(S.Nombre,' - ',TS.Nombre) AS Tipo,
        P.Nombre AS Punto_Dispensacion,
        P.Wacom,D.Acta_Entrega,
        L.Nombre AS Departamento,
        D.Estado EstadoEntrega,
        D.Estado_Dispensacion AS Estado,
        D.Estado_Facturacion,
        D.Estado_Dispensacion,
        CONCAT_WS(' ', D.Estado_Auditoria, A.Estado, A1.Estado) AS Estado_Auditoria,
        D.Id_Factura,
        ifnull(A.Id_Auditoria, A1.Id_Auditoria) AS Id_Auditoria,
        D.Pendientes,
        D.Id_Dispensacion,
        IF(D.Estado_Dispensacion ='Anulada' OR PD.Estado ='Anulada', Null, PD.Tutela	) AS Tiene_Tutela,
        IF(D.Estado_Dispensacion ='Anulada' OR PD.Estado ='Anulada', NULL, PD.Tutela	) AS Tutela,
        IF(D.Estado_Dispensacion ='Anulada' OR PD.Estado ='Anulada', NULL, PD.Platino	) AS Platino

        FROM Dispensacion D
        LEFT JOIN (
            (SELECT PDA.id, D2.Id_Dispensacion AS Id_Dispensacion, trim(if(PDA.RLmarcaEmpleador!='', PDA.RLmarcaEmpleador, NULL)) AS Platino,  if(PDA.tieneTutela !='' || PDA.tieneTutela !='0', PDA.tieneTutela , NULL)AS Tutela, PDA.Estado,  D2.Codigo 
				  FROM Positiva_Data PDA 
				  inner JOIN Dispensacion D2 on PDA.id = D2.Id_Positiva_Data  )
                  Union all (
                      SELECT PDA.id, D2.Id_Dispensacion AS Id_Dispensacion, trim(if(PDA.RLmarcaEmpleador!='', PDA.RLmarcaEmpleador, NULL)) AS Platino,  if(PDA.tieneTutela !='' || PDA.tieneTutela !='0', PDA.tieneTutela , NULL)AS Tutela, PDA.Estado,  D2.Codigo 
				  FROM Positiva_Data PDA 
				  inner JOIN Dispensacion D2 on PDA.Id_Dispensacion = D2.Id_Dispensacion
                  )  
        )PD ON PD.Id_Dispensacion = D.Id_Dispensacion 
        LEFT JOIN Auditoria A ON A.Id_Dispensacion=D.Id_Dispensacion
		LEFT JOIN Auditoria A1 ON A1.Id_Auditoria = D.Id_Auditoria
        STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente 
        STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
        LEFT JOIN Punto_Dispensacion PROPH ON PROPH.Id_Punto_Dispensacion= P.Id_Propharmacy 
        STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
        LEFT JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio
        LEFT JOIN Servicio S ON TS.Id_Servicio=S.Id_Servicio 
        -- $condicion_dis
          $condicion $condicion1
          $orden LIMIT $limit , $tamPag";

        
$oCon = new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones["dispensaciones"] = $oCon->getData();
$dispensaciones["indicadores"] = [];
unset($oCon);


$dispensaciones["numReg"] = $numReg;

$condicion2 = " AND  DATE_FORMAT(Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin' ";
$condicion3 = " AND  DATE_FORMAT(Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin' ";

if (isset($_REQUEST['id_punto']) && $_REQUEST['id_punto'] != "") {
    $condicion2 .= " AND Id_Punto_Dispensacion=$_REQUEST[id_punto]  ";
}

if ($condicion2 != "" && $_REQUEST['id_punto'] != '' && isset($_REQUEST['id_punto'])) {
    $condicion3 = " AND Id_Punto_Dispensacion=$_REQUEST[id_punto] ";
}
echo json_encode($dispensaciones);