<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

switch ($tipo) {

    case "Bodega":{
            $query = "SELECT
					PRD.Id_Producto,
					IFNULL(C.Costo_Promedio,0) as Precio_Venta,
					PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada,

					CONCAT_WS(' ',PRD.Nombre_Comercial,CONCAT('(',PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion,
					PRD.Cantidad, CONCAT(PRD.Unidad_Medida,')'),'LAB -',PRD.Laboratorio_Comercial, 'CUM:',PRD.Codigo_Cum) as Nombre,
					PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario_Nuevo, IFNULL(C.Costo_Promedio,0) as precio,PRD.Cantidad_Presentacion,
					CONCAT_WS('','{\"label\":',
							CONCAT_WS('','\"Lote: ',I.Lote,
								' - Vencimiento: ',  I.Fecha_Vencimiento,
								' - Cantidad: ',(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),
							'\"'),
						',\"value\":',I.Id_Inventario_Nuevo,
						',\"Codigo_Cum\":\"',PRD.Codigo_Cum,
						'\",\"Fecha_Vencimiento\":\"',I.Fecha_Vencimiento,
						'\",\"Lote\":\"',REPLACE(I.Lote,CHAR(13,10),''),
						'\",\"Cantidad\":\"',(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),
						'\",\"Costo\":\"',IFNULL(C.Costo_Promedio,0),
						'\",\"Id_Inventario_Nuevo\":\"',I.Id_Inventario_Nuevo,
						'\",\"Cantidad_Apartada\":\"',I.Cantidad_Apartada,
						'\",\"Nombre\":\"',CONCAT_WS(' ',PRD.Nombre_Comercial,
									CONCAT('(',PRD.Principio_Activo),
										PRD.Presentacion,PRD.Concentracion,
										PRD.Cantidad, CONCAT(PRD.Unidad_Medida,')'),
										'LAB -',PRD.Laboratorio_Comercial,
										'CUM:',PRD.Codigo_Cum),
						'\",\"Embalaje\":\"',PRD.Embalaje,
						'\",\"Laboratorio_Comercial\":\"',PRD.Laboratorio_Comercial,
						'\",\"Id_Producto\":\"',PRD.Id_Producto,
						'\"}') as Lote
					FROM Inventario_Nuevo I
					INNER JOIN Producto PRD On I.Id_Producto=PRD.Id_Producto
					LEFT JOIN Costo_Promedio C ON C.Id_Producto = PRD.Id_Producto
					WHERE I.Id_Estiba='$id' AND I.Cantidad>0
					ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC";
            // echo  $query;exit;
            break;
        }
    case "Punto":{
            $query = "SELECT   PRD.Id_Producto,IFNULL(C.Costo_Promedio,0) as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada,
					CONCAT_WS(' ',PRD.Nombre_Comercial,CONCAT('(',PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad,
				CONCAT(PRD.Unidad_Medida,')'),'LAB -',PRD.Laboratorio_Comercial, 'CUM:',PRD.Codigo_Cum) as Nombre,
				PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,I.Id_Inventario_Nuevo, IFNULL(C.Costo_Promedio,0) as precio,PRD.Cantidad_Presentacion,

				CONCAT_WS('','{\"label\":',
							CONCAT_WS('','\"Lote:', TRIM(I.Lote),
								' - Vencimiento: ',  I.Fecha_Vencimiento,
								' - Cantidad: ',(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),
								'\"'),
						',\"value\":', I.Id_Inventario_Nuevo,
						',\"Codigo_Cum\":\"',I.Codigo_Cum,
						'\",\"Fecha_Vencimiento\":\"',I.Fecha_Vencimiento,
						'\",\"Lote\":\"',TRIM(I.Lote),
						'\",\"Cantidad\":\"',(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),
						'\",\"Costo\":\"',IFNULL(C.Costo_Promedio,0),
						'\",\"Id_Inventario_Nuevo\":\"',I.Id_Inventario_Nuevo,
						'\",\"Id_Categoria\":\"',PRD.Id_Categoria,
						'\",\"Cantidad_Apartada\":\"',I.Cantidad_Apartada,
						'\",\"Nombre\":\"',CONCAT_WS(' ',PRD.Nombre_Comercial,
													CONCAT('(',PRD.Principio_Activo),
													PRD.Presentacion,PRD.Concentracion,
													PRD.Cantidad,
													CONCAT(PRD.Unidad_Medida,')'),
													'LAB -',	PRD.Laboratorio_Comercial,
													'CUM:',PRD.Codigo_Cum
												),
						'\",\"Embalaje\":\"',PRD.Embalaje,
						'\",\"Laboratorio_Comercial\":\"',PRD.Laboratorio_Comercial,
						'\",\"Id_Producto\":\"',PRD.Id_Producto,
						'\"}') as Lote
					FROM Inventario_Nuevo I
					INNER JOIN Producto PRD
					On I.Id_Producto=PRD.Id_Producto
					LEFT JOIN Costo_Promedio C
					ON C.Id_Producto = PRD.Id_Producto
					INNER JOIN Estiba E on E.Id_Estiba = I.Id_Estiba
					WHERE E.Id_Punto_Dispensacion='$id' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0
						ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC";
            break;
        }
}

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$i = -1;
$idproducto = '';
$resultado = [];
$pos = -1;
$poslotes = 0;
$lotes = [];
$cantidad_disponible = 0;
foreach ($productos as $producto) {$i++;
    if ($producto['Id_Producto'] != $idproducto) {
        if ($pos >= 0) {
            $resultado[$pos]["Lotes"] = $lotes;
            $resultado[$pos]["Cantidad_Disponible"] = $cantidad_disponible;
            $poslotes = 0;
        }
        $pos++;
        $resultado[$pos]["Id_Producto"] = $producto["Id_Producto"];
        if ($producto["Nombre"] == '') {
            //var_dump($producto["Nombre_Comercial"]);
            //var_dump ($producto["Id_Producto"]);
            $resultado[$pos]["Nombre"] = $producto["Nombre_Comercial"] . " LAB- " . $producto["Laboratorio_Comercial"];
        } else {
            $resultado[$pos]["Nombre"] = $producto["Nombre"];
        }

        $resultado[$pos]["precio"] = $producto["precio"];
        $resultado[$pos]["Precio_Venta"] = $producto["precio"];
        $resultado[$pos]["Id_Inventario_Nuevo"] = $producto["Id_Inventario_Nuevo"];
        $resultado[$pos]["Cantidad_Presentacion"] = $producto["Cantidad_Presentacion"];
        $resultado[$pos]["Embalaje"] = $producto["Embalaje"];
        $idproducto = $producto['Id_Producto'];
        $lotes = [];
        $cantidad_disponible = 0;
        $borrar = array("\t", "\r", "\n");
        $borra2 = array("", "", "");
        $lotes[$poslotes] = (array) json_decode(trim(str_replace($borrar, $borra2, $producto["Lote"]), true));

        $cantidad_disponible += ($producto['Cantidad'] - $producto['Cantidad_Apartada'] - $producto['Cantidad_Seleccionada']);
    } else {
        $poslotes++;
        $borrar = array("\t", "\r", "\n");
        $borra2 = array("", "", "");
        $lotes[$poslotes] = (array) json_decode(trim(str_replace($borrar, $borra2, $producto["Lote"]), true));

        $cantidad_disponible += $producto['Cantidad'] - $producto['Cantidad_Apartada'] - $producto['Cantidad_Seleccionada'];
    }

}

$resultado[$pos]["Lotes"] = $lotes;

echo json_encode($resultado);
