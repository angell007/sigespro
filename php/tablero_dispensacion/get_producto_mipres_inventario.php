<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');


$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$id_punto_dispensacion = (isset($_REQUEST['id_punto']) ? $_REQUEST['id_punto'] : '');
$id_mipres = (isset($_REQUEST['id_mipres']) ? $_REQUEST['id_mipres'] : '');

$query = "SELECT PDM.Id_Producto_Dispensacion_Mipres, PDM.Id_Producto, PDM.Cantidad, PDM.NoPrescripcion as Numero_Prescripcion, PDM.Tipo_Tecnologia, PDM.CodSerTecAEntregar,

True As Brand

 FROM Producto_Dispensacion_Mipres As PDM
INNER JOIN  Producto As Produ ON Produ.Id_Producto = PDM.Id_Producto
 INNER JOIN Dispensacion_Mipres  AS DM  ON 
 DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
		
		WHERE DM.Id_Dispensacion_Mipres=$id_mipres";

$queryObj->SetQuery($query);
$productos_mipres = $queryObj->ExecuteQuery('Multiple');

$resultado = [];

foreach ($productos_mipres as $m) {
	$condicion = SetCondiciones($m['Id_Producto']);

	$query_producto = "SELECT Id_Inventario_Nuevo 
						FROM Inventario_Nuevo 
						INNER JOIN Estiba As Estiba ON Inventario_Nuevo.Id_Estiba=Estiba.Id_Estiba 
						WHERE Estiba.Id_Punto_Dispensacion =$id_punto_dispensacion AND Id_Producto=$m[Id_Producto] AND (Cantidad-Cantidad_Apartada)>0 ";
	$queryObj->SetQuery($query_producto);
	$inv = $queryObj->ExecuteQuery('Simple');

	if ($inv) {
		$buscar_inventario = 'false';
	} else {
		$buscar_inventario = 'true';
	}

	if ($m['Tipo_Tecnologia'] == 'M') {
		$query = GetQuery($condicion, $buscar_inventario, $m['Cantidad'], $m['Numero_Prescripcion'], $m['Tipo_Tecnologia'], $m['Id_Producto_Dispensacion_Mipres']);
		$queryObj->SetQuery($query);
		$productos = $queryObj->ExecuteQuery('Multiple');
		foreach ($productos as $i => $value) {
			$productos[$i]['Asociados'] = [];
		}
	} else {
		$query = GetQueryAsociados($condicion);
		$queryObj->SetQuery($query);
		$productos = $queryObj->ExecuteQuery('Multiple');

		$productos[0]['Brand']=$m['Brand'];
		$asociados = GetAsociados($productos[0], $m['Numero_Prescripcion']);
		$productos[0]['Asociados'] = $asociados;

		$inventario_principal = null;
		if (!empty($asociados)) {
			foreach ($asociados as $asociado) {
				if ((int) $asociado['Id_Producto'] === (int) $productos[0]['Id_Producto']) {
					$inventario_principal = $asociado;
					break;
				}
			}

			if (!$inventario_principal) {
				$inventario_principal = $asociados[0];
			}
		}

		if ($inventario_principal) {
			$productos[0]['Id_Inventario_Nuevo'] = $inventario_principal['Id_Inventario_Nuevo'];
			$productos[0]['Fecha_Vencimiento'] = $inventario_principal['Fecha_Vencimiento'];
			$productos[0]['Lote'] = $inventario_principal['Lote'];
			$productos[0]['Cantidad_Disponible'] = $inventario_principal['Cantidad_Disponible'];
			$productos[0]['Costo'] = $inventario_principal['Costo'];
			$productos[0]['Cantidad_Formulada'] = $inventario_principal['Cantidad_Formulada'];
			$productos[0]['Numero_Prescripcion'] = $inventario_principal['Numero_Prescripcion'];
		} else {
			$productos[0]['Id_Inventario_Nuevo'] = 0;
			$productos[0]['Fecha_Vencimiento'] = '0000-00-00';
			$productos[0]['Lote'] = 'Pendiente';
			$productos[0]['Cantidad_Disponible'] = 0;
			$productos[0]['Costo'] = 0;
			$productos[0]['Cantidad_Formulada'] = $m['Cantidad'];
			$productos[0]['Numero_Prescripcion'] = $m['Numero_Prescripcion'];
		}
	}

	$resultado = array_merge($resultado, $productos);

	for ($i = 0; $i < count($resultado); $i++) {
		$resultado[$i]['pos'] = (int) $i;
	}
}

$http_response->SetRespuesta(0, 'Exitoso', 'Se obtuvieron datos de productos');
$response = $http_response->GetRespuesta();

$response['Productos'] = $resultado;
$response['Total'] = count($productos_mipres);

echo json_encode($response);

function SetCondiciones($id_producto)
{

	$condicion = " WHERE P.Id_Producto= $id_producto ";
	return $condicion;
}



function GetQuery($condicion, $buscar_inventario, $cantidad, $numeroPrescripcion, $tipo_tecnologia, $prod_mipres)
{
	global $id_punto_dispensacion;

	$query = '';

	$query .= 'SELECT
	' . $prod_mipres . ' AS Id_Producto_Dispensacion_Mipres,
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,
	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, 0 as Seleccionado, "' . $tipo_tecnologia . '" AS Tipo_Tecnologia

	';

	if ($buscar_inventario == 'false') {
		/*Modificado el 18-08-2020 Carlos Cardona - Costo Promedio */
		$query .= ", I.Id_Inventario_Nuevo, I.Fecha_Vencimiento, I.Lote, I.Id_Inventario_Nuevo, (I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible, 
		IFNULL( (SELECT CP.Costo_Promedio  FROM Costo_Promedio CP WHERE CP.Id_Producto = I.Id_Producto ), 0  ) AS Costo, 
		$cantidad as Cantidad_Formulada, $numeroPrescripcion as Numero_Prescripcion
		FROM Inventario_Nuevo I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
		INNER JOIN Estiba As Est ON I.Id_Estiba=Est.Id_Estiba
		" . $condicion . ' 
		AND Est.Id_Punto_Dispensacion = ' . $id_punto_dispensacion . ' AND (I.Cantidad-I.Cantidad_Apartada) > 0 
		ORDER BY I.Fecha_Vencimiento ASC ';
	} else {
		$query .= ", 0 AS Id_Inventario_Nuevo, 0 as Cantidad_Disponible, 'Pendiente' as Lote, '0000-00-00' as Fecha_Vencimiento, 0 as Id_Inventario_Nuevo,0 as Costo , $cantidad as Cantidad_Formulada,$numeroPrescripcion as Numero_Prescripcion FROM Producto P  " . $condicion . '
		ORDER BY P.Nombre_Comercial ASC';
	}

	return $query;
}


function GetTabla($id)
{
	global $queryObj;
	$query = "SELECT Tipo_Lista FROM Tipo_Servicio WHERE Id_Tipo_Servicio=$id";
	$queryObj->SetQuery($query);
	$lista = $queryObj->ExecuteQuery('simple');

	return $lista['Tipo_Lista'];
}



function GetListaDepartamento($id)
{
	global $queryObj;

	$query = "SELECT Id_Lista_Producto_Nopos FROM Punto_Dispensacion PT INNER JOIN Departamento_Lista_Nopos DL ON PT.Departamento=DL.Id_Departamento WHERE PT.Id_Punto_Dispensacion=$id";
	$queryObj->SetQuery($query);
	$lista = $queryObj->ExecuteQuery('simple');

	return $lista['Id_Lista_Producto_Nopos'];
}


function GetAsociados($data, $numeroPrescripcion)
{
	global $id_punto_dispensacion;
	/*Modificado el 18-08-2020 Carlos Cardona - Costo Promedio */
	/*Modificado para modelo Estiba 08/04/2021 */

	$query = "SELECT P.Nombre_Comercial,	CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) AS Nombre,	P.Codigo_Cum, P.Id_Producto,
	'$data[Id_Producto_Dispensacion_Mipres]' AS Id_Producto_Dispensacion_Mipres, '$data[Cantidad]' AS Cantidad_Formulada, IFNULL(I.Cantidad_Disponible, 0) AS Cantidad_Disponible, 0 AS Seleccionado,
	IFNULL(I.Id_Inventario_Nuevo, 0) AS Id_Inventario_Nuevo, IFNULL(I.Fecha_Vencimiento, '0000-00-00') AS Fecha_Vencimiento, 
	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) AS Cantidad_Minima, 
	 IFNULL( (SELECT CP.Costo_Promedio  FROM Costo_Promedio CP WHERE CP.Id_Producto = P.Id_Producto ), 0  ) AS Costo, IFNULL(I.Lote, 'Pendiente') AS Lote, '$numeroPrescripcion' AS Numero_Prescripcion 
	FROM Producto_Tipo_Tecnologia_Mipres PD 
	INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres 
	INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto 

	LEFT JOIN

	(SELECT Inv.*, (Cantidad-Cantidad_Apartada) AS Cantidad_Disponible FROM Inventario_Nuevo As Inv
	INNER JOIN Estiba  Estiba ON Estiba.Id_Estiba = Inv.Id_Estiba
	WHERE Estiba.Id_Punto_Dispensacion = $id_punto_dispensacion AND (Cantidad-Cantidad_Apartada) > 0)

     I ON I.Id_Producto = P.Id_Producto  
	WHERE (Codigo_Actual='$data[CodSerTecAEntregar]' OR Codigo_Anterior='$data[CodSerTecAEntregar]' ) AND M.Codigo='$data[Tipo_Tecnologia]'";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$prod = $oCon->getData();
	unset($oCon);

	foreach ($prod as $i => $value) { //ESTA ESTRUCTURA ES PARA QUE FUNCIONE EN LOS ng-select
		$stockText = $value['Cantidad_Disponible'] > 0 ? "Con Stock" : "Sin Stock";
		$prod[$i]['label'] = "Producto: $value[Nombre_Comercial] | Cantidad_Disponible: $value[Cantidad_Disponible] | Lote: $value[Lote] | Fecha Vencimiento: $value[Fecha_Vencimiento] | Inventario: $stockText";
		$prod[$i]['value'] = $i + 1;
	}

	return $prod;
}

function GetQueryAsociados($condicion)
{
	global $id_mipres;

	$query = '';

	$query .= 'SELECT
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,PD.Tipo_Tecnologia, PD.CodSerTecAEntregar, PD.Cantidad,
	0 as Seleccionado,
	"" AS Id_Producto_Asociado,
	"" AS Id_Producto_Asoc_Anterior,
	"No" AS Ver_Asociado,
	Id_Producto_Dispensacion_Mipres
	FROM Producto P 
	INNER JOIN Producto_Dispensacion_Mipres PD ON P.Id_Producto=PD.Id_Producto ' . $condicion . ' AND PD.Id_Dispensacion_Mipres=' . $id_mipres . ' LIMIT 1 ';

	return $query;
}
