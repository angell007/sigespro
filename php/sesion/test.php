<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: cookie, cookies');
date_default_timezone_set('America/Bogota');

session_start();
echo json_encode($_COOKIE); 
// echo json_encode( $_SESSION );
exit;

include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$ids = $_REQUEST['id'] ? $_REQUEST['id'] : '';
$cum = $_REQUEST['cum'] ? $_REQUEST['cum'] : '';

if($ids!=''){
    $cums=getCums( getCumsByIds($ids));    
}

if ($cum != '') {
    $cums= getCums($cum);
    $ids = getIdsByCum($cums);
}


$ids = str_replace(',', ', ', $ids);
$query1 = getTablasByCum($cums);
$oCon = new consulta();
$oCon->setQuery($query1);
$oCon->setTipo('Multiple');
$lista1 = $oCon->getData();
unset($oCon);
$encabezado1 = ($lista1[0]);

$query = getQuery($ids);
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
$encabezado = ($lista[0]);

$contenido = "<div  style='display: flex;flex-direction: row;flex-wrap: wrap;'>
<div class='col' style='width: 50%;'>
$ids
<table border='1' style='border-collapse: collapse;'>
    <thead>
        <tr>";
    foreach ($encabezado as $nombre => $value) {
        $contenido .= "<th>$nombre</th>";
    }
    $contenido .= '</tr>';

    $contenido .= '
        </thead>
        <tbody>';

    foreach ($lista as $key => $value) {
        $contenido .= '<tr>';
        foreach ($value as $valor) {
            $contenido .= "<td>$valor</td>";
        }
        $contenido .= '</tr>';
    }

    $contenido .= "
    </tbody>
</table>
</div>
<div class='col' style='width:50%'>
$cums
<table border='1' style='border-collapse: collapse;'>
    <thead>
        <tr>";
    foreach ($encabezado1 as $nombre => $value) {
        $contenido .= "<th>$nombre</th>";
    }
    $contenido .= '</tr>';

    $contenido .= '
        </thead>
        <tbody>';

    foreach ($lista1 as $key => $value) {
        $contenido .= '<tr>';
        foreach ($value as $valor) {
            $contenido .= "<td>$valor</td>";
        }
        $contenido .= '</tr>';
    }

    $contenido .= '
    </tbody>
</table>
</div>
</div>

';

echo $contenido;
function getQuery($ids)
{
    $tablas = "Actividad_Ajuste_Inventario,Actividad_Producto,Costo_Promedio,Costo_Promedio_Temp,Descripcion_Inventario_Valorizado,Historial_Inventario,Historial_Inventario_Punto,Importacion,Inventario_Nuevo,Inventario_Temp,Inventario_Viejo,Producto,Producto3,Producto_Acta_Recepcion,Producto_Acta_Recepcion_Internacional,Producto_Acta_Recepcion_Remision,Producto_Ajuste_Individual,Producto_Auditoria,Producto_Cohorte,Producto_Contrato,Producto_Control_Cantidad,Producto_Cotizacion_Venta,Producto_Descarga_Pendiente_Remision,Producto_Devolucion_Compra,Producto_Devolucion_Interna,Producto_Dispensacion,Producto_Dispensacion_Mipres,Producto_Doc_Inventario_Auditable,Producto_Doc_Inventario_Fisico,Producto_Doc_Inventario_Fisico_Punto,Producto_Factura,Producto_Factura_Venta,Producto_Grupo_Materiales,Producto_Inventario_Fisico,Producto_Inventario_Fisico_Punto,Producto_Movimiento_Vencimiento,Producto_Nacionalizacion_Parcial,Producto_Nota_Credito,Producto_Nota_Credito_Global,Producto_No_Conforme,Producto_No_Conforme_Internacional,Producto_No_Conforme_Remision,Producto_Orden_Compra_Internacional,Producto_Orden_Compra_Nacional,Producto_Orden_Pedido,Producto_Pendientes_Remision,Producto_Pre_Compra,Producto_Remision,Producto_Remision_Antigua,Producto_Tipo_Tecnologia_Mipres,Remision_Callcenter,Reporte_Inventario,Saldo_Inicial_Kardex,Z_Producto_Documento_Inventario_Fisico_Nuevo";
    $tablas = explode(",", $tablas);
    $productos = $ids;
    $sql = [];
    foreach ($tablas as $tabla) {
        $col = '';
        switch ($tabla) {
            case 'Costo_Promedio_Temp':
                $col = 'Id_Costo_Promedio';
                break;
            case 'Inventario_Temp':
                $col = 'Id_Inventario_Nuevo';
                break;
            case 'Inventario_Viejo':
                $col = 'Id_Inventario';
                break;
            case 'Producto3':
                $col = 'Id_Producto';
                break;
            case 'Producto_Doc_Inventario_Auditable':
                $col = 'Id_Producto_Doc_Inventario_Fisico';
                break;
            case 'Producto_Inventario_Fisico_Punto':
                $col = 'Id_Producto_Inventario_Fisico';
                break;
            case 'Z_Producto_Documento_Inventario_Fisico_Nuevo':
                $col = 'Id_Producto_Documento_Inventario_Fisico';
                break;
            default:
                $col = "Id_$tabla";
                break;
        }
        $query = "SELECT $col as Id_Tabla ,Id_Producto, '$tabla' as Tabla from $tabla Where Id_Producto in($productos) \n";
        array_push($sql, $query);
    }
    $query = "SELECT Id_Producto_Asociado as Id_Tabla ,Id_Producto, 'Producto_Asociado' as Tabla from Producto_Asociado PA
            INNER JOIN Producto P ON CONCAT('-',REPLACE(REPLACE(PA.Producto_Asociado, ',', '-'), ' ', ''), '-') LIKE CONCAT('%-',P.Id_Producto,'-%')
            WHERE P.Id_Producto IN ($productos) \n";
        array_push($sql, $query);
    $consulta = "(" . implode(")union all(", $sql) . ")";
    return $consulta;
}

function getIdsByCum($cum)
{
    $query = "SELECT Codigo_Cum, group_concat(Id_Producto)as ID FROM Producto WHERE Codigo_Cum IN ($cum) ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $lista = $oCon->getData();
    return $lista['ID'];
}
function getCumsByIds($ids)
{
    

    $query = "SELECT group_concat( distinct Codigo_Cum)as cums FROM Producto WHERE Id_Producto in($ids) ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $lista = $oCon->getData();
    return $lista['cums'];
}


function getTablasByCum($cums)
{

    $tablas = "Bodega_Inicial,Lista_Precio_Proveedor,Precio_Regulado,Producto_Cohorte_Temp,Producto_Evento,Producto_Evento_Temporal,Producto_Lista_Ganancia,Producto_NoPos,Producto_No_Encontrados,Temporal_Factura_Rem,Z_Cum_Excluidos,Z_Factura_Vieja,Z_Mipres_Reportar";
    $tablas = explode(",", $tablas);
    $productos = $cums;
    $sql = [];
    foreach ($tablas as $tabla) {
        $col = '';
        switch ($tabla) {
            case 'Bodega_Inicial':
                $col = 'Id_Bodega_Inicial';
                $col_Cum = 'Cum';
                
                break;
            case 'Lista_Precio_Proveedor':
                $col = 'Id_Lista_Precio';
                $col_Cum = 'Cum';
                break;
            case 'Producto_Lista_Ganancia':
                $col = 'Id_Producto_Lista_Ganancia';
                $col_Cum = 'Cum';
                break;
            case 'Producto_NoPos':
                $col = 'Id_Producto_NoPos';
                $col_Cum = 'Cum';
                break;
            case 'Z_Cum_Excluidos':
                $col = 'Id_Cum_Excluidos';
                $col_Cum = 'Cum';
                break;
            case 'Z_Factura_Vieja':
                $col = 'Id_Factura_Vieja';
                $col_Cum = 'Cum';
                break;
            case 'Z_Mipres_Reportar':
                $col = 'Id_Mipres_Reportar';
                $col_Cum = 'CUM';
                break;
           
            default:
                $col = "Id_$tabla";
                $col_Cum = 'Codigo_Cum';
                break;
        }
        $query = "SELECT $col as Id_Tabla,  $col_Cum as 'Cum', '$tabla' as Tabla from $tabla Where $col_Cum in($productos) ";
        array_push($sql, $query);
    }
    
    $consulta = "(" . implode(")union all(", $sql) . ")";
    return $consulta;
}

function getCums($cums = ''){
    $cum = array_map(function($cum){
            $cum1 = explode('-', $cum);
            $cum1[0]? $cum1[0] = (int)$cum1[0] > 0 ? (int)$cum1[0] : $cum1[0] : '';
            $cum1[1] ? $cum1[1] = (int)$cum1[1] > 0 ? (int)$cum1[1] : $cum1[1] : '';
            $cum2 = implode('-', $cum1);
            return "'$cum2'";
        }, explode(',',$cums));
    
    $cums = array_map(function($cum){
        return "'$cum'";
    }, explode(',', $cums));
    
    $cums = implode(",", array_merge($cums, $cum)) ;
    return $cums;
}
?>
