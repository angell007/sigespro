<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$Id_Contrato = ( isset( $_REQUEST['Id_Contrato'] ) ? $_REQUEST['Id_Contrato'] : '' );
$Id_Producto = ( isset( $_REQUEST['Id_Producto'] ) ? $_REQUEST['Id_Producto'] : '' );

$query='SELECT INU.Lote, 
                P.Id_Producto, 
                IC.Id_Inventario_Contrato, 
                Cum, 
                IC.Id_Inventario_Nuevo, 
                SUM(IC.Cantidad - (IFNULL(IC.Cantidad_Apartada,0)+IFNULL(IC.Cantidad_Seleccionada,0)) ) as CantidadDisponibleContrato 
            FROM Inventario_Contrato IC
            INNER JOIN Producto_Contrato PR ON IC.Id_Producto_Contrato = PR.Id_Producto_Contrato 
            INNER JOIN Producto P ON P.Codigo_Cum = PR.Cum
            INNER JOIN Inventario_Nuevo INU ON IC.Id_Inventario_Nuevo = INU.Id_Inventario_Nuevo
            WHERE IC.Id_Contrato = '.$Id_Contrato.' AND P.Id_Producto = '.$Id_Producto.'
            GROUP BY INU.Id_Inventario_Nuevo';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contratos = $oCon->getData();
unset($oCon);

echo json_encode($contratos);

