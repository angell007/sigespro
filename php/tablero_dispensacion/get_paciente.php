<?php

use PhpParser\Node\Expr\Exit_;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.http_response.php';
include_once '../../class/class.utility.php';

$http_response = new HttpResponse(); //Objeto de la clase HttpResponse
$queryObj = new QueryBaseDatos(); //Objeto de la clase Query
$util = new Utility(); //Objeto de la clase Utility

$idPaciente = (isset($_REQUEST['id_paciente']) ? $_REQUEST['id_paciente'] : ''); // id del paciente
$IdPunto = (isset($_REQUEST['id_punto']) ? $_REQUEST['id_punto'] : '');// id del punto

$hoy = date('Y-m-d');  // fecha actual
$fecha_min_pendiente = strtotime('-5 month', strtotime($hoy)); // fecha minima pendiente
$fecha_min_pendiente = date('Y-m-d', $fecha_min_pendiente); // fecha minima pendiente
    
$condicion = SetCondiciones(); // obtiene la condicion de la consulta
$query = GetQueryPaciente(); // obtiene el query

$condicion_lotes = "WHERE Estiba.Id_Punto_Dispensacion=$IdPunto "; // condicion de lote

$queryObj->SetQuery($query); // seteo el query
$paciente = $queryObj->ExecuteQuery('simple'); // ejecuto el query


if ($paciente['Id_Paciente']) { //  si el paciente existe

    if ($paciente['Estado'] == 'Activo') { // si el paciente esta activo

        $salario_base = GetSalarioBase();// Se obtienen salario base de la configuarcion
        $servicios = GetServicios(); // se obtienen los servicios
        $i = 0; // contador
        /** se cambia la logica para traer todos los productos pendientes por dispensacion y */
        foreach ($servicios as $s) { // se recorren los servicios
            $tiposervicios = GetTipoServicios($s['Id_Servicio']); // se obtienen los tipos de servicio
            if ($tiposervicios != '') { // si existen tipos de servicio es no esta vacio
                $queryPendientesConExistencia = GetQueryPendientesConExistencia($tiposervicios); // se obtienen query de los pendientes con existencia
                $queryObj->SetQuery($queryPendientesConExistencia);// se setea el query
                $prodcutospendientesdispensar = $queryObj->ExecuteQuery('Multiple');// se ejecuta el query y se guardan en la variable pendientesconexistencia
                $prodcutospendientesdispensar = VerOtrosLotes($prodcutospendientesdispensar);// se busca similares en los lotes
                $prodcutospendientesdispensar = VerSimilares($prodcutospendientesdispensar);// se busca similares en productos asociados
            } else {
                $prodcutospendientesdispensar = []; // se pasa que no tiene pendientes
            }
            $servicios[$i]['Productos_Disponibles'] = []; // se pasa que no tiene pendientes
            $servicios[$i]['Productos_No_Disponibles'] = []; // se pasa que no tiene pendientes
            foreach ($prodcutospendientesdispensar as $producto) { // se recorren los productos pendientes a dispensar
                $producto = NormalizarProducto($producto); // garantiza estructura plana y campos basicos
                if($producto['Cantidad_Disponible'] > 0 || count($producto['Similares']) >0 ){ // si tiene stock o similares con stock se guarda en productos disponibles
                     $servicios[$i]['Productos_Disponibles'][] = $producto;
                }else{ // si no tiene stock se guarda en productos no disponibles
                    $servicios[$i]['Productos_No_Disponibles'][] = $producto; // se envía el producto sin stock (ya incluye Similares)
                }
            }
            
            //$servicios[$i]['Productos_Disponibles'] = $pendientesconexistencia;
            //$id_productos_existentes = GetIdExistentes($pendientesconexistencia);            

            //$query = GetPendientesSinExistencia($tiposervicios);
            //$queryObj->SetQuery($query);
            //$pendientessinexistencia = $queryObj->ExecuteQuery('Multiple');

            //$pendientessinexistencia = VerSimilares($pendientessinexistencia);

            //$servicios[$i]['Productos_No_Disponibles'] = $pendientessinexistencia;

            $i++;
        }
        /**
         * hasta aqui se obtienen los servicios(dispensaciones pendientes) que se pueden entregar
         */

        $paciente = GetDatosSubsidiado($paciente);

        $productos_del_mes = GetProductoEntregados($paciente['Id_Paciente']);

        $http_response->SetRespuesta(0, 'Exitoso', 'Se obtuvieron datos del paciente');
        $response = $http_response->GetRespuesta();
        $response['Servicios'] = $servicios;
        $response['Paciente'] = $paciente;
        $response['Productos_Entregados'] = $productos_del_mes;
    } else {
        $http_response->SetRespuesta(1, 'Error', 'El paciente se encuentra inactivo en la base de datos.');
        $response = $http_response->GetRespuesta();
    }
} else {
    $http_response->SetRespuesta(1, 'Error', 'El paciente consultado no se encuntra registrado en la base de datos.');
    $response = $http_response->GetRespuesta();
}

echo json_encode($response);

function SetCondiciones()
{
    global $idPaciente;

    $condicion = " WHERE P.Id_Paciente='$idPaciente'";

    return $condicion;
}

function GetQueryPaciente()
{
    global $condicion;

    $query = 'SELECT
	R.Nombre as Regimen,
	N.Nombre as Nivel ,
	N.Valor as Valor_Nivel,
	N.Numero as Numero_Nivel,
	CONCAT_WS(" ",P.Primer_Nombre, P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido) as Nombre_Paciente,
	0 AS Cuota,
	P.EPS as Eps,
	P.Nit,
	P.Id_Departamento,
	P.Id_Paciente,
	P.Estado
	FROM Paciente P
	LEFT JOIN Regimen R ON P.Id_Regimen=R.Id_Regimen
	LEFT JOIN Nivel N ON P.Id_Nivel=N.Id_Nivel ' . $condicion;

    return $query;
}

function GetSalarioBase()
{
    global $queryObj;
    $query = "SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1";
    $queryObj->SetQuery($query);
    $salario = $queryObj->ExecuteQuery('simple');

    return $salario['Salario_Base'];
}

function GetQueryPendientesConExistencia($tiposervicios)
{
    global $idPaciente, $IdPunto;

    $epsCliente = (getPaciente($idPaciente))[0]['Nit'] ?? null;

    $brand = ' False AS Brand,';
    if ($epsCliente) {
        $brand = "
        IF(
            EXISTS (
                SELECT 1
                FROM Lista_Producto_Nopos LPN
                INNER JOIN Producto_NoPos PN 
                    ON LPN.Id_Lista_Producto_Nopos = PN.Id_Lista_Producto_Nopos
                WHERE PN.Cum = P.Codigo_Cum
                  AND LPN.Id_Cliente = $epsCliente
                LIMIT 1
            ),
            TRUE,
            FALSE
        ) AS Brand,";
    }

    if ($tiposervicios == '') {
        return '';
    }

    $query = "
    SELECT
        CONCAT_WS(
            ' ',
            CONCAT('(',D.Codigo,')'),
            '-',
            P.Nombre_Comercial,
            '(',
            P.Principio_Activo,
            P.Presentacion,
            P.Concentracion,
            ')',
            P.Cantidad,
            P.Unidad_Medida
        ) AS Nombre,

        P.Nombre_Comercial,
        P.Codigo_Cum,

        /* ===== EXISTENCIA ===== */
        IFNULL(SUM(
            CASE 
                WHEN E.Id_Punto_Dispensacion = $IdPunto 
                THEN (I.Cantidad - IFNULL(I.Cantidad_Apartada,0) - IFNULL(I.Cantidad_Seleccionada,0))
                ELSE 0
            END
        ), 0) AS Cantidad_Punto,

        IFNULL(SUM(
            CASE 
                WHEN E.Id_Punto_Dispensacion IS NULL 
                THEN (I.Cantidad - IFNULL(I.Cantidad_Apartada,0) - IFNULL(I.Cantidad_Seleccionada,0))
                ELSE 0
            END
        ), 0) AS Cantidad_Bodega,

        IFNULL(SUM(
            (I.Cantidad - IFNULL(I.Cantidad_Apartada,0) - IFNULL(I.Cantidad_Seleccionada,0))
        ), 0) AS Cantidad_Disponible,

        /* ===== DATOS INVENTARIO ===== */
        MIN(I.Fecha_Vencimiento) AS Fecha_Vencimiento,
        MIN(I.Lote) AS Lote,

        IFNULL(
            (SELECT C.Costo_Promedio 
             FROM Costo_Promedio C 
             WHERE C.Id_Producto = P.Id_Producto 
             LIMIT 1),
            0
        ) AS Costo,

        D.*,
        0 AS Seleccionado,

        IFNULL(
            (SELECT PD.RLnumeroSolicitudSiniestro 
             FROM Positiva_Data PD 
             WHERE PD.Id_Dispensacion = D.Id_Dispensacion
             LIMIT 1),
            (SELECT PD.RLnumeroSolicitudSiniestro 
             FROM Positiva_Data PD 
             WHERE PD.id = D.Id_Positiva_Data
             LIMIT 1)
        ) AS Solicitud,

        $brand
        0 AS Mostrar

    FROM (
        SELECT
            D.Cuota,
            D.Codigo,
            (PD.Cantidad_Formulada - PD.Cantidad_Entregada) AS Cantidad_Pendiente,
            PD.Cantidad_Entregada AS Entrega_Parcial,
            PD.Id_Producto,
            (PD.Cantidad_Formulada - PD.Cantidad_Entregada) AS Cantidad_Formulada,
            PD.Numero_Autorizacion,
            PD.Fecha_Autorizacion,
            PD.Id_Dispensacion,
            D.Id_Dispensacion_Mipres,
            PD.Id_Producto_Dispensacion,
            D.Id_Positiva_Data,
            PD.Generico,
            A.Estado AS Estado_Auditoria
        FROM Producto_Dispensacion PD
        INNER JOIN Dispensacion D 
            ON D.Id_Dispensacion = PD.Id_Dispensacion
        LEFT JOIN Auditoria A 
            ON (A.Id_Dispensacion = D.Id_Dispensacion 
                OR D.Id_Auditoria = A.Id_Auditoria)
            AND A.Estado IN ('Aceptar','Aceptado')
        INNER JOIN Servicio SE 
            ON SE.Id_Servicio = D.Id_Servicio
        WHERE (PD.Cantidad_Formulada - PD.Cantidad_Entregada) > 0
          AND D.Numero_Documento = '$idPaciente'
          AND D.Id_Tipo_Servicio IN ($tiposervicios)
          AND D.Estado_Dispensacion <> 'Anulada'
          AND DATE(D.Fecha_Actual) >= DATE_SUB(NOW(), INTERVAL SE.Dias_Limite_Pendiente DAY)
    ) D

    INNER JOIN Producto P 
        ON D.Id_Producto = P.Id_Producto

    LEFT JOIN Inventario_Nuevo I 
        ON I.Id_Producto = P.Id_Producto
       AND (
            DATE(I.Fecha_Vencimiento) >= CURDATE()
            OR I.Fecha_Vencimiento IS NULL
       )

    LEFT JOIN Estiba E 
        ON I.Id_Estiba = E.Id_Estiba

    GROUP BY
        D.Id_Dispensacion,
        P.Id_Producto

    ";

    return $query;
}


function getPaciente($idPaciente)
{
    $queryObj = new QueryBaseDatos();
    $query = " SELECT Id_Paciente,  Nit , Concat(Primer_Nombre, ' ' , Primer_Apellido ) As Nombre, EPS FROM Paciente WHERE `Id_Paciente` = '$idPaciente' ";
    $queryObj->SetQuery($query);
    $paciente = $queryObj->ExecuteQuery('Simple');
    return $paciente;
}

function GetIdExistentes($productos)
{
    $ids_productos = '';

    foreach ($productos as $producto) {
        $pos = strpos($ids_productos, $producto["Id_Producto"]);
        if ($pos === false) {
            $ids_productos .= $producto["Id_Producto"] . ',';
        }
    }

    return trim($ids_productos, ",");
}

function GetPendientesSinExistencia($tiposervicios)
{
    global $id_productos_existentes, $idPaciente;

    $condicion = '';

    if ($id_productos_existentes != '') {
        $condicion = 'AND PD.Id_Producto NOT IN (' . $id_productos_existentes . ')';
    }
    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
    $query = "SELECT CONCAT_WS(' ',CONCAT('(',D.Codigo,')'),'-',P.Nombre_Comercial,'(',	P.Principio_Activo, P.Presentacion, P.Concentracion,')', P.Cantidad, P.Unidad_Medida) as Nombre,   P.Nombre_Comercial,
			D.Cuota,
			(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
			PD.Id_Producto,
			P.Codigo_Cum,
			PD.Lote,
			PD.Id_Inventario_Nuevo,
			(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Formulada,
			PD.Cantidad_Entregada as Entrega_Parcial,
			PD.Numero_Autorizacion,
			PD.Fecha_Autorizacion,
			'' as Vencimiento,
			PD.Id_Producto_Dispensacion,
			PD.Id_Dispensacion,
			D.Id_Dispensacion_Mipres,
			0 as Seleccionado,
			0 as Mostrar,
			0 as Buscar,
			D.Id_Servicio,
			P.Id_Categoria, 
            (Select PD.RLnumeroSolicitudSiniestro From Positiva_Data PD Where PD.Id_Dispensacion = D.Id_Dispensacion) as Solicitud, 
            PD.Generico
			FROM Dispensacion D
			INNER JOIN Producto_Dispensacion PD	ON D.Id_Dispensacion=PD.Id_Dispensacion
			INNER JOIN Auditoria A	ON (A.Id_Dispensacion=D.Id_Dispensacion OR D.Id_Auditoria = A.Id_Auditoria) AND A.Estado IN ('Aceptar', 'Aceptada')
			INNER JOIN Producto P ON P.Id_Producto=PD.Id_Producto
			INNER JOIN Servicio SE ON SE.Id_Servicio = D.Id_Servicio
			WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0
			AND D.Numero_Documento='$idPaciente'
			AND D.Id_Tipo_Servicio IN ($tiposervicios)
			AND D.Estado_Dispensacion <> 'Anulada'
			AND DATE(D.Fecha_Actual) >= DATE_SUB(NOW(),INTERVAL SE.Dias_Limite_Pendiente DAY)
			" . $condicion . "
			GROUP BY PD.Id_Producto, D.Id_Dispensacion";

    return $query;
}

function GetServicios()
{

    global $queryObj, $IdPunto;
    $query = "SELECT S.Id_Servicio,S.Nombre, REPLACE(S.Nombre, ' ', '_') as fieldName
	           FROM Servicio_Punto_Dispensacion PS
			   INNER JOIN Servicio S ON PS.Id_Servicio=S.Id_Servicio
			   WHERE Id_Punto_Dispensacion=$IdPunto ";
    $queryObj->SetQuery($query);
    $servicios = $queryObj->ExecuteQuery('Multiple');

    return $servicios;
}

function GetTipoServicios($id)
{
    global $queryObj, $IdPunto;

    $query = "SELECT GROUP_CONCAT( DISTINCT TS.Id_Tipo_Servicio) as Tipo_Servicio
				FROM Tipo_Servicio_Punto_Dispensacion TS
				INNER JOIN Tipo_Servicio S ON TS.Id_Tipo_Servicio=S.Id_Tipo_Servicio
				WHERE S.Id_Servicio=$id AND TS.Id_Punto_Dispensacion=$IdPunto";
    $queryObj->SetQuery($query);
    $servicios = $queryObj->ExecuteQuery('simple');

    return $servicios['Tipo_Servicio'];
}

function VerSimilares($productos)
{
    $j = -1;
    foreach ($productos as $p) {
        $j++;
        if($p['Entrega_Parcial']==0){
            $similares = GetSimilares($p['Id_Producto'], $p['Generico']);
        }

        if (!$similares['Producto_Asociado']) {
            $productos[$j]["Similares"] = [];
        } else {

            $productossimilares = GetLotesProductosimilares($similares, $p);

            if (count($productossimilares) == 0) {
                $productos[$j]["Similares"] = [];
                //    unset($productos[$j]);
            } else {
			$productossimilares= VerOtrosLotes($productossimilares);
                	$productos[$j]["Similares"] = $productossimilares;
            }
        }
        unset($similares);
    }

    return $productos;
}

function GetSimilares($id, $generico)
{

    global $queryObj;

    $query = "SELECT CONCAT_WS(' ,' ,PA.Producto_Asociado, group_concat( PA1.Producto_Asociado separator ' ,')) as Producto_Asociado
            FROM Producto_Asociado PA
            LEFT JOIN Producto_Asociado PA1 on concat(',', PA.Id_Asociado_Genericos, ',') like Concat('%,',PA1.Id_Producto_Asociado, ',%') 
            WHERE (PA.Producto_Asociado LIKE '$id,%' OR PA.Producto_Asociado LIKE '%, $id,%' OR PA.Producto_Asociado LIKE '%, $id' OR PA.Producto_Asociado LIKE '$id') ";
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');

    // Fallback: si no hay asociaciones configuradas, buscar genericos por principio activo/presentacion
    if (!$productos['Producto_Asociado']) {
        $productos = BuscarGenericosPorPrincipioActivo($id);
    }
    return $productos;
}

/**
 * Busca productos que compartan principio activo/presentacion/concentracion con el producto original.
 */
function BuscarGenericosPorPrincipioActivo($idProducto)
{
    global $queryObj;

    $query = "
        SELECT GROUP_CONCAT(P2.Id_Producto) AS Producto_Asociado
        FROM Producto P1
        INNER JOIN Producto P2
            ON LOWER(P2.Principio_Activo) = LOWER(P1.Principio_Activo)
           AND REPLACE(REPLACE(LOWER(P2.Concentracion), 'mg', ''), ' ', '') = REPLACE(REPLACE(LOWER(P1.Concentracion), 'mg', ''), ' ', '')
           AND P2.Unidad_Medida   = P1.Unidad_Medida
           AND P2.Cantidad        = P1.Cantidad
           AND P2.Id_Producto    <> P1.Id_Producto
        WHERE P1.Id_Producto = $idProducto
    ";

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');

    return $productos;
}

function GetLotesProductosimilares($productos, $p)
{
    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
    global $condicion_lotes, $queryObj;
    $query = "SELECT I.Cantidad as Cantidad_Disponible,P.Nombre_Comercial,
    I.Id_Producto,
    IFNULL( (SELECT C.Costo_Promedio FROM Costo_Promedio C WHERE C.Id_Producto = P.Id_Producto ) , 0  ) AS Costo,
    -- (Select PD.RLnumeroSolicitudSiniestro From Positiva_Data PD Where PD.Id_Dispensacion = D.Id_Dispensacion) as Solicitud,
    CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,
    P.Id_Producto,
    0 as Seleccionado, 
    I.Id_Inventario_Nuevo,
    I.Fecha_Vencimiento, P.Codigo_Cum,I.Lote,
	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, 
    $p[Cantidad_Formulada] AS Cantidad_Formulada, 
    $p[Id_Producto_Dispensacion] as Id_Producto_Dispensacion, 
    '$p[Solicitud]' as Solicitud

    FROM Inventario_Nuevo I
    INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto
    INNER JOIN Estiba As Estiba ON I.Id_Estiba=Estiba.Id_Estiba
    $condicion_lotes AND  I.Id_Producto IN ($productos[Producto_Asociado] )

    AND I.Cantidad>0
    GROUP BY I.Id_Producto  
   ";

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

    return $productos;
}

function GetDatosSubsidiado($p)
{
    global $salario_base;
    $maximo_cobro = 0;
    $porcentaje = 0;
    $aplica_cuota_recuperacion = 'No';
    if ($p['Regimen'] == 'Subsidiado') {
        if ($p['Numero_Nivel'] == '2') {
            $maximo_cobro = $salario_base * 2;
            $aplica_cuota_recuperacion = 'Si';
            $porcentaje = '0.1';
        } elseif ($p['Numero_Nivel'] == '3') {
            $maximo_cobro = $salario_base * 3;
            $aplica_cuota_recuperacion = 'Si';
            $porcentaje = '0.3';
        }
    }
    $p['Porcentaje'] = $porcentaje;
    $p['Aplica_Cuota_Recuperacion'] = $aplica_cuota_recuperacion;
    $p['Maximo_Cobro'] = $maximo_cobro;
    $p['Total_Cuota'] = GetCoutas($p['Id_Paciente']);

    return $p;
}

function GetCoutas($id)
{
    global $queryObj;

    $query = "SELECT IFNULL(SUM(Cuota),0) as Total_Cuota FROM Dispensacion WHERE Numero_Documento='$id' AND Estado_Dispensacion!='Anulada' AND YEAR(Fecha_Actual) = YEAR(CURRENT_DATE())  ";
    $queryObj->SetQuery($query);
    $cuota = $queryObj->ExecuteQuery('simple');

    return $cuota['Total_Cuota'];
}

function GetProductoEntregados($i)
{
    global $queryObj;

    $query = "SELECT
	D.Codigo,DATE(D.Fecha_Actual) as Fecha,PD.Id_Producto
	 FROm Producto_Dispensacion PD INNER JOIN Dispensacion D On PD.Id_Dispensacion=D.Id_Dispensacion
	 WHERE D.Numero_Documento='$i' AND MONTH(Fecha_Actual)=MONTH(NOW()) AND YEAR(D.Fecha_Actual)=YEAR(NOW()) AND  D.Estado_Dispensacion!='Anulada'
	 GROUP BY PD.Id_Producto
	 ORDER BY D.Id_Dispensacion DESC ";

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos;
}

function VerOtrosLotes($productos)
{
	$i=0;
    foreach ($productos as $value) {
        # code...
	//   echo json_encode($value); exit;
	  $lotes= lotesCantidades($value['Id_Producto']);
	  $productos[$i]['Lotes']=$lotes;
	  $i++;
    }
    return $productos;

}
function lotesCantidades($producto)
{
	global  $condicion_lotes;
    $query = "SELECT
		I.Id_Inventario_Nuevo,
		P.Nombre_Comercial,
		P.Codigo_Cum,
    		CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,
		I.Lote, 
		I.Id_Producto,
		I.Id_Estiba,
		(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Disponible,
		I.Cantidad, 
		0 as Cantidad_Entregada
	FROM Inventario_Nuevo I
	INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto
	INNER JOIN Estiba  as Estiba ON  Estiba.Id_Estiba = I.Id_Estiba
	$condicion_lotes
	AND  I.Id_Producto=$producto	
	HAVING Cantidad>0";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lotes = $oCon->getData();
    return $lotes;
}

/**
 * Asegura que cada producto tenga estructura plana y campos basicos inicializados.
 */
function NormalizarProducto($producto)
{
    // Defaults basicos para evitar undefined en el front
    $producto['Codigo'] = isset($producto['Codigo']) ? $producto['Codigo'] : '';
    $producto['Nombre'] = isset($producto['Nombre']) ? $producto['Nombre'] : '';
    $producto['Codigo_Cum'] = isset($producto['Codigo_Cum']) ? $producto['Codigo_Cum'] : '';
    $producto['Lote'] = isset($producto['Lote']) ? $producto['Lote'] : '';
    $producto['Fecha_Vencimiento'] = isset($producto['Fecha_Vencimiento']) ? $producto['Fecha_Vencimiento'] : '';
    $producto['Cantidad_Disponible'] = isset($producto['Cantidad_Disponible']) ? $producto['Cantidad_Disponible'] : 0;
    $producto['Cantidad_Pendiente'] = isset($producto['Cantidad_Pendiente']) ? $producto['Cantidad_Pendiente'] : 0;
    $producto['Cantidad_Formulada'] = isset($producto['Cantidad_Formulada']) ? $producto['Cantidad_Formulada'] : 0;
    $producto['Seleccionado'] = isset($producto['Seleccionado']) ? $producto['Seleccionado'] : "0";
    $producto['Mostrar'] = isset($producto['Mostrar']) ? $producto['Mostrar'] : "0";
    $producto['Buscar'] = isset($producto['Buscar']) ? $producto['Buscar'] : "0";
    $producto['Similares'] = isset($producto['Similares']) && is_array($producto['Similares']) ? $producto['Similares'] : [];
    $producto['Lotes'] = isset($producto['Lotes']) && is_array($producto['Lotes']) ? $producto['Lotes'] : [];

    // Normalizar similares para que tambien tengan campos basicos
    foreach ($producto['Similares'] as $k => $sim) {
        $sim['Codigo'] = isset($sim['Codigo']) ? $sim['Codigo'] : '';
        $sim['Nombre'] = isset($sim['Nombre']) ? $sim['Nombre'] : '';
        $sim['Codigo_Cum'] = isset($sim['Codigo_Cum']) ? $sim['Codigo_Cum'] : '';
        $sim['Lote'] = isset($sim['Lote']) ? $sim['Lote'] : '';
        $sim['Fecha_Vencimiento'] = isset($sim['Fecha_Vencimiento']) ? $sim['Fecha_Vencimiento'] : '';
        $sim['Cantidad_Disponible'] = isset($sim['Cantidad_Disponible']) ? $sim['Cantidad_Disponible'] : 0;
        $sim['Cantidad_Formulada'] = isset($sim['Cantidad_Formulada']) ? $sim['Cantidad_Formulada'] : 0;
        $sim['Seleccionado'] = isset($sim['Seleccionado']) ? $sim['Seleccionado'] : "0";
        $sim['Mostrar'] = isset($sim['Mostrar']) ? $sim['Mostrar'] : "0";
        $sim['Buscar'] = isset($sim['Buscar']) ? $sim['Buscar'] : "0";
        $sim['Similares'] = []; // evitar niveles anidados
        $producto['Similares'][$k] = $sim;
    }

    return $producto;
}
