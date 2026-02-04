<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : false );
$codigo1=substr($codigo,0,12);

$query = 'SELECT  I.Id_Producto FROM Inventario_Inicial I
        INNER JOIN Producto P
        ON I.Id_Producto=P.Id_Producto
        WHERE I.Codigo='.$codigo1.' OR P.Codigo_Barras='.$codigo.' OR I.Alternativo LIKE "%'.$codigo1.'%"';

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$productos = [];

if ($resultado) {
        $query = 'SELECT  P.Nombre_Comercial,I.Id_Inventario_Inicial, I.Lote, I.Cantidad , I.Fecha_Vencimiento,
        IF(CONCAT( P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, " (", P.Nombre_Comercial,") ",
                P.Cantidad," ",
                P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )="" OR CONCAT( P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, " (", P.Nombre_Comercial,") ",
                P.Cantidad," ",
                P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ) IS NULL, CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial), CONCAT( P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, " (", P.Nombre_Comercial,") ",
                P.Cantidad," ",
                P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )) as Nombre, P.Nombre_Comercial, P.Laboratorio_Comercial, P.Id_Producto, P.Embalaje FROM Inventario_Inicial I
                INNER JOIN Producto P
                ON I.Id_Producto=P.Id_Producto
                WHERE I.Id_Producto='.$resultado['Id_Producto'].'
                Order BY I.Fecha_Vencimiento ASC';
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);    
}





echo json_encode($productos);

?>