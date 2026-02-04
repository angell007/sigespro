<?php
ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Dispensacion.xls"');
header('Cache-Control: max-age=0'); 
$permiso = permiso();

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
	$condicion .= " AND D.Numero_Documento=$_REQUEST[pac]";
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	$condicion .= " AND D.Id_Tipo_Servicio='$_REQUEST[tipo]'";

}

if (isset($_REQUEST['pend']) && $_REQUEST['pend'] == "No") {
	$condicion .= " AND PD.Cantidad_Formulada=PD.Cantidad_Entregada";
} elseif(isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si")  {
	$condicion .= " AND PD.Cantidad_Formulada>PD.Cantidad_Entregada";
}
if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
	$condicion .= " AND D.Codigo='$_REQUEST[dis]'";
}

if (isset($_REQUEST['cte']) && $_REQUEST['cte'] != "") {
	$condicion .= " AND PA.Nit='$_REQUEST[cte]'";
}
if (isset($_REQUEST['prod']) && $_REQUEST['prod'] != "") {
	$condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[prod]%'";
}
if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
	$condicion .= " AND PA.Nit LIKE '%$_REQUEST[nit]%'";
}
if (isset($_REQUEST['estado_facturacion']) && $_REQUEST['estado_facturacion'] != "") {
	$condicion .= " AND D.Estado_Facturacion LIKE '%$_REQUEST[estado_facturacion]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
	$condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}
if (isset($_REQUEST['servicio']) && $_REQUEST['servicio'] != "") {
	$condicion .= " AND D.Id_Servicio=$_REQUEST[servicio] ";
}
if (isset($_REQUEST['estado_disp']) && $_REQUEST['estado_disp'] != "") {
	$condicion .= " AND D.Estado_Dispensacion LIKE '%$_REQUEST[estado_disp]%'";
}


$query =
"SELECT PDA.numeroAutorizacion,
PA.*,
A.*,
A.Estado AS Estado_Auditoria,
D.Estado_Dispensacion,
IF(A.Id_Auditoria IS NULL, 'No', 'Si') AS Auditada,
D.Cuota AS Cuota_Moderadora,
D.Codigo,
D.Observaciones,
D.Fecha_Actual,
PA.Id_Paciente,
IFNULL((CASE
			WHEN D.Id_Tipo_Servicio = 7 THEN (FCAP.Codigo)
			ELSE (FAC.Codigo)	END),'No Facturada') AS Numero_Factura,
IFNULL((CASE
			WHEN D.Id_Tipo_Servicio = 7 THEN (FCAP.Fecha_Documento)
			ELSE (FAC.Fecha_Documento)	END), 'No Facturada') AS Fecha_Factura,
IF(D.Firma_Reclamante != ''	OR D.Acta_Entrega IS NOT NULL,	'Si',	'No') AS Soporte,
D.Acta_Entrega,
D.Firma_Reclamante,
PD.Nombre AS Punto_Dispensacion,

D.Estado_Facturacion,
DP.Nombre AS Departamento,
PA.Eps AS Nombre_Tercero,
PA.Regimen AS Regimen_Paciente,
CONCAT_WS(' ',
		CONCAT(F.Identificacion_Funcionario, ' -'),
		F.Nombres,
		F.Apellidos) AS Funcionario_Digita,
D.Causal_No_Pago,
TS.Nombre  AS Tipo_Servicio,
Serv.Nombre AS Servicio,
(SELECT 
		Act.Observacion
	FROM
		Actividad_Auditoria Act
	WHERE
		Act.Id_Auditoria = A.Id_Auditoria
			AND Act.Detalle LIKE '%correcta%'
	LIMIT 1) AS Nota_Auditoria, 
	GROUP_CONCAT(CONCAT_WS(';', DATE(AD.Fecha), concat(FA.Nombres, ' ', FA.Apellidos),  AD.Detalle) separator '|') as Actividades
FROM
Dispensacion D
	INNER JOIN (SELECT Id_Paciente,	CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) AS Nombre_Paciente, Tipo_Documento, Id_Regimen,Eps,IF(Id_Regimen = 1, 'Contributivo', 'Subsidiado') AS Regimen,Nit FROM Paciente) PA ON D.Numero_Documento = PA.Id_Paciente 
	INNER JOIN Punto_Dispensacion PD ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
	LEFT JOIN Positiva_Data PDA ON PDA.Id_Dispensacion = D.Id_Dispensacion 
	INNER JOIN Departamento DP ON PD.Departamento = DP.Id_Departamento 
	INNER JOIN (SELECT Nombres, Apellidos, Identificacion_Funcionario FROM Funcionario) F ON F.Identificacion_Funcionario = D.Identificacion_Funcionario 
	LEFT JOIN (SELECT A.Id_Auditoria, A.Estado, CONCAT_WS(' ',CONCAT(F.Identificacion_Funcionario,' -'),F.Nombres,F.Apellidos) AS Funcionario_Preauditoria, A.Id_Dispensacion FROM Auditoria A LEFT JOIN Funcionario F ON F.Identificacion_Funcionario = A.Funcionario_Preauditoria ) A ON A.Id_Dispensacion = D.Id_Dispensacion
	LEFT JOIN Servicio Serv ON Serv.Id_Servicio = D.Id_Servicio
	LEFT JOIN Factura_Capita FCAP ON FCAP.Id_Factura_Capita = D.Id_Factura
	LEFT JOIN Factura FAC ON FAC.Id_Factura = D.Id_Factura
	LEFT JOIN Tipo_Servicio TS	ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio
	LEFT JOIN Actividades_Dispensacion AD on AD.Id_Dispensacion = D.Id_Dispensacion
	LEFT JOIN Funcionario FA on FA.Identificacion_Funcionario = AD.Identificacion_Funcionario
	 $condicion
	 GROUP BY D.Id_Dispensacion
	 ";
	 
	 


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

$contenido = '<table border="1"><tr>';
$contenido .= '<td>Codigo</td>';
$contenido .= '<td>Fecha_Solicitud</td>';
$contenido .= '<td>Numero_Factura</td>';
$contenido .= '<td>Fecha_Factura</td>';
$contenido .= '<td>Identif_Tercero</td>';
$contenido .= '<td>Nombre_Tercero</td>';
$contenido .= '<td>Funcionario_Digita</td>';
$contenido .= '<td>Punto_Dispensacion</td>';
$contenido .= '<td>Tipo_Documento</td>';
$contenido .= '<td>Id_Paciente</td>';
$contenido .= '<td>Nombre_Paciente</td>';
$contenido .= '<td>Regimen_Paciente</td>';
$contenido .= '<td>Departamento</td>';
$contenido .= '<td>Tipo Servicio</td>';
$contenido .= '<td>¿Auditada?</td>';
$contenido .= '<td>Funcionario Auditor</td>';
$contenido .= '<td>Estado_Dispensacion</td>';
$contenido .= '<td>Estado Auditoria</td>';
$contenido .= '<td>Autorizacion</td>';
$contenido .= '<td>Soportes</td>';
$contenido .= '<td>Observaciones</td>';
$contenido .= '<td>Nota Auditoria</td>';
$contenido .= '<td>Actividades</td>';


$contenido .= '</tr>';


$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;

	$contenido .= '<tr>';
	
	$contenido .= '<td>' . $disp["Codigo"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Actual"] . '</td>';
	$contenido .= '<td>' . $disp["Numero_Factura"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Factura"] . '</td>';
	$contenido .= '<td>' . $disp["Nit"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre_Tercero"] . '</td>';	
	$contenido .= '<td>' . $disp["Funcionario_Digita"] . '</td>';
	$contenido .= '<td>' . $disp["Punto_Dispensacion"] . '</td>';
	$contenido .= '<td>' . $disp["Tipo_Documento"] . '</td>';
	$contenido .= '<td>' . $disp["Id_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Regimen_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Departamento"] . '</td>';
	$contenido .= '<td>' . $disp["Tipo_Servicio"] . '</td>';
	$contenido .= '<td>' . $disp["Auditada"] . '</td>';
	$contenido .= '<td>' . $disp["Funcionario_Preauditoria"] . '</td>';
	$contenido .= '<td>' . $disp["Estado_Dispensacion"] . '</td>';
	$contenido .= '<td>' . $disp["Estado_Auditoria"] . '</td>';
	$contenido .= '<td>' . $disp["numeroAutorizacion"] . '</td>';
	$contenido .= '<td>' . $disp["Soporte"] . '</td>';
	$contenido .= '<td>' . $disp["Observaciones"] . '</td>';
	$contenido .= '<td>' . $disp["Nota_Auditoria"] . '</td>';
	$actividades = explode("|", $disp['Actividades']);

	foreach ($actividades as $act ) {
		$contenido .= '<td>' . $act . '</td>';
	}
	$contenido .= '</tr>';
	
}

$contenido .= '</table>';


echo $contenido;

function permiso(){
	$identificacion_funcionario = $_SESSION["user"];
	if($identificacion_funcionario==''){
		$identificacion_funcionario=$_REQUEST['funcionario'];
	}
	
	$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$identificacion_funcionario; 
	$oCon= new consulta();
	$oCon->setQuery($query);
	$permisos = $oCon->getData();
	unset($oCon);

	$status = false; // Sin permisos

	if ($permisos) {
		$status = true;
	}

	return $status;
}

?>