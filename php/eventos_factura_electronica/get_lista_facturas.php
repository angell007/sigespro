<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta_paginada.php';

$codigo = (isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '');
$fechas = (isset($_REQUEST['fechas']) ? $_REQUEST['fechas'] : '');
$proveedor = (isset($_REQUEST['proveedor']) ? $_REQUEST['proveedor'] : '');
$cufe = (isset($_REQUEST['Cufe']) ? $_REQUEST['Cufe'] : '');
// $id = (isset($_REQUEST['id_documento']) ? $_REQUEST['id_documento'] : '');
$pag = (isset($_REQUEST['pag']) ? $_REQUEST['pag'] : 1);
$tam = (isset($_REQUEST['tam']) ? $_REQUEST['tam'] : 20);
$estado_aceptacion = (isset($_REQUEST['Estado_Aceptacion']) ? $_REQUEST['Estado_Aceptacion'] : '');
$acuse_servicio = (isset($_REQUEST['Acuse_Servicio_Codigo']) ? $_REQUEST['Acuse_Servicio_Codigo'] : '');
$acuse_factura = (isset($_REQUEST['Acuse_Factura_Codigo']) ? $_REQUEST['Acuse_Factura_Codigo'] : '');


$limit = ($pag - 1) * $tam;

$query = getQuery();
$oCon = new consulta();
$oCon->setQuery($query . " LIMIT $limit, $tam");
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);



$respuesta['Facturas'] = $facturas['data'];
$respuesta['TotalItems'] = $facturas['total'];

echo json_encode($respuesta);


function getQuery()
{
    global $codigo, $fechas, $proveedor, $id, $cufe, $estado_aceptacion, $acuse_servicio, $acuse_factura;

    $condiciones = [];

    if ($codigo != '') {
        array_push($condiciones, "F.Codigo_Factura like '%$codigo%'");
    }
    if ($fechas != '') {
        $fechas = str_replace(" - ", " 00:00:00' AND '", $fechas) . ' 23:59:00';
        array_push($condiciones, "F.Fecha_Factura BETWEEN '$fechas'");
    }
    if ($id != '') {
        array_push($condiciones, "F.Id_Factura_Recibida ='$id'");
    }
    if ($cufe != '') {
        array_push($condiciones, "F.Cufe like '%$cufe%'");
    }
    if ($acuse_factura != '') {
        if ($acuse_factura == 'pend') {
            array_push($condiciones, "ARF.Acuse_Factura_Codigo IS NULL");
        } else
            array_push($condiciones, "ARF.Acuse_Factura_Codigo IS NOT NULL");
    }
    if ($acuse_servicio != '') {
        if ($acuse_servicio == 'pend') {
            array_push($condiciones, "ARBS.Acuse_Servicio_Codigo IS NULL");
        } else
            array_push($condiciones, "ARBS.Acuse_Servicio_Codigo IS NOT NULL");
    }
    if ($proveedor != '') {
        $proveedor = str_replace(" ", "%", $proveedor);
        $having = "HAVING Proveedor like '%$proveedor%'";
    }
    switch ($estado_aceptacion) {
        case 'expresa':
            array_push($condiciones, "AEF.Aceptacion_Factura_Codigo IS NOT NULL");
            break;
        case 'tacita':
            array_push($condiciones, "F.Factura_Aceptada IS NOT NULL");
            break;
        case 'rechazo':
            array_push($condiciones, "RF.Rechazo_Codigo IS NOT NULL");
            break;
        case 'pend':
            array_push($condiciones, "AEF.Aceptacion_Factura_Codigo IS NULL");
            array_push($condiciones, "F.Factura_Aceptada IS NULL");
            array_push($condiciones, "RF.Rechazo_Codigo IS NULL");
            break;
        default:
            break;
    }

    $condiciones = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : '';

    $query = "SELECT 
    SQL_CALC_FOUND_ROWS
    IF(P.Tipo='Juridico', P.Razon_Social, COALESCE(P.Nombre, CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido))) AS Proveedor,
    ARF.*,
    AEF.*, 
    ARBS.*, 
    RF.*, 
    F.* , 
    IFNULL(F.Factura_Aceptada, AEF.Aceptacion_Factura_Funcionario) as Aceptacion_Factura_Funcionario,
    IFNULL(F.Factura_Aceptada, AEF.Aceptacion_Factura_Codigo) as Aceptacion_Factura_Codigo
    FROM Factura_Recibida F
    INNER JOIN Proveedor P ON P.Id_Proveedor = F.Id_Proveedor
    LEFT JOIN
    ( 	SELECT ARF.Codigo AS Acuse_Factura_Codigo,
        ARF.Cude AS Acuse_Factura_Cude,
        ARF.Procesada AS Acuse_Factura_Procesada,
        CONCAT_WS(' ', FARF.Nombres, FARF.Apellidos) AS Acuse_Factura_Funcionario,
        ARF.Fecha AS Acuse_Factura_Fecha ,
        ARF.Id_Factura_Recibida as Id_Seguimiento
        FROM  Acuse_Recibo_Factura ARF 
        INNER JOIN Funcionario FARF ON FARF.Identificacion_Funcionario = ARF.Identificacion_Funcionario
    )ARF ON ARF.Id_Seguimiento = F.Id_Factura_Recibida
    LEFT JOIN
    ( 	SELECT       
        ARBS.Fecha AS Acuse_Servicio_Fecha,
        ARBS.Codigo AS Acuse_Servicio_Codigo,
        ARBS.Cude AS Acuse_Servicio_Cude,
        CONCAT_WS(' ', FARBS.Nombres, FARBS.Apellidos) AS Acuse_Servicio_Funcionario,
        ARBS.Procesada AS Acuse_Servicio_Procesada,
        ARBS.Id_Factura_Recibida as Seguimiento_Id
        FROM  Acuse_Recibo_Bien_Servicio ARBS 
        INNER JOIN Funcionario FARBS ON FARBS.Identificacion_Funcionario = ARBS.Identificacion_Funcionario
    )ARBS ON ARBS.Seguimiento_Id = F.Id_Factura_Recibida
      
    LEFT JOIN
    ( 	SELECT       
        AEF.Fecha AS Aceptacion_Factura_Fecha,
        AEF.Codigo AS Aceptacion_Factura_Codigo,
        AEF.Cude AS Aceptacion_Factura_Cude,
        CONCAT_WS(' ', FAEF.Nombres, FAEF.Apellidos) AS Aceptacion_Factura_Funcionario,
        AEF.Procesada AS Aceptacion_Factura_Procesada,
        AEF.Id_Factura_Recibida as Id_Seg
        FROM  Aceptacion_Expresa_Factura AEF
        INNER JOIN Funcionario FAEF ON FAEF.Identificacion_Funcionario = AEF.Identificacion_Funcionario
    )AEF ON AEF.Id_Seg = F.Id_Factura_Recibida
    
    LEFT JOIN
    ( 	SELECT       
        RF.Fecha AS Rechazo_Fecha,
        RF.Codigo AS Rechazo_Codigo,
        RF.Cude AS Rechazo_Cude,
        CONCAT_WS(' ', FRF.Nombres, FRF.Apellidos) AS Rechazo_Funcionario,
        RF.Procesada AS Rechazo_Procesada,
        RF.Id_Factura_Recibida as Seguimiento
        FROM Rechazo_Factura RF 
        INNER JOIN Funcionario FRF ON FRF.Identificacion_Funcionario = RF.Identificacion_Funcionario
    )RF ON RF.Seguimiento = F.Id_Factura_Recibida
    $condiciones
    $having
    ORDER BY  F.Fecha_Factura DESC  , F.Id_Factura_Recibida DESC
    ";

    return "$query";
}
