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

$query = "
          SELECT 
               D.Codigo,
               D.Fecha_Actual,
               D.Id_Punto_Dispensacion,
               D.Id_Servicio,
               D.Id_Tipo_Servicio,
               D.Numero_Documento,
               CONCAT_WS(' ',
                         PC.Primer_Nombre,
                         PC.Segundo_Nombre,
                         PC.Primer_Apellido,
                         PC.Segundo_Apellido) AS Paciente,
               (SELECT Numero_Telefono FROM Paciente_Telefono WHERE Id_Paciente = D.Numero_Documento LIMIT 1) AS Numero_Telefono,
               PC.Direccion,
               (SELECT Nombre FROM Regimen WHERE Id_Regimen = PC.Id_Regimen) AS Regimen,
               D.CIE,
               PC.Nit AS Cliente,
               D.Estado_Facturacion,
               D.Estado_Dispensacion,
               D.Estado_Auditoria,
               D.Id_Factura,
               D.Pendientes  
                FROM Dispensacion D
                         STRAIGHT_JOIN Paciente PC
                         on D.Numero_Documento=PC.Id_Paciente
                         STRAIGHT_JOIN Punto_Dispensacion P
                         on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
                         STRAIGHT_JOIN Departamento L 
                         ON P.Departamento = L.Id_Departamento
               WHERE
                    DATE(D.Fecha_Actual) >= '$ultimos_30_dias' 
                    AND D.Estado <> 'Anulada'";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

echo "Codigo;Fecha_Actual;Id_Punto_Dispensacion;Id_Servicio;Id_Tipo_Servicio;Numero_Documento;Paciente;Numero_Telefono;Direccion;Regimen;CIE;Cliente;Estado_Facturacion;Estado_Dispensacion;Estado_Auditoria;Id_Factura;Pendientes\r\n";

$contenido = '';

$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;

	$contenido .= $disp["Codigo"] . ';';
	$contenido .= $disp["Fecha_Actual"] . ';';
	$contenido .= $disp["Id_Punto_Dispensacion"] . ';';
	$contenido .= $disp["Id_Servicio"] . ';';
	$contenido .= $disp["Id_Tipo_Servicio"] . ';';
	$contenido .= $disp["Numero_Documento"] . ';';
	$contenido .= $disp["Paciente"] . ';';
	$contenido .= $disp["Numero_Telefono"] . ';';
	$contenido .= $disp["Direccion"] . ';';
	$contenido .= $disp["Regimen"] . ';';
	$contenido .= $disp["CIE"] . ';';
	$contenido .= $disp["Cliente"] . ';';
	$contenido .= $disp["Estado_Facturacion"] . ';';
	$contenido .= $disp["Estado_Dispensacion"] . ';';
	$contenido .= $disp["Estado_Auditoria"] . ';';
	$contenido .= $disp["Id_Factura"] . ';';
	$contenido .= $disp["Pendientes"] . "\r\n";

	
}

echo $contenido;
?>