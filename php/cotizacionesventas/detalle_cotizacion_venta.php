<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT 
                CV.Fecha_Documento as Fecha , CV.Observacion_Cotizacion_Venta as observacion, CV.Codigo as Codigo, CV.Fecha_Documento_Edicion as FechaEdicion,
                CV.Condiciones_Comerciales, CV.Codigo_Qr,
                CV.Estado_Cotizacion_Venta as Estado,
                C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, M.Nombre as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente,
                LG.Nombre as NombreGanancia, LG.Porcentaje as PorcentajeGanancia, LG.Id_Lista_Ganancia as IdLG,
                B.Nombre as NombreBodega , B.Id_Bodega as IdBodega
            FROM Cotizacion_Venta CV, Cliente C , Lista_Ganancia LG, Bodega B, Municipio M
            WHERE  CV.Id_Lista_Ganancia = LG.Id_Lista_Ganancia 
            AND CV.Id_Lista_Ganancia = LG.Id_Lista_Ganancia 
            AND CV.Id_Cliente = C.Id_Cliente 
            AND M.Id_Municipio = C.Ciudad
            AND CV.Id_Cotizacion_Venta = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query2 = "SELECT 
            P.Nombre_Comercial, 
            S.Nombre AS Subcategoria,
            P.Laboratorio_Comercial, 
            P.Laboratorio_Generico, 
            IFNULL(CONCAT(P.Principio_Activo, ' ', P.Presentacion, ' ', P.Concentracion, P.Cantidad,' ', P.Unidad_Medida), concat_WS('', P.Nombre_Comercial, ' ',   '(',P.Cantidad , ' ', P.Presentacion,')' )) as producto, 
            P.Id_Producto,
            P.Codigo_Cum as Cum,
            P.Codigo_Cum,
            P.Embalaje, 
            P.Id_Producto as Id_Producto, 
            P.Cantidad_Presentacion, 
            P.Invima, 
            (CASE WHEN P.Gravado='Si' THEN '19%' ELSE '0%' END) AS Impuesto,
            PCV.Cantidad as Cantidad, 
            PCV.Descuento,
            PCV.Observacion,
            PCV.Precio_Venta as Precio_Venta,
            PCV.Iva, PCV.Subtotal as Subtotal,
            PCV.Id_Producto_Cotizacion_Venta as idPcv, 
            ifnull(PRG.Precio_Venta, -1) as Precio_Regulado,
            ifnull(PLG.Precio, -1) as Precio_Lista
        FROM Producto P 
        INNER JOIN Subcategoria S ON S.Id_Subcategoria = P.Id_Subcategoria
        INNER JOIN Producto_Cotizacion_Venta PCV on P.Id_Producto=PCV.Id_Producto
        Left Join Producto_Lista_Ganancia PLG on PLG.Cum = P.Codigo_Cum and PLG.Id_Lista_Ganancia = '$dis[IdLG]'
        LEFT JOIN Precio_Regulado PRG on PRG.Codigo_Cum = P.Codigo_Cum
        WHERE PCV.Id_Cotizacion_Venta =  $id" ;


$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$query="SELECT AC.*, F.Imagen,CONCAT_WS(' ',F.Nombres, F.Apellidos) as Funcionario
FROM Actividad_Cotizacion AC
INNER JOIN Funcionario F On AC.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Cotizacion_Venta C ON AC.Id_Cotizacion=C.Id_Cotizacion_Venta
WHERE AC.Id_Cotizacion=$id


Order BY Fecha ASC, Id_Actividad_Cotizacion ASC";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades = $oCon->getData();

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;
$resultado["Actividades"]=$actividades;

echo json_encode($resultado);


?>