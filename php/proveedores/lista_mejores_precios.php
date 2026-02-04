<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cum = ( isset( $_REQUEST['Cum'] ) ? $_REQUEST['Cum'] : '' );

if ($cum) {
    $query = 'SELECT PR.Nombre_Comercial,  CONCAT(PR.Nombre_Comercial," ",PR.Laboratorio_Comercial) as Nombre, PR.Laboratorio_Generico,PR.Laboratorio_Comercial, PV.Nombre as NombreProveedor, LPP.Precio, LPP.Ultima_Actualizacion
                FROM lista_precio_proveedor LPP
                INNER JOIN proveedor PV ON LPP.Id_Proveedor = PV.Id_Proveedor
                INNER JOIN producto PR ON LPP.Cum = PR.Codigo_Cum
                WHERE LPP.Cum = "'.$cum.'"
                ORDER BY LPP.Precio_Anterior LIMIT 3';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado["Listado"] = $oCon->getData();
    unset($oCon);
}

echo json_encode($resultado);
