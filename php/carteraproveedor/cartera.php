<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['prov']) && $_REQUEST['prov']) {
    $condicion .= " HAVING Nombre LIKE '%$_REQUEST[prov]%'";
}

### LAS FACTURAS QUE SE REALIZARON EN SIGESPRO, SOLO DEBEN APARECER LAS QUE SE HICIERON DESDE EL AÑO 2019, LAS DEL AÑO 2018 SE CONTABILIZARON EN MANTIS.

$query = "SELECT
    r.Nombre_Proveedor AS Nombre,
COUNT(r.Nit) AS Cantidad
FROM
(
    SELECT 
    IF(CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido) != '',
	CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido),
	C.Razon_Social) AS Nombre_Proveedor,

    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Total_Compra,
	(CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END) AS Neto_Factura,
MC.Nit
FROM
Movimiento_Contable MC
	INNER JOIN
Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
	INNER JOIN
Proveedor C ON C.Id_Proveedor = MC.Nit
WHERE
MC.Estado != 'Anulado'
	AND Id_Plan_Cuenta = 272
GROUP BY MC.Nit
HAVING Neto_Factura != 0
) r $condicion";

$oCon= new consulta();
$oCon->setQuery($query);
// $oCon->setTipo('Multiple');
$total = $oCon->getData();
unset($oCon);

// echo $query; exit;

// $datos['query']=$query;
####### PAGINACIÓN ######## 
$tamPag = 30; 
$numReg = $total['Cantidad']; 
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


$query = "SELECT
r.Nit AS Id_Proveedor,
r.Nombre_Proveedor AS Nombre,
MAX(r.Dias_Mora) AS Dias_Mora,
SUM(r.Neto_Factura) AS TOTAL
FROM
(
    SELECT 
DATE(MC.Fecha_Movimiento) AS Fecha_Factura,
MC.Documento AS Factura,
(SELECT Archivo_Factura FROM Factura_Acta_Recepcion WHERE Factura = MC.Documento LIMIT 1) AS Archivo_Factura,
(SELECT IFNULL(SUM(PAR.Precio*PAR.Cantidad),0) FROM Producto_Acta_Recepcion PAR WHERE PAR.Impuesto = 0 AND PAR.Factura = MC.Documento) AS Exenta,
(SELECT IFNULL(SUM(PAR.Precio*PAR.Cantidad),0) FROM Producto_Acta_Recepcion PAR WHERE PAR.Impuesto != 0 AND PAR.Factura = MC.Documento) AS Gravada,
(SELECT IFNULL(SUM(PAR.Precio*PAR.Cantidad*(Impuesto/100)),0) FROM Producto_Acta_Recepcion PAR WHERE PAR.Impuesto != 0 AND PAR.Factura = MC.Documento) AS Iva,
(CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Total_Compra,
(CASE PC.Naturaleza
	WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
	ELSE (SUM(MC.Debe) - SUM(MC.Haber))
END) AS Neto_Factura,
PC.Naturaleza AS Nat,
MC.Nit,
IF(CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido) != '',
	CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido),
	C.Razon_Social) AS Nombre_Proveedor,
DATE_ADD(DATE(MC.Fecha_Movimiento), INTERVAL IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) DAY) AS Fecha_Vencimiento,
IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) AS Condicion_Pago,

-- IF(C.Condicion_Pago > 1, 
	IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago, 
		DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago, 
--	0),
	0) AS Dias_Mora
	
FROM
Movimiento_Contable MC
	INNER JOIN
Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
	INNER JOIN
Proveedor C ON C.Id_Proveedor = MC.Nit
WHERE
MC.Estado != 'Anulado'
	AND Id_Plan_Cuenta = 272
GROUP BY MC.Id_Plan_Cuenta , MC.Documento, MC.Nit
HAVING Neto_Factura != 0
) r GROUP BY r.Nit $condicion ORDER BY TOTAL DESC LIMIT $limit,$tamPag";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$datos['Lista']=$resultado;
$datos['numReg'] = $numReg;

echo json_encode($datos);


?>