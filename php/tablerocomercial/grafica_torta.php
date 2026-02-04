<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT SUM(Valor_Medicamento) AS Meta_Medicamento, SUM(Valor_Material) AS Meta_Material FROM Meta_Cliente";

$oCon = new consulta();
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$resultado = [
    [
        "Bodega" => "Medicamentos",
        "Valor" => $datos['Meta_Medicamento']
    ],
    [
        "Bodega" => "Materiales",
        "Valor" => $datos['Meta_Material']
    ]
];

echo json_encode($resultado);

