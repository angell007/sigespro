<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : null;
$cantidad = isset($_REQUEST['cantidad']) ? $_REQUEST['cantidad'] : null;
$id_dis = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

if( $codigo && $cantidad && !$id_dis){
    $id_dis = get_Id_Dispensacion($codigo, $cantidad);
}


$fecha_actual = date('Y-m-d H:i:s');
// echo "ok"; exit;

$query = "SELECT * from Producto_Dispensacion PD Where PD.Cantidad_Formulada != PD.Cantidad_Entregada and PD.Id_Dispensacion = $id_dis";
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);

$productos = $oCon->getData();
try {

    if (count($productos) > 0) {
        $disp = new complex('Dispensacion', 'Id_Dispensacion', $id_dis);
        $disAnt = $disp->getData();

        $aud = new complex('Auditoria', 'Id_Dispensacion', $id_dis);
        $auditoria = $aud->getData();
        unset($aud);
        $pos_data = new complex('Positiva_Data', 'Id_Dispensacion', $id_dis);
        $positiva = $pos_data->getData();
        unset($pos_data);

        unset($disAnt['Id_Dispensacion'], $disAnt['Acta_Entrega'], $disAnt['Estado_Facturacion'], $disAnt['Id_Factura'], $disAnt['Id_Dispensacion_Pendientes'], $disAnt['Pendientes']);
        $disNueva = new complex('Dispensacion', 'Id_Dispensacion');
        foreach ($disAnt as $key => $value) {
            if ($value != '') {
                $disNueva->$key = $value;
            }

        }

        $consecutivoPen=getConsecutivoPendientes();
        $auditoria['Id_Auditoria'] ? $disNueva->Id_Auditoria = $auditoria['Id_Auditoria'] : '';
        $positiva['id'] ? $disNueva->Id_Positiva_Data = $positiva['id'] : '';
        $disNueva->Fecha_Actual = $fecha_actual;
        $disNueva->Identificacion_Funcionario = "12345";
        $disNueva->Estado = "Activo";
        $disNueva->Codigo_Qr = "";
        $disNueva->Codigo = $consecutivoPen;
        $disNueva->save();
        $id_pendientes = $disNueva->getId();

        $disp->Id_Dispensacion_Pendientes = $id_pendientes;
        $disp->Pendientes = 0;
        $disp->save();

        foreach ($productos as $producto) {
            $cant_Pendiente = $producto['Cantidad_Formulada'] - $producto['Cantidad_Entregada'];

            $prodAnt = new complex('Producto_Dispensacion', 'Id_Producto_Dispensacion', $producto['Id_Producto_Dispensacion']);
            $prodAnt->Cantidad_Formulada = $producto['Cantidad_Entregada'];
            $prodAnt->save();

            $eliminar_atributos = [
                'Cantidad_Formulada',
                'Id_Dispensacion',
                'Id_Producto_Dispensacion',
                'Lote',
                'Costo',
                'Id_Inventario_Nuevo_Seleccionados',
                'Id_Inventario_Nuevo',
                'Cantidad_Entregada',
                'Fecha_Carga',
            ];

            foreach ($eliminar_atributos as $atrib) {
                unset($producto[$atrib]);
            }

            $oItem = new complex('Producto_Dispensacion', 'Id_Producto_Dispensacion');
            foreach ($producto as $key => $value) {
                if ($value != "") {
                    $oItem->$key = $value;
                }
            }
            $oItem->Cantidad_Formulada = $cant_Pendiente;
            $oItem->Cantidad_Formulada_Total = $cant_Pendiente;
            $oItem->Cantidad_Entregada = 0;
            $oItem->Fecha_Carga = $fecha_actual;
            $oItem->Id_Dispensacion = $id_pendientes;
            $oItem->save();
        }

        $activ = new complex('Actividades_Dispensacion', 'Id_Actividades_Dispensacion');
        $activ->Id_Dispensacion = $id_pendientes;
        $activ->Identificacion_Funcionario = "12345";
        $activ->Detalle = "Dispensacion creada de manera automática por pendientes de la dispensacion $disAnt[Codigo]";
        $activ->Estado = "Creado";
        $activ->Fecha = $fecha_actual;
        $activ->save();

        $activ = new complex('Actividades_Dispensacion', 'Id_Actividades_Dispensacion');
        $activ->Id_Dispensacion = $id_dis;
        $activ->Identificacion_Funcionario = "12345";
        $activ->Detalle = "Creada dispensacion pendiente $consecutivoPen";
        $activ->Estado = "Creado";
        $activ->Fecha = $fecha_actual;
        $activ->save();

    }
    $respuesta['success'] = true;
    echo json_encode($respuesta);
} catch (\Throwable $th) {
    echo $th->getMessage();
}

function getConsecutivoPendientes()
{
    $query = "SELECT ifnull(MAX(CAST(REPLACE(D.Codigo, 'PEN', '') AS DECIMAL) ), 0)+1 AS Consecutivo
      FROM Dispensacion D WHERE D.Codigo LIKE 'PEN%'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $consecutivo = $oCon->getData()['Consecutivo'];
    return "PEN$consecutivo";
}
function get_Id_Dispensacion($codigo, $cantidad){
    $oItem = new complex('Dispensacion', 'Codigo', $codigo, 'str');
    $dis= $oItem->getData();
    $id_dis = $dis['Id_Dispensacion'];

    $query = "UPDATE Producto_Dispensacion SET Cantidad_Formulada = Cantidad_Formulada + $cantidad WHERE Id_Dispensacion = $id_dis";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->getData();
    return $id_dis;
    
}
