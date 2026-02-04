<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','2048M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

/* require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php'; */

 header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte Dispensacion.csv"');  


// $objPHPExcel = new PHPExcel;
//$permiso = permiso();

$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}else{
	$condicion .= 'WHERE  DATE(D.Fecha_Actual)=CURDATE()';
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
	$condicion .= " AND D.Pendientes = 0";
} elseif(isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si")  {
	$condicion .= " AND D.Pendientes > 0 ";
}

if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
	$condicion .= " AND D.Codigo='$_REQUEST[dis]'";
}

if (isset($_REQUEST['cte']) && $_REQUEST['cte'] != "") {
	$condicion .= " AND PA.Nit='$_REQUEST[cte]'";
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
PA.*,A.*,A.Estado as Estado_Auditoria,D.Estado_Dispensacion,IF(A.Id_Auditoria IS NULL, "No", "Si") AS Auditada,D.Cuota as Cuota_Moderadora,
 D.Codigo,
 D.Fecha_Actual ,
 PA.Id_Paciente,
 IFNULL((
	CASE
		WHEN D.Id_Tipo_Servicio = 7 THEN (SELECT Codigo FROM Factura_Capita WHERE Id_Factura_Capita = D.Id_Factura)
		ELSE (SELECT Codigo FROM Factura WHERE Id_Factura = D.Id_Factura)
	END
),"No Facturada") as Numero_Factura,
IFNULL((
	CASE
		WHEN D.Id_Tipo_Servicio = 7 THEN (SELECT Fecha_Documento FROM Factura_Capita WHERE Id_Factura_Capita = D.Id_Factura)
		ELSE (SELECT Fecha_Documento FROM Factura WHERE Id_Factura = D.Id_Factura)
	END
),"No Facturada") as Fecha_Factura,
 IF(D.Firma_Reclamante != ""
		 OR D.Acta_Entrega IS NOT NULL,
	 "Si",
	 "No") AS Soporte,
 D.Acta_Entrega,
 D.Firma_Reclamante,
 PD.Nombre AS Punto_Dispensacion,

 D.Estado_Facturacion,
 DP.Nombre AS Departamento, PA.Eps as Nombre_Tercero, PA.Regimen as Regimen_Paciente,
 CONCAT_WS(" ",CONCAT(F.Identificacion_Funcionario," -"),F.Nombres,F.Apellidos) as Funcionario_Digita,  D.Causal_No_Pago, (SELECT Nombre FROM
	 Tipo_Servicio WHERE Id_Tipo_Servicio=D.Id_Tipo_Servicio
 ) as Tipo_Servicio, (SELECT Nombre FROM
	 Servicio WHERE Id_Servicio=D.Id_Servicio) as Servicio
 FROM
 Dispensacion D
	 INNER JOIN
 (SELECT 
	 Id_Paciente,
		 CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) AS Nombre_Paciente,Tipo_Documento,Id_Regimen,
		 Eps, IF(Id_Regimen=1, "Contributivo","Subsidiado") as Regimen,Nit
 FROM
	 Paciente) PA ON D.Numero_Documento = PA.Id_Paciente
	 INNER JOIN
 Punto_Dispensacion PD ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion

 
	 INNER JOIN
 Departamento DP ON PD.Departamento = DP.Id_Departamento
 INNER JOIN (SELECT Nombres, Apellidos, Identificacion_Funcionario FROM Funcionario) F 
ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
 LEFT JOIN (SELECT A.Id_Auditoria, A.Estado, CONCAT_WS(" ",CONCAT(F.Identificacion_Funcionario," -"),F.Nombres,F.Apellidos) AS Funcionario_Preauditoria, A.Id_Dispensacion FROM Auditoria A INNER JOIN Funcionario F ON F.Identificacion_Funcionario = A.Funcionario_Preauditoria ) A ON A.Id_Dispensacion = D.Id_Dispensacion
'.$condicion;



$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

echo "Codigo;Fecha_Solicitud;Numero_Factura;Fecha_Factura;Identif_Tercero;Nombre_Tercero;Funcionario_Digita;Punto_Dispensacion;Tipo_Documento;Id_Paciente;Nombre_Paciente;Regimen_Paciente;Departamento;Servicio;Tipo_Servicio;Cuota_Moderadora;Cuota_Recuperacion;Causal_No_Pago;Auditada?;Funcionario_Auditor;Estado_Dispensacion;Estado_Auditoria;Soportes\r\n";

$contenido = '';

$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;

	$contenido .= $disp["Codigo"] . ';';
	$contenido .= $disp["Fecha_Actual"] . ';';
	$contenido .= $disp["Numero_Factura"] . ';';
	$contenido .= $disp["Fecha_Factura"] . ';';
	$contenido .= $disp["Nit"] . ';';
	$contenido .= $disp["Nombre_Tercero"] . ';';
	$contenido .= $disp["Funcionario_Digita"] . ';';
	$contenido .= $disp["Punto_Dispensacion"] . ';';
	$contenido .= $disp["Tipo_Documento"] . ';';
	$contenido .= $disp["Id_Paciente"] . ';';
	$contenido .= $disp["Nombre_Paciente"] . ';';
	$contenido .= $disp["Regimen_Paciente"] . ';';
	$contenido .= $disp["Departamento"] . ';';
	$contenido .= $disp["Servicio"] . ';';
	$contenido .= $disp["Tipo_Servicio"] . ';';

	if ($disp['Id_Regimen'] == '1') {
		$contenido .=  $disp["Cuota_Moderadora"] . ';';
		
	} else {
		$contenido .= ' 0;';
	}
	if ($disp['Id_Regimen'] == '2') {
		$contenido .=  $disp["Cuota_Moderadora"] . ';';
		
	} else {
		$contenido .= ' 0;';
	}
	$contenido .= $disp["Causal_No_Pago"] . ';';
	$contenido .= $disp["Auditada"] . ';';
	$contenido .= $disp["Funcionario_Preauditoria"] . ';';
	$contenido .= $disp["Estado_Dispensacion"] . ";";
	$contenido .= $disp["Estado_Auditoria"].";";
	$contenido .= $disp["Soporte"] . "\r\n";

	
}

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