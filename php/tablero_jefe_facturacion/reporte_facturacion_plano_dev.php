<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$condicion = '';
$condicion_capita = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE DATE_FORMAT(F.Fecha_Documento, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
	$condicion_capita .= 'WHERE DATE_FORMAT(FC.Fecha_Documento, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}


if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	if ($condicion != "") {
		$condicion .= " AND F.Id_Funcionario=$_REQUEST[func]"; 
		$condicion_capita .= " AND F.Identificacion_Funcionario=$_REQUEST[func]";
	} else {
		$condicion .= "WHERE F.Id_Funcionario=$_REQUEST[func]";
		$condicion_capita .= "WHERE F.Identificacion_Funcionario=$_REQUEST[func]";
	}
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Tipo='$_REQUEST[tipo]'";
	} else {
		$condicion .= "WHERE D.Tipo='$_REQUEST[tipo]'";
	}
}

if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
	if ($condicion != "") {
		$condicion .= " AND F.Id_Cliente =$_REQUEST[cliente]";
		$condicion_capita .= " AND C.Id_Cliente =$_REQUEST[cliente]";
	} else {
		$condicion .= "WHERE C.Id_Cliente = $_REQUEST[cliente]";
		$condicion_capita .= "WHERE C.Id_Cliente = $_REQUEST[cliente]";
	}
}

if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
	$cond_estado = $_REQUEST['estado'] == "Activa" ? "(F.Estado_Factura = 'Sin Cancelar' OR F.Estado_Factura = 'Pagada')" : "F.Estado_Factura = 'Anulada'";
	$cond_estado2 = $_REQUEST['estado'] == "Activa" ? "(FC.Estado_Factura = 'Sin Cancelar' OR FC.Estado_Factura = 'Pagada')" : "FC.Estado_Factura = 'Anulada'";
	if ($condicion != "") {
		$condicion .= " AND $cond_estado";
		$condicion_capita .= " AND $cond_estado2";
	} else {
		$condicion .= "WHERE $cond_estado";
		$condicion_capita .= "WHERE $cond_estado2";
	}
}

$tipo_reporte="Productos";

if (isset($_REQUEST['tipo_reporte']) && $_REQUEST['tipo_reporte'] != "") {
	$tipo_reporte=$_REQUEST['tipo_reporte'];
}

header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte Facturacion.csv"'); 

if($tipo_reporte=='Productos'){
	$query ='(SELECT 
	F.Codigo as Factura, F.Tipo as Tipo_Factura,
	F.Fecha_Documento as Fecha_Factura, 
	CONCAT(FU.Nombres," ",FU.Apellidos) as Funcionario_Facturador, 
	D.Codigo as Dis, 
	F.Id_Cliente, 
	C.Nombre as Razon_Social, 
	P.Tipo_Documento, 
	P.Id_Paciente, 
	P.Primer_Apellido, P.Segundo_Apellido, P.Primer_Nombre, P.Segundo_Nombre, 
	P.Fecha_Nacimiento, TIMESTAMPDIFF(YEAR,P.Fecha_Nacimiento,CURDATE()) as Edad, 
	P.Genero, R.Nombre as Regimen, P.EPS, 
	P.Cod_Departamento, M.Codigo_Dane as Codigo_Municipio, M.Nombre as Nombre_Municipio, 
	PD.Numero_Autorizacion, PD.Fecha_Autorizacion, 
	PD.Numero_Prescripcion, D.Fecha_Formula, 
	D.Fecha_Actual as Fecha_Dispensacion, 
	D.CIE, D.Tipo, TS.Nombre as Tipo_Servicio, PR.Codigo_Cum AS Cum, 
	CONCAT_WS(" ", PR.Nombre_Comercial,"(",PR.Principio_Activo,PR.Concentracion,PR.Presentacion,PR.Embalaje) as Nombre_Producto,
	PF.Cantidad, PF.Precio,  PF.Subtotal, PF.Descuento, PF.Impuesto, F.Cuota, C.Tipo_Valor,
	(
		CASE
			WHEN C.Tipo_Valor = "Exacta" THEN ( ( ((PF.Precio * PF.Cantidad)+((PF.Precio * PF.Cantidad - IF(F.Id_Cliente = 890500890,FLOOR(PF.Descuento*PF.Cantidad), (PF.Descuento*PF.Cantidad)) ) * (PF.Impuesto/100) )) - (IF(F.Id_Cliente = 890500890, FLOOR(PF.Descuento* PF.Cantidad), PF.Descuento* PF.Cantidad)))  )
			ELSE ( ROUND(( ((ROUND(PF.Precio) * PF.Cantidad)+((ROUND(PF.Precio) * PF.Cantidad- ROUND((PF.Descuento*PF.Cantidad))) * (PF.Impuesto/100) )) - ROUND((PF.Descuento*PF.Cantidad)))) )
		END
	) AS Valor_Factura
	FROM Producto_Factura PF
	INNER JOIN Factura F
	ON F.Id_Factura = PF.Id_Factura
	INNER JOIN (SELECT Id_Dispensacion, Codigo, Numero_Documento, Tipo_Servicio, Fecha_Formula, Fecha_Actual, CIE, Tipo FROM Dispensacion WHERE Estado_Dispensacion != "Anulada" AND Tipo != "Capita" AND Estado_Facturacion = "Facturada") D
	ON D.Id_Dispensacion = F.Id_Dispensacion
	INNER JOIN Paciente P
	ON P.Id_Paciente = D.Numero_Documento
	LEFT JOIN Funcionario FU
	ON FU.Identificacion_Funcionario = F.Id_Funcionario
	INNER JOIN Cliente C
	ON C.Id_Cliente = F.Id_Cliente
	INNER JOIN Regimen R
	ON R.Id_Regimen = P.Id_Regimen
	LEFT JOIN Municipio M
	ON M.Id_Municipio = P.Codigo_Municipio
	LEFT JOIN Producto_Dispensacion PD
	ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
	INNER JOIN Producto PR
	ON PR.Id_Producto = PF.Id_Producto
	LEFT JOIN Tipo_Servicio TS
	ON TS.Id_Tipo_Servicio = D.Tipo_Servicio '.$condicion.' 
	ORDER BY F.Id_Factura ASC, PF.Subtotal DESC) 
	UNION ALL (SELECT
	FC.Codigo AS Factura,"Capita" as Tipo_Factura,
	FC.Fecha_Documento as Fecha_Factura, 
	CONCAT(F.Nombres," ",F.Apellidos) as Funcionario_Facturador,
	"" AS Dis,
	FC.Id_Cliente, 
	C.Nombre as Razon_Social, 
	"" AS Tipo_Documento, 
	"" AS Id_Paciente, 
	"" AS Primer_Apellido, "" AS Segundo_Apellido, "" AS Primer_Nombre, "" AS Segundo_Nombre, 
	"" AS Fecha_Nacimiento, "" as Edad, 
	"" AS Genero, (SELECT Nombre FROM Regimen WHERE Id_Regimen = FC.Id_Regimen) as Regimen, "" AS EPS,
	(SELECT Codigo FROM Departamento WHERE Id_Departamento = FC.Id_Departamento) AS Cod_Departamento,
	"" as Codigo_Municipio, "" as Nombre_Municipio, "" AS Numero_Autorizacion, "" AS Fecha_Autorizacion, 
	"" AS Numero_Prescripcion, "" AS Fecha_Formula,
	"" as Fecha_Dispensacion, 
	"" AS CIE, "Capita" AS Tipo, "Capita" as Tipo_Servicio, "" AS Cum,
	"" AS Nombre_Producto,
	DFC.Cantidad, DFC.Precio,  DFC.Total AS Subtotal, 0 AS Descuento, 0 AS Impuesto, FC.Cuota_Moderadora AS Cuota, C.Tipo_Valor,DFC.Total as  Valor_Factura
	FROM
	Descripcion_Factura_Capita DFC
	INNER JOIN Factura_Capita FC ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
	INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente
	INNER JOIN Funcionario F ON FC.Identificacion_Funcionario = F.Identificacion_Funcionario '.$condicion_capita.' ORDER BY FC.Id_Factura_Capita, DFC.Total DESC)';


	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($query);
	$productos= $oCon->getData();
	unset($oCon);

	$contenido = '';

	$contenido .= "N Factura;";
	$contenido .= "Fecha Factura;";
	$contenido .= "Facturador;";
	$contenido .= "N Dispensacion;";
	$contenido .= "Nit Cliente;";
	$contenido .= "Razon Social;";
	$contenido .= "Tipo ID Paciente;";
	$contenido .= "Id Paciente;";
	$contenido .= "Primer Apellido;";
	$contenido .= "Segundo Apellido;";
	$contenido .= "Primer Nombre;";
	$contenido .= "Segundo Nombre;";
	$contenido .= "Fecha Nacimiento;";
	$contenido .= "Edad Paciente;";
	$contenido .= "Genero Paciente;";
	$contenido .= "Regimen Paciente;";
	$contenido .= "EPS Paciente;";
	$contenido .= "Cod Departamento;";
	$contenido .= "Cod Ciudad;";
	$contenido .= "Nom Ciudad;";
	$contenido .= "N Autorizacion;";
	$contenido .= "F Autorizacion;";
	$contenido .= "N Prescripcion;";
	$contenido .= "Fecha Formula;";
	$contenido .= "Fecha Dispensacion;";
	$contenido .= "Codigo DX;";
	$contenido .= "Tipo Servicio 1;";
	$contenido .= "Tipo Servicio 2;";
	$contenido .= "Codigo Cum;";
	$contenido .= "Nombre Producto;";
	$contenido .= "Cantidad;";
	$contenido .= "Precio Unitario;";
	$contenido .= "Subtotal;";
	$contenido .= "Descuento;";
	$contenido .= "Iva;";
	$contenido .= "Cuota_Recuperacion;";
	$contenido .= "Total_Neto_Facturado;";
	$contenido .= "Valor_Factura;";
	$contenido .= "Tipo;\r\n";


	$j=1;
	$cod='';

	foreach($productos as $disp){ $j++;

		$decimales = 2;

		if ($disp['Tipo_Valor'] == 'Cerrada') {
			$decimales = 0;
		}

		$cuota = 0;
		
		if($cod!=$disp["Factura"]){
			$cod=$disp["Factura"];
			
			$rec = $disp["Cuota"] - $disp["Subtotal"];
			
			if($rec<=0){
				$cuota=$disp["Cuota"];
			}else{
				$cuota = $disp["Subtotal"];
			}
			$fin=$disp["Cuota"]-$cuota;
		}else{

			$rec = $fin - $disp["Subtotal"];
			
			if($rec<=0){
				$cuota=$fin;
			}else{
				$cuota = $disp["Subtotal"];
			}
			$fin=$fin-$cuota;
		}
		$precio = number_format($disp["Precio"],$decimales,".","");
		$total_descuento = $disp["Cantidad"]*$disp["Descuento"];
		$subtotal = $disp['Cantidad']*$precio;
		$iva = ($subtotal-$total_descuento)*($disp["Impuesto"]/100);
		$final = $subtotal-$total_descuento-$cuota + $iva;
		$contenido .= $disp["Factura"] .";";
		$contenido .= fecha($disp["Fecha_Factura"]) .";";
		$contenido .= $disp["Funcionario_Facturador"] .";";
		$contenido .= $disp["Dis"] .";";
		$contenido .= $disp["Id_Cliente"] .";";
		$contenido .= $disp["Razon_Social"] .";";
		$contenido .= $disp["Tipo_Documento"] .";";
		$contenido .= $disp["Id_Paciente"] .";";
		$contenido .= $disp["Primer_Apellido"] .";";
		$contenido .= $disp["Segundo_Apellido"] .";";
		$contenido .= $disp["Primer_Nombre"] .";";
		$contenido .= $disp["Segundo_Nombre"] .";";
		$contenido .= $disp["Fecha_Nacimiento"] .";";
		$contenido .= $disp["Edad"] .";";
		$contenido .= $disp["Genero"] .";";
		$contenido .= $disp["Regimen"] .";";
		$contenido .= $disp["EPS"] .";";
		$contenido .= $disp["Cod_Departamento"] .";";
		$contenido .= $disp["Codigo_Municipio"] .";";
		$contenido .= $disp["Nombre_Municipio"] .";";
		$contenido .= $disp["Numero_Autorizacion"] .";";
		$contenido .= $disp["Fecha_Autorizacion"] .";";
		$contenido .= $disp["Numero_Prescipcion"] .";";
		$contenido .= $disp["Fecha_Formula"] .";";
		$contenido .= fecha($disp["Fecha_Dispensacion"]) .";";
		$contenido .= $disp["CIE"] .";";
		$contenido .= $disp["Tipo"] .";";
		$contenido .= $disp["Tipo_Servicio"] .";";
		$contenido .= $disp["Cum"] .";"; 
		$contenido .= $disp["Nombre_Producto"] .";";
		$contenido .= $disp["Cantidad"] .";";
		$contenido .= $precio .";";
		$contenido .= number_format(($disp['Cantidad']*$precio),$decimales,",","") .";";
		$decimales_dcto = $decimales;
		if ($disp["Id_Cliente"] == 890500890) { // SI ES NORTE DE SANTANDER
			$decimales_dcto = 0;
		}
		$contenido .= number_format(($disp["Descuento"]*$disp["Cantidad"]),$decimales_dcto,",","") . ";";
		$contenido .= number_format($iva,$decimales,",","") . ";";
		$contenido .= $cuota . ";";
		$contenido .= number_format($final,$decimales,",","") . ";";
		$contenido .= number_format($disp['Valor_Factura'],$decimales,",","") . ";";
		$contenido .= $disp["Tipo_Factura"] . ";\r\n";
		
	}
}else{

	$query='SELECT
		F.Codigo AS Factura,
		F.Fecha_Documento AS Fecha_Factura,
		F.Id_Cliente ,
		C.Nombre AS Razon_Social,	
		
	
		(IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
		FROM Producto_Factura PF
		WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) as Descuentos,

		F.Cuota AS Cuota_Moderadora,

		 IFNULL((
                CASE
                    WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+((Precio * Cantidad - IF(F.Id_Cliente = 890500890,FLOOR(Descuento*Cantidad), (Descuento*Cantidad)) ) * (Impuesto/100) )) - (IF(F.Id_Cliente = 890500890, FLOOR(Descuento* Cantidad), Descuento* Cantidad))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
                    ELSE (SELECT ROUND(SUM( ((ROUND(Precio) * Cantidad)+((ROUND(Precio) * Cantidad- ROUND((Descuento*Cantidad))) * (Impuesto/100) )) - ROUND((Descuento*Cantidad)))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
                END
            ),0) AS Neto_Factura,	
		
		F.Estado_Factura AS Estado,
		IF(D.Tipo = "Evento", D.Tipo, D.Tipo_Servicio) AS Tipo_Servicio, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=F.Id_Funcionario)  as Funcionario_Facturador,
		P.Tipo_Documento, 
		P.Id_Paciente, 
		P.Primer_Apellido, P.Segundo_Apellido, P.Primer_Nombre, P.Segundo_Nombre, 
		P.Fecha_Nacimiento, TIMESTAMPDIFF(YEAR,P.Fecha_Nacimiento,CURDATE()) as Edad, 
		P.Genero, (SELECT Nombre FROM Regimen WHERE Id_Regimen=P.Id_Regimen) as Regimen, P.EPS, D.Fecha_Formula, D.Fecha_Actual,D.Tipo,D.CIE, D.Codigo as Dis

		FROM Factura F
		INNER JOIN (SELECT Id_Dispensacion, Codigo, Tipo, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = T.Tipo_Servicio) AS Tipo_Servicio, Numero_Documento, Fecha_Formula, DATE(Fecha_Actual) as Fecha_Actual, CIE FROM Dispensacion T WHERE Tipo != "Capita" AND Estado_Facturacion = "Facturada") D ON D.Id_Dispensacion = F.Id_Dispensacion
		INNER JOIN Paciente P ON D.Numero_Documento=P.Id_Paciente
		INNER JOIN Cliente C
		ON C.Id_Cliente = F.Id_Cliente
		'.$condicion.'	
	
	UNION ALL
	(SELECT
	FC.Codigo AS Factura,
	FC.Fecha_Documento as Fecha_Factura, 
	FC.Id_Cliente, 
	C.Nombre as Razon_Social,
	 0 AS Descuentos, FC.Cuota_Moderadora, ((DFC.Cantidad * DFC.Precio)-FC.Cuota_Moderadora) AS Neto_Factura,	
	FC.Estado_Factura AS Estado,
	"Capita" AS Tipo_Servicio, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=FC.Identificacion_Funcionario)  as Funcionario_Facturador,
	"" as Tipo_Documento, 
	"" as Id_Paciente, 
	"" as Primer_Apellido, "" as Segundo_Apellido, "" as Primer_Nombre, "" as Segundo_Nombre, 
	"" as Fecha_Nacimiento, "" as Edad, 
	"" as Genero, "" as Regimen, "" as EPS, "" as Fecha_Formula, "" as Fecha_Actual, "Capita" as Tipo, "" as CIE, "" as Dis

	FROM
	Descripcion_Factura_Capita DFC
	INNER JOIN Factura_Capita FC ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
	INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente
	'.$condicion_capita.'
	)
	
	ORDER BY `Fecha_Factura`  ASC';


	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($query);
	$productos= $oCon->getData();
	unset($oCon);

	$contenido = '';

	$contenido .= "N Factura;";
	$contenido .= "Fecha Factura;";
	$contenido .= "Facturador;";
	$contenido .= "Dispensacion;";
	$contenido .= "Nit Cliente;";
	$contenido .= "Razon Social;";
	$contenido .= "Tipo Documento;";
	$contenido .= "Id Paciente;";
	$contenido .= "Primer Apellido;";
	$contenido .= "Segundo Apellido;";
	$contenido .= "Primer Nombre;";
	$contenido .= "Segundo Nombre;";
	$contenido .= "Fecha Nacimiento;";
	$contenido .= "Edad Paciente;";
	$contenido .= "Genero Paciente;";
	$contenido .= "Regimen Paciente;";
	$contenido .= "EPS Paciente;";
	$contenido .= "Fecha Formula;";
	$contenido .= "Fecha Dispensacion;";
	$contenido .= "Codigo DX;";
	$contenido .= "Tipo Servicio 1;";
	$contenido .= "Tipo Servicio 2;";	
	$contenido .= "Descuento;";
	$contenido .= "Cuota_Recuperacion;";
	$contenido .= "Total_Factura;\r\n";


	foreach($productos as $disp){ $j++;		
		$decimales = 2;

		if ($disp['Tipo_Valor'] == 'Cerrada') {
			$decimales = 0;
		} 
		$contenido .= $disp["Factura"] .";";
		$contenido .= fecha($disp["Fecha_Factura"]) .";";
		$contenido .= $disp["Funcionario_Facturador"] .";";
		$contenido .= $disp["Dis"] .";";
		$contenido .= $disp["Id_Cliente"] .";";
		$contenido .= $disp["Razon_Social"] .";";
		$contenido .= $disp["Tipo_Documento"] .";";
		$contenido .= $disp["Id_Paciente"] .";";
		$contenido .= $disp["Primer_Apellido"] .";";
		$contenido .= $disp["Segundo_Apellido"] .";";
		$contenido .= $disp["Primer_Nombre"] .";";
		$contenido .= $disp["Segundo_Nombre"] .";";
		$contenido .= $disp["Fecha_Nacimiento"] .";";
		$contenido .= $disp["Edad"] .";";
		$contenido .= $disp["Genero"] .";";
		$contenido .= $disp["Regimen"] .";";
		$contenido .= $disp["EPS"] .";";		
		$contenido .= $disp["Fecha_Formula"] .";";
		$contenido .= $disp["Fecha_Actual"] .";";
		$contenido .= $disp["CIE"] .";";
		$contenido .= $disp["Tipo"] .";";
		$contenido .= $disp["Tipo_Servicio"] .";";		
		$decimales_dcto = $decimales;
		if ($disp["Id_Cliente"] == 890500890) { // SI ES NORTE DE SANTANDER
			$decimales_dcto = 0;
		}
		$contenido .= number_format(($disp["Descuentos"]),$decimales_dcto,",","") . ";";
		
		$contenido .= number_format($disp["Cuota_Moderadora"],$decimales,",","") . ";";
		$contenido .= number_format($disp['Neto_Factura'],$decimales,",","") . "";
		$contenido .= ";\r\n";
		
	}
	
}


echo $contenido;

function fecha($fecha) {
	return date('d/m/Y', strtotime($fecha));
}

?>
