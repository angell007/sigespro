<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$idPaciente = ( isset( $_REQUEST['IdPaciente'] ) ? $_REQUEST['IdPaciente'] : '' );
/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
$query = 'SELECT  CONCAT(P.Principio_Activo, " ",P.Presentacion, " ",P.Concentracion, " (",P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " ") as Nombre, 
            D.Id_Dispensacion, 
           (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Pendiente,
           PD.Id_Producto, 
           PD.Id_Inventario_Nuevo as IdInventario,
           PD.Cum, PD.Lote,
           PD.Cantidad_Formulada,
           PD.Cantidad_Entregada,
           PD.Numero_Autorizacion,
           P.Fecha_Vencimiento_Invima as Vencimiento,
           PD.Id_Producto_Dispensacion
FROM Dispensacion D
INNER JOIN Producto_Dispensacion PD
ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Producto P
ON PD.Id_Producto=P.Id_Producto
WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 AND D.Numero_Documento='.$idPaciente ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$listapendientes = $oCon->getData();

unset($oCon);
$i=-1;
foreach($listapendientes as $lista){$i++;
    $prod[$i]["Cantidad"]=$listapendientes[$i]["Cantidad"];
    $prod[$i]["Cum"]=$listapendientes[$i]["Cum"];
    $prod[$i]["IdInventario"]=$listapendientes[$i]["IdInventario"];
    $prod[$i]["Id_Producto"]=$listapendientes[$i]["Id_Producto"];
    $prod[$i]["Lote"]=$listapendientes[$i]["Lote"];
    $prod[$i]["Nombre"]=$listapendientes[$i]["Nombre"];
    $prod[$i]["Precio"]='';
    $prod[$i]["Vencimiento"]=$listapendientes[$i]["Vencimiento"];
    $listapendientes[$i]["producto"]=$prod;
}

echo json_encode($listapendientes);

?>