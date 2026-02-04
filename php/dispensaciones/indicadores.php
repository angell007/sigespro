<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.http_response.php';
include_once '../../class/class.utility.php';

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$colores = ['bg-c-blue', 'bg-info', 'bg-inverse', 'bg-c-pink', 'bg-c-lite-green', 'bg-default', 'bg-facebook'];

$iconos_servicios = ['ti ti-tag', 'ti ti-receipt'];

$condicion = SetCondiciones();

$query = "SELECT S.Nombre AS Servicio,
                r.*
                FROM (
                    SELECT D.Id_Servicio,
                    COUNT(D.Id_Servicio) AS Total_Servicio,
                    COUNT(CASE WHEN D.Pendientes > 0 THEN 1 ELSE NULL END) AS Pendientes,
                    COUNT(CASE WHEN D.Estado_Facturacion = 'Facturada' AND D.Id_Tipo_Servicio != 7 THEN 1 ELSE NULL END) AS Total_Facturadas ,
                    (Select Count(PD.id)  FROM Positiva_Data PD 
                            INNER JOIN Dispensacion D on D.Id_Dispensacion = PD.Id_Dispensacion
                            $condicion and PD.Id_Dispensacion = D.Id_Dispensacion and PD.RLmarcaEmpleador ='Platino' and PD.Anulado !=1) as Platinos,
                    (Select Count(PD.id)  FROM Positiva_Data PD
                            INNER JOIN Dispensacion D on D.Id_Dispensacion = PD.Id_Dispensacion
                            $condicion  and PD.Id_Dispensacion = D.Id_Dispensacion and PD.tieneTutela ='1' and PD.Anulado !=1) as Tutela
                    FROM Dispensacion D $condicion GROUP BY Id_Servicio
                ) r
                INNER JOIN Servicio S ON S.Id_Servicio = r.Id_Servicio";
$indicadores = [];
// echo $query; exit;
$respuesta = [];

$queryObj->SetQuery($query);
$resultado = $queryObj->ExecuteQuery('Multiple');
// echo json_encode($resultado);exit;
$total = array_sum(array_column($resultado, 'Total_Servicio'));
$pendientes = array_sum(array_column($resultado, 'Pendientes'));
$facturadas = array_sum(array_column($resultado, 'Total_Facturadas'));
$platinos = array_sum(array_column($resultado, 'Platinos'));
$tutelas = array_sum(array_column($resultado, 'Tutela'));

$info_adicional = [
    "Titulo" => "Dis. Totales",
    "Total" => $total,
    "class" => "bg-warning",
    "icono" => "fa fa-ticket",
];

array_push($indicadores, $info_adicional);

foreach ($resultado as $i => $value) {
    $info = [
        "Titulo" => "Dis." . $value['Servicio'],
        "Total" => $value['Total_Servicio'],
        "class" => $colores[$i],
        "icono" => $iconos_servicios[$i % 2],
    ];

    $indicadores[] = $info;
}

$info_adicional = [
    "Titulo" => "Dis. Pendientes",
    "Total" => $pendientes,
    "class" => "bg-inverse",
    "icono" => "fa fa-hourglass-end",
];

array_push($indicadores, $info_adicional);

$info_adicional = [
    "Titulo" => "Dis. Facturadas",
    "Total" => $facturadas,
    "class" => "bg-c-pink",
    "icono" => "fa fa-file-text-o",
];

array_push($indicadores, $info_adicional);
$info_adicional = [
    "Titulo" => "Linea Oro",
    "Total" => $platinos,
    "class" => "bg-info",
    "icono" => "fa fa-medkit",
];

array_push($indicadores, $info_adicional);
$info_adicional = [
    "Titulo" => "Tutela",
    "Total" => $tutelas,
    "class" => "bg-primary",
    "icono" => "fa fa-font-awesome",
];

array_push($indicadores, $info_adicional);

echo json_encode($indicadores);

function SetCondiciones()
{

    $condicion = 'WHERE';
    $condiciones = [];

    if ($_REQUEST['id_punto']) {
        array_push($condiciones, "D.Id_Punto_Dispensacion=$_REQUEST[id_punto]");
    }
    if ($_REQUEST['tipo'] && $_REQUEST['tipo']!='todos') {
        array_push($condiciones, "D.Id_Tipo_Servicio=$_REQUEST[tipo]");
    }
    if ($_REQUEST['est']) {
        array_push($condiciones, "D.Estado_Dispensacion='$_REQUEST[est]'");
    }
    if ($_REQUEST['fact']) {
        array_push($condiciones, "D.Estado_Facturacion='$_REQUEST[fact]'");
    }
    if ($_REQUEST['cod']) {
        array_push($condiciones, "D.Codigo like '%$_REQUEST[cod]%'");
    }

    if ($_REQUEST['fecha']) {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);

        array_push($condiciones, "DATE_FORMAT(D.Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'");
    }

    $condicion = count($condiciones) > 0 ? "$condicion " . implode(" AND ", $condiciones) : '';

    if ($condicion == '') {
        $condicion = " WHERE DATE(D.Fecha_Actual)=CURRENT_DATE()";
    }

    return $condicion;
}

function GetQueryConteoTotal()
{
    global $condicion;

    $query = 'SELECT COUNT(*) as Total, "bg-c-yellow" as class , "fa fa-ticket" as icono, "Total Dis" as Titulo FROM Dispensacion'
        . $condicion;

    return $query;
}

function GetServicios()
{
    global $queryObj;

    $query = 'SELECT Nombre as Servicio, Id_Servicio FROM Servicio WHERE Estado="Activo" ';
    $queryObj->SetQuery($query);
    $servicios = $queryObj->ExecuteQuery('Multiple');

    return $servicios;
}

function GetQueryServicios($value, $pos)
{
    global $condicion, $colores, $iconos_servicios;

    $pos_icono = $pos % 2;

    $query = "SELECT COUNT(*) as Total, '$colores[$pos]' as class ,' $iconos_servicios[$pos_icono]' as icono, 'Dis. $value[Servicio]' as Titulo FROM Dispensacion"
        . $condicion . " AND Id_Servicio =$value[Id_Servicio] ";

    return $query;
}
