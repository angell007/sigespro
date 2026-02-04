<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= ' WHERE  DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}else{
	$condicion .= ' WHERE  DATE(D.Fecha_Actual) =CURDATE()';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
		$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
		$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
	
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
		$condicion .= " AND D.Identificacion_Funcionario=$_REQUEST[func]";
	
}
if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
		$condicion .= " AND PA.Id_Paciente=$_REQUEST[pac]";
	
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
		$condicion .= " AND D.Id_Tipo_Servicio='$_REQUEST[tipo]'";
	
}

if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
		$condicion .= " AND D.Codigo='$_REQUEST[dis]'";
}

if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
	$condicion .= " AND PA.Nit LIKE '%$_REQUEST[nit]%'";
}
if (isset($_REQUEST['estado_facturacion']) && $_REQUEST['estado_facturacion'] != "") {
	$condicion .= " AND D.Estado_Facturacion LIKE '%$_REQUEST[estado_facturacion]%'";
}

if (isset($_REQUEST['servicio']) && $_REQUEST['servicio'] != "") {
	$condicion .= " AND D.Id_Servicio=$_REQUEST[servicio] ";
}
if (isset($_REQUEST['estado_disp']) && $_REQUEST['estado_disp'] != "") {
	$condicion .= " AND D.Estado_Dispensacion LIKE '%$_REQUEST[estado_disp]%'";
}

$query = 'SELECT 
COUNT(*) AS Total
FROM   Dispensacion D  
    INNER  JOIN ( SELECT Id_Paciente, CONCAT_WS(" ", Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido ) as Paciente,Eps,Nit FROM Paciente ) PA 
    ON D.Numero_Documento=PA.Id_Paciente 
    INNER JOIN Punto_Dispensacion PD ON D.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
     LEFT JOIN Positiva_Data PDA ON PDA.Id_Dispensacion = D.Id_Dispensacion
    INNER JOIN ( SELECT TS.Nombre as Tipo_Servicio,TS.Id_Tipo_Servicio,S.Nombre as Servicio  FROM Tipo_Servicio TS INNER JOIN Servicio S ON TS.Id_Servicio=S.Id_Servicio) TS ON D.Id_Tipo_Servicio=TS.Id_Tipo_Servicio
    INNER JOIN Departamento DP
ON PD.Departamento=DP.Id_Departamento
'.$condicion.'  ';


$oCon= new consulta();
$oCon->setQuery($query);
$dispensaciones= $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $dispensaciones["Total"]; 
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
   PA.*, PDA.numeroAutorizacion,
    D.Codigo,
    D.Observaciones,
    DATE(D.Fecha_Actual) AS Fecha_Actual,
    PA.Id_Paciente,
    IF(D.Firma_Reclamante != ""
            OR D.Acta_Entrega IS NOT NULL,
        "Si",
        "No") AS Soporte,
    D.Acta_Entrega,
    D.Firma_Reclamante,
    COUNT(PR.Id_Producto_Dispensacion) AS Items,
    PR.Id_Dispensacion,
    PD.Nombre AS Punto_Dispensacion,
    TS.Servicio,
    TS.Tipo_Servicio,
    D.Estado_Facturacion,
    DP.Nombre AS Departamento, PA.Eps as Nombre_Tercero, PA.Regimen as Regimen_Paciente 
	FROM
    Dispensacion D
    LEFT JOIN Positiva_Data PDA ON PDA.Id_Dispensacion = D.Id_Dispensacion
        INNER JOIN
    Producto_Dispensacion PR ON D.Id_Dispensacion = PR.Id_Dispensacion
        INNER JOIN
    (SELECT 
        Id_Paciente,
            CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) AS Nombre_Paciente,Tipo_Documento,
            Eps, IF(Id_Regimen=1, "Contributivo","Subsidiado") as Regimen,Nit
    FROM
        Paciente) PA ON D.Numero_Documento = PA.Id_Paciente
        INNER JOIN
    Punto_Dispensacion PD ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
        INNER JOIN
    (SELECT 
        TS.Nombre AS Tipo_Servicio,
            TS.Id_Tipo_Servicio,
            S.Nombre AS Servicio
    FROM
        Tipo_Servicio TS
    INNER JOIN Servicio S ON TS.Id_Servicio = S.Id_Servicio) TS ON D.Id_Tipo_Servicio = TS.Id_Tipo_Servicio
        INNER JOIN
    Departamento DP ON PD.Departamento = DP.Id_Departamento
'.$condicion . ' group by D.Id_Dispensacion LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$dispensaciones= $oCon->getData();
unset($oCon);

$resultado['dispensaciones'] = $dispensaciones;
$resultado['numReg'] = $numReg;

echo json_encode($resultado);

?>