<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idbodega = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$cum = ( isset( $_REQUEST['cum'] ) ? $_REQUEST['cum'] : '' );

$query='SELECT INU.Id_Inventario_Nuevo, 
               INU.Id_Producto,
               INU.Codigo_CUM, 
               INU.Lote, 
               INU.Id_Estiba, 
               E.Id_Bodega_Nuevo, 
               (INU.Cantidad - (INU.Cantidad_Apartada+INU.Cantidad_Seleccionada)- IFNULL( (
                    SELECT 
                         SUM(IC.Cantidad - (IFNULL(IC.Cantidad_Apartada,0)+IFNULL(IC.Cantidad_Seleccionada,0)) )
                          FROM Inventario_Contrato IC
                          WHERE IC.Id_Inventario_Nuevo = INU.Id_Inventario_Nuevo
                          GROUP BY IC.Id_Inventario_Nuevo
                
               
               ),0)) as CantidadDisponible,
               E.Nombre as NombreEstiba
        FROM Inventario_Nuevo INU
        INNER JOIN Estiba E ON INU.Id_Estiba = E.Id_Estiba
        WHERE INU.Cantidad != 0 AND E.Id_Bodega_Nuevo = "'.$idbodega.'" AND INU.Codigo_CUM =  "'.$cum.'"';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contratos = $oCon->getData();
unset($oCon);

echo json_encode($contratos);
?>