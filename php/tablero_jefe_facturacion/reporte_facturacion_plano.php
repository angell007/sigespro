<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once '../../class/class.querybasedatos.php';
$queryObj = new QueryBaseDatos();

date_default_timezone_set("America/Bogota");
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

$condicion = ' ';
$condicion_capita = '';
$incluir_capita = true;

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'AND DATE_FORMAT(F.Fecha_Documento, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
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
	$tipo = $_REQUEST['tipo'];
	if (strcasecmp($tipo, 'Capita') === 0) {
		// When tipo is Capita, only include the Capita block.
		if ($condicion != "") {
			$condicion .= " AND 1=0";
		} else {
			$condicion = "WHERE 1=0";
		}
	} else {
		$incluir_capita = false;
		if ($condicion != "") {
			$condicion .= " AND D.Id_Tipo_Servicio=$tipo";
		} else {
			$condicion .= "WHERE D.Id_Tipo_Servicio=$tipo";
		}
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
	$cond_estado = $_REQUEST['estado'] == "Activa" ? "(F.Estado_Factura = 'Sin Cancelar' OR F.Estado_Factura = 'Pagada')" : "F.Nota_Credito ='Si'";
	$cond_estado2 = $_REQUEST['estado'] == "Activa" ? "(FC.Estado_Factura = 'Sin Cancelar' OR FC.Estado_Factura = 'Pagada')" : "FC.Nota_Credito = 'Si'";
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


if($tipo_reporte=='Productos'){
	$query ="SELECT 
	F.Id_Factura,
	F.Codigo as Factura, F.Tipo as Tipo_Factura,
	F.Fecha_Documento as Fecha_Factura, 
	CONCAT(FU.Nombres,' ',FU.Apellidos) as Funcionario_Facturador, 
	IF( RD.Codigo IS NOT NULL , RD.Codigo, '' ) AS Radicado,
    IF( RD.Codigo IS NOT NULL , RD.Fecha_Radicado, '' ) AS Fecha_Radicado,
	D.Codigo as Dis, 
	D.Departamento,
	D.Estado_Dispensacion,
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
	F.Estado_Factura AS Estado,
	F.Nota_Credito,
	D.CIE, (SELECT Nombre FROM Servicio WHERE Id_Servicio=D.Id_Servicio) as Tipo, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio=D.Id_Tipo_Servicio) as Tipo_Servicio, PR.Codigo_Cum AS Cum, 
	CONCAT_WS(' ', PR.Nombre_Comercial,'(',PR.Principio_Activo,PR.Concentracion,PR.Presentacion,PR.Embalaje) as Nombre_Producto,
	IFNULL(PD.Lote, '') AS Lote_Producto,
	IFNULL(PD.Costo, '') AS Costo_Producto,
	PF.Cantidad, PF.Precio,  PF.Subtotal, PF.Descuento, PF.Impuesto, F.Cuota, C.Tipo_Valor
	FROM Producto_Factura PF
	INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
	INNER JOIN (
				SELECT D.Id_Dispensacion,
					D.Codigo, D.Numero_Documento,
					D.Id_Tipo_Servicio, D.Fecha_Formula,
					D.Fecha_Actual, D.CIE, D.Id_Servicio,
					D.Estado_Dispensacion,
					DP.Nombre AS Departamento
				FROM Dispensacion D 
				LEFT JOIN Punto_Dispensacion P ON P.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
				LEFT JOIN Departamento DP ON P.Departamento= DP.Id_Departamento
				WHERE D.Id_Tipo_Servicio != 7 
			) D ON D.Id_Dispensacion = F.Id_Dispensacion
	INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento
	LEFT JOIN Funcionario FU ON FU.Identificacion_Funcionario = F.Id_Funcionario
	INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
	INNER JOIN Regimen R ON R.Id_Regimen = P.Id_Regimen
	LEFT JOIN Municipio M ON M.Id_Municipio = P.Codigo_Municipio
	LEFT JOIN Radicado_Factura RF ON RF.Id_Factura = F.Id_Factura AND RF.Estado_Factura_Radicacion = 'Radicada'
   LEFT JOIN Radicado RD ON RF.Id_Radicado = RD.Id_Radicado AND RD.Estado = 'Radicada'
	LEFT JOIN Producto_Dispensacion PD ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
	INNER JOIN Producto PR
	ON PR.Id_Producto = PF.Id_Producto
	Where True
	#
	$condicion
	#

	
	UNION ALL (SELECT
	FC.Id_Factura_Capita as Id_Factura,
	FC.Codigo AS Factura,'Capita' as Tipo_Factura,
	FC.Fecha_Documento as Fecha_Factura, 
	CONCAT(F.Nombres,' ',F.Apellidos) as Funcionario_Facturador,
	'' AS Radicado,
    ''  AS Fecha_Radicado,
	'' AS Dis,
	'' AS Departamento,
	'' AS Estado_Dispensacion,
	FC.Id_Cliente, 
	FC.Estado_Factura AS Estado,
	C.Nombre as Razon_Social, 
	'' AS Tipo_Documento, 
	'' AS Id_Paciente, 
	'' AS Primer_Apellido, '' AS Segundo_Apellido, '' AS Primer_Nombre, '' AS Segundo_Nombre, 
	'' AS Fecha_Nacimiento, '' as Edad, 
	'' AS Genero, (SELECT Nombre FROM Regimen WHERE Id_Regimen = FC.Id_Regimen) as Regimen, '' AS EPS,
	(SELECT Codigo FROM Departamento WHERE Id_Departamento = FC.Id_Departamento) AS Cod_Departamento,
	'' as Codigo_Municipio, '' as Nombre_Municipio, '' AS Numero_Autorizacion, '' AS Fecha_Autorizacion, 
	'' AS Numero_Prescripcion, '' AS Fecha_Formula,
	'' as Fecha_Dispensacion, 
	'' AS CIE, 'Capita' AS Tipo, 'Capita' as Tipo_Servicio, '' AS Cum,
	'' AS Nombre_Producto,
	'' AS Lote_Producto,
	'' AS Costo_Producto,
	FC.Nota_Credito,
	DFC.Cantidad, DFC.Precio,  DFC.Total AS Subtotal, 0 AS Descuento, 0 AS Impuesto, FC.Cuota_Moderadora AS Cuota, C.Tipo_Valor
	FROM
	Descripcion_Factura_Capita DFC
	INNER JOIN Factura_Capita FC ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
	INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente
	INNER JOIN Funcionario F ON FC.Identificacion_Funcionario = F.Identificacion_Funcionario 
	#
	$condicion_capita
	#
	)
	ORDER BY Id_Factura ASC, Subtotal DESC
	";
	
	// echo $query; exit;
	// $oCon= new consulta();
	$queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
	// $productos= $oCon->getData();
	// unset($oCon);
	
	$contenido = '';
	
	$contenido .= "N Factura;";
	$contenido .= "Fecha Factura;";
	$contenido .= "Facturador;";
	$contenido .= "N Dispensacion;";
	$contenido .= "Estado Dispensacion;";
	$contenido .= "Departamento;";
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
	$contenido .= "Lote;";
	$contenido .= "Costo;";
	$contenido .= "Cantidad;";
	$contenido .= "Precio Unitario;";
	$contenido .= "Subtotal;";
	$contenido .= "Descuento;";
	$contenido .= "Iva;";
	$contenido .= "Cuota_Recuperacion;";
	$contenido .= "Total_Neto_Facturado;";
	$contenido .= "Radicado;";
	$contenido .= "Fecha Radicado;";
	$contenido .= "Estado Pago;";
	$contenido .= "Tipo;";
	$contenido .= "Nota Credito;\r\n";
	
	
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
		$contenido .= $disp["Estado_Dispensacion"] .";";
		$contenido .= $disp["Departamento"] .";";
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
		$contenido .= "\"$disp[Nombre_Producto] \";";
		$contenido .= $disp["Lote_Producto"] .";";
		$costo = $disp["Costo_Producto"] !== '' ? number_format($disp["Costo_Producto"], $decimales, ".", "") : '';
		$contenido .= $costo .";";
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
		$contenido .= $disp["Radicado"] . ";";
		$contenido .= $disp["Fecha_Radicado"] . ";";
		$contenido .= $disp["Estado"] . ";";
		$contenido .= $disp["Tipo_Factura"] . ";";
		$contenido .= $disp["Nota_Credito"] . ";\r\n";
		
	}
	
}else{

	$query="SELECT 
			F.Codigo AS Factura,
			IFNULL(RD.Codigo, '' ) AS Radicado,
			IFNULL(RD.Fecha_Radicado, '' ) AS Fecha_Radicado,
			F.Fecha_Documento AS Fecha_Factura,
			F.Id_Cliente,
			C.Nombre AS Razon_Social,
			(IFNULL( if(PF.Impuesto = 0, SUM(PF.Cantidad * PF.Descuento), 0),0 ) ) AS Descuentos,
			F.Cuota AS Cuota_Moderadora,
			IFNULL(
				(CASE 
					WHEN C.Tipo_Valor = 'Exacta' THEN(
						 SUM((
							(PF.Precio * PF.Cantidad) +
							(( PF.Precio * PF.Cantidad - IF(F.Id_Cliente = 890500890, FLOOR(PF.Descuento * PF.Cantidad), (PF.Descuento * PF.Cantidad))) *(PF.Impuesto / 100))) - 
							(IF(F.Id_Cliente = 890500890, FLOOR(PF.Descuento * PF.Cantidad),PF.Descuento * PF.Cantidad))
						) - F.Cuota
						
					) ELSE(
						ROUND( SUM((
									(ROUND(PF.Precio) * PF.Cantidad) + 
								((ROUND(PF.Precio) * PF.Cantidad - ROUND((PF.Descuento * PF.Cantidad))) *(PF.Impuesto / 100))) - 
									ROUND((PF.Descuento * PF.Cantidad))
								)) - F.Cuota
						)
					END),
			0) AS Neto_Factura,
			
			F.Estado_Factura AS Estado,
			D.Tipo_Servicio,
			CONCAT_WS( ' ',FN.Nombres, FN.Apellidos) AS Funcionario_Facturador,
			P.Tipo_Documento,
			P.Id_Paciente,
			P.Primer_Apellido,
			P.Segundo_Apellido,
			P.Primer_Nombre,
			P.Segundo_Nombre,
			P.Fecha_Nacimiento,
			TIMESTAMPDIFF( YEAR, P.Fecha_Nacimiento, CURDATE()) AS Edad,
			P.Genero,            
			(RG.Nombre ) AS Regimen,
			P.EPS,
			D.Fecha_Formula,
			D.Fecha_Actual,
			D.Tipo,
			D.CIE,
			D.Codigo AS Dis, 
			DP.Nombre AS Departamento
	
	 		FROM Factura F
			 LEFT JOIN Funcionario FN ON FN.Identificacion_Funcionario = F.Id_Funcionario
			 INNER JOIN Producto_Factura PF ON PF.Id_Factura = F.Id_Factura
			 INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente		
			LEFT JOIN Radicado_Factura RF ON RF.Id_Factura = F.Id_Factura AND RF.Estado_Factura_Radicacion = 'Radicada'
			LEFT JOIN Radicado RD ON RF.Id_Radicado = RD.Id_Radicado AND RD.Estado = 'Radicada'
				INNER JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion
				INNER JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio
				INNER JOIN Servicio S ON S.Id_Servicio=D.Id_Servicio
				left JOIN Punto_Dispensacion PT ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
				left JOIN Departamento DP ON PT.Departamento= DP.Id_Departamento 
				left JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente
				LEFT JOIN Regimen RG ON RG.Id_Regimen = P.Id_Regimen
			
				WHERE D.Id_Tipo_Servicio != 7 AND D.Estado_Facturacion = 'Facturada' 
				#
				$condicion
				#
				GROUP BY F.Id_Factura";
	if ($incluir_capita) {
		$query .= "
		UNION ALL
			(
			SELECT
			'' AS Radicado,
			''  AS Fecha_Radicado,
			FC.Codigo AS Factura,
			FC.Fecha_Documento AS Fecha_Factura,
			FC.Id_Cliente,
			C.Nombre AS Razon_Social,
			0 AS Descuentos,
			FC.Cuota_Moderadora,
			(
			(DFC.Cantidad * DFC.Precio) - FC.Cuota_Moderadora
			) AS Neto_Factura,
			FC.Estado_Factura AS Estado,
			'Capita' AS Tipo_Servicio,
			
			(
			SELECT
			CONCAT(Nombres, ' ', Apellidos)
			FROM
			Funcionario
			WHERE
			Identificacion_Funcionario = FC.Identificacion_Funcionario
			) AS Funcionario_Facturador,
			'' AS Tipo_Documento,
			'' AS Id_Paciente,
			'' AS Primer_Apellido,
			'' AS Segundo_Apellido,
			'' AS Primer_Nombre,
			'' AS Segundo_Nombre,
			'' AS Fecha_Nacimiento,
			'' AS Edad,
			'' AS Genero,
			'' AS Regimen,
			'' AS EPS,
			'' AS Fecha_Formula,
			'' AS Fecha_Actual,
			'Capita' AS Tipo,
			'' AS CIE,
			'' AS Dis, 
			'' AS Departamento
			FROM
			Descripcion_Factura_Capita DFC
			INNER JOIN Factura_Capita FC ON
			DFC.Id_Factura_Capita = FC.Id_Factura_Capita
			INNER JOIN Cliente C ON
			C.Id_Cliente = FC.Id_Cliente
			#
			$condicion_capita
			#
		)";
	}
	$query .= "	
	ORDER BY Fecha_Factura  ASC";
	// Continues to CSV output (no debug JSON).
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
	$contenido .= "Departamento;";
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
	$contenido .= "Radicado;";
	$contenido .= "Fecha Radicado;";
	$contenido .= "Estado Pago;";
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
		$contenido .= "$disp[Departamento];";
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
		$contenido .= $disp["Radicado"] .";";		
		$contenido .= $disp["Fecha_Radicado"] .";";		
		$contenido .= $disp["Estado"] .";";	
		$contenido .= number_format($disp['Neto_Factura'],$decimales,",","") . ";";
		$contenido .= "\r\n";
		
	}
	
}

header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte Facturacion.csv"'); 

echo $contenido;

function fecha($fecha) {
	return date('d/m/Y', strtotime($fecha));
}

?>
