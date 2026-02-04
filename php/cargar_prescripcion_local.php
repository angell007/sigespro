<?php
// Inserta en BD una prescripción MiPres desde un archivo JSON local (sin validar municipio).
// Archivo esperado: tmp/prescripcion_20251125157002805042.json
// Ejecutar desde la raíz del proyecto:
//   php php/cargar_prescripcion_local.php

header('Content-Type: text/plain');

include_once __DIR__ . '/../class/class.querybasedatos.php';
include_once __DIR__ . '/../class/class.complex.php';

$jsonPath = __DIR__ . '/../tmp/prescripcion_20251125157002805042.json';
$data = json_decode(file_get_contents($jsonPath), true);

if (!$data) {
    echo "No se pudo leer el JSON en $jsonPath\n";
    exit(1);
}

// Resuelve Id_Producto para CUM 139 y TipoTec 'S'
$q = new consulta();
$q->setQuery(
    "SELECT Id_Producto
     FROM Producto_Tipo_Tecnologia_Mipres PD
     JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres
     WHERE (Codigo_Actual='139' OR Codigo_Anterior='139') AND M.Codigo='S'
     LIMIT 1"
);
$p = $q->getData();
$idProd = $p['Id_Producto'] ?? null;

if (!$idProd) {
    echo "Sin Id_Producto para CUM 139 / TipoTec S\n";
    exit(1);
}

foreach ($data as $dis) {
    // Cabecera Dispensacion_Mipres
    $disp = $dis;
    $disp['Fecha'] = date('Y-m-d H:i:s');
    $disp['Id_Paciente'] = $dis['NoIDPaciente'];
    $disp['Fecha_Maxima_Entrega'] = $dis['FecMaxEnt'];
    $disp['Numero_Entrega'] = $dis['NoEntrega'];
    $disp['Fecha_Direccionamiento'] = $dis['FecDireccionamiento'];
    $disp['Id_Servicio'] = 1;
    $disp['Id_Tipo_Servicio'] = 3;
    $disp['Codigo_Municipio'] = $dis['CodMunEnt'];
    $disp['Tipo_Tecnologia'] = $dis['TipoTec'];

    $o = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres');
    foreach ($disp as $k => $v) {
        $o->$k = $v;
    }
    $o->save();
    $idDis = $o->getId();
    unset($o);

    // Detalle Producto_Dispensacion_Mipres
    $dis['Codigo_Cum'] = '139';
    $dis['Id_Producto'] = $idProd;
    $dis['Id_Dispensacion_Mipres'] = $idDis;
    $dis['Cantidad'] = $dis['CantTotAEntregar'];

    $d = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres');
    foreach ($dis as $k => $v) {
        $d->$k = $v;
    }
    $d->save();
    unset($d);

    echo "Importado IDDireccionamiento {$dis['IDDireccionamiento']} -> Dispensacion {$idDis}\n";
}
