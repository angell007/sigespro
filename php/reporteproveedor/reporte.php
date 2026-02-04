<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$inicio = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fin = ( isset( $_REQUEST['fin'] ) ? $_REQUEST['fin'] : '' );
$proveedor = ( isset( $_REQUEST['proveedor'] ) ? $_REQUEST['proveedor'] : false );

$condicion='';

if($proveedor){
    $condicion=' AND OCN.Id_Proveedor='.$proveedor;
}

if(isset($_REQUEST['bodega']) && $_REQUEST['bodega'] != ''){
	$condicion .= ' AND B.Nombre LIKE "%'.$_REQUEST["bodega"].'%"';
}

if(isset($_REQUEST['orden_compra']) && $_REQUEST['orden_compra'] != ''){
	$condicion .= ' AND OCN.Codigo LIKE "%'.$_REQUEST["orden_compra"].'%"';
}

/*if(isset($_REQUEST['factura']) && $_REQUEST['factura'] != ''){
	$condicion .= ' AND F.Factura LIKE "%'.$_REQUEST["factura"].'%"';
}*/

if(isset($_REQUEST['acta_recepcion']) && $_REQUEST['acta_recepcion'] != ''){
	$condicion .= ' AND AR.Codigo LIKE "%'.$_REQUEST["acta_recepcion"].'%"';
}

$query = 'SELECT COUNT(*) AS Total 
FROM ((SELECT (SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ") 
FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura,
	IFNULL(B.Nombre,BN.Nombre) as Nombre_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor,
(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra,
AR.Codigo as Codigo_Acta, OCN.Fecha
FROM Orden_Compra_Nacional OCN 
LEFT JOIN Acta_Recepcion AR
ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional

LEFT JOIN Bodega B
ON AR.Id_Bodega=B.Id_Bodega

LEFT JOIN Bodega_Nuevo BN
ON BN.Id_Bodega_Nuevo = AR.Id_Bodega_Nuevo

WHERE OCN.Estado!="Anulada" AND DATE_FORMAT(AR.Fecha_Creacion, "%Y-%m-%d") BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.') UNION (SELECT (SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ") FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura, PT.Nombre as Nombre_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor, (SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra, AR.Codigo as Codigo_Acta, OCN.Fecha
FROM Orden_Compra_Nacional OCN 
LEFT JOIN Acta_Recepcion AR
ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional
INNER JOIN Punto_Dispensacion PT
ON AR.Id_Punto_Dispensacion=PT.Id_Punto_Dispensacion
WHERE OCN.Estado!="Anulada" AND DATE_FORMAT(AR.Fecha_Creacion, "%Y-%m-%d") BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.') ORDER BY Codigo_Acta ASC ) as r';


$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $total['Total']; 
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

$resultado['numReg'] = $numReg;

/*$query = '(SELECT  
	(SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ") FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura, 
	B.Nombre as Nombre_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor, 
	(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra, 
	AR.Codigo as Codigo_Acta, OCN.Fecha
FROM Orden_Compra_Nacional OCN 
LEFT JOIN Acta_Recepcion AR
ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional
INNER JOIN Bodega B
ON AR.Id_Bodega=B.Id_Bodega
WHERE OCN.Estado!="Anulada" AND DATE_FORMAT(AR.Fecha_Creacion, "%Y-%m-%d") BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.')
UNION (SELECT (SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ") FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura, PT.Nombre as Nombre_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor, (SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra, AR.Codigo as Codigo_Acta, OCN.Fecha
FROM Orden_Compra_Nacional OCN 
LEFT JOIN Acta_Recepcion AR
ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional
INNER JOIN Punto_Dispensacion PT
ON AR.Id_Punto_Dispensacion=PT.Id_Punto_Dispensacion
WHERE OCN.Estado!="Anulada" AND DATE_FORMAT(AR.Fecha_Creacion, "%Y-%m-%d") BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.') ORDER BY Codigo_Compra DESC LIMIT '.$limit.','.$tamPag;
*/
$query = '(SELECT  
			(SELECT
			GROUP_CONCAT(F.Factura SEPARATOR " / ")
			FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura, 
			
			IFNULL((SELECT B.Nombre FROM Bodega B WHERE B.Id_Bodega = AR.Id_Bodega ),
			    (SELECT BN.Nombre FROM Bodega_Nuevo BN WHERE BN.Id_Bodega_Nuevo = AR.Id_Bodega_Nuevo )) as Nombre_Bodega, 
			(SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor, 

			(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion
			PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra, 

			AR.Codigo as Codigo_Acta, OCN.Fecha
			
			FROM Orden_Compra_Nacional OCN 

			LEFT JOIN Acta_Recepcion AR

			ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional

			

			WHERE OCN.Estado!="Anulada" AND DATE_FORMAT(AR.Fecha_Creacion, "%Y-%m-%d") BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.')
		UNION ALL
		(SELECT
		 	(SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ")
		 	FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion
		  	GROUP BY F.Id_Acta_Recepcion) AS Factura, PT.Nombre as Nombre_Bodega,
		   	(SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor,
		   	(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR
			 	WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion 
			 	GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra,
			AR.Codigo as Codigo_Acta, OCN.Fecha

		FROM Orden_Compra_Nacional OCN 

		LEFT JOIN Acta_Recepcion AR

		ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional

		INNER JOIN Punto_Dispensacion PT

		ON PT.Id_Punto_Dispensacion = AR.Id_Punto_Dispensacion

		WHERE OCN.Estado!="Anulada" AND DATE_FORMAT(AR.Fecha_Creacion, "%Y-%m-%d") BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.')
	    ORDER BY Codigo_Compra DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['records'] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>