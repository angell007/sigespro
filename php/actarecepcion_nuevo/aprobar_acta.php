<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.costo_promedio.php';

$id_acta_recepcion = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
$costos = isset($_REQUEST['costos']) ? $_REQUEST['costos'] : false;
// echo json_encode($_REQUEST); exit;

if ($id_acta_recepcion) {

  
    //Consultar el codigo del acta y el id de la orden de compra
    $query_codido_acta = 'SELECT
                            Codigo,
                            Id_Orden_Compra_Nacional
                        FROM
                            Acta_Recepcion
                        WHERE
                            Id_Acta_Recepcion = ' . $id_acta_recepcion;

    $oCon = new consulta();
    $oCon->setQuery($query_codido_acta);
    $acta_data = $oCon->getData();
    unset($oCon);

    aprobarActa($id_acta_recepcion, $costos);

    //actualizar costo promedio Y Listas de ganancias por cada producto
    if ($costos == "si") {
        actualizarCostoPromedio($id_acta_recepcion);
    }

    //Guardando paso en el seguimiento del acta en cuestion
    guardarActividad($id_acta_recepcion);

    if ($acta_data) {
        $resultado['mensaje'] = "Se ha aprobado e ingresado correctamente el acta al inventario";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Operación Exitosa";
    } else {
        $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor intentelo de nuevo";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error";
    }

    echo json_encode($resultado);

}

function actualizarCostoPromedio($id_acta_recepcion)
{
    $query = "SELECT PA.Id_Producto, SUM(PA.Cantidad) AS Cantidad, PA.Precio,
         P.Codigo_Cum AS Cum
        FROM Producto_Acta_Recepcion PA
        INNER JOIN Producto P ON P.Id_Producto= PA.Id_Producto
        WHERE Id_Acta_Recepcion = $id_acta_recepcion
         GROUP BY P.Id_Producto";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oItem);

    foreach ($productos as $producto) {
        # code...
        $costopromedio = new Costo_Promedio($producto["Id_Producto"], $producto["Cantidad"], $producto["Precio"]);
        $costopromedio->actualizarCostoPromedio();
        unset($costopromedio);

        actualizarListaGanancia($producto);
    }

}

function guardarActividad($id_acta_recepcion)
{
    global $funcionario, $acta_data;
    $oItem = new complex('Actividad_Orden_Compra', "Id_Acta_Recepcion_Compra");
    $oItem->Id_Orden_Compra_Nacional = $acta_data['Id_Orden_Compra_Nacional'];
    $oItem->Id_Acta_Recepcion_Compra = $id_acta_recepcion;
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Detalles = "Se aprobo y se ingreso el Acta con codigo " . $acta_data['Codigo'];
    $oItem->Fecha = date("Y-m-d H:i:s");
    $oItem->Estado = 'Aprobacion';
    $oItem->save();
    unset($oItem);
}

function aprobarActa($id_acta_recepcion, $costos)
{

    $oItem = new complex('Acta_Recepcion', 'Id_Acta_Recepcion', $id_acta_recepcion);
    $oItem->Estado = 'Aprobada';
    $oItem->Afecta_Costo = $costos;
    $oItem->save();

    unset($oItem);
}

function actualizarListaGanancia($item)
{
    // ACA SE AGREGA EL PRODUCTO A LA LISTA DE GANANCIA
    $query = 'SELECT LG.Porcentaje, LG.Id_Lista_Ganancia FROM Lista_Ganancia LG';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $porcentaje = $oCon->getData();
    unset($oCon);
    //datos
    $cum_producto = $item['Cum'];
    foreach ($porcentaje as $value) {
        $query = 'SELECT * FROM Producto_Lista_Ganancia WHERE Cum="' . $cum_producto . '" AND Id_lista_Ganancia=' . $value['Id_Lista_Ganancia'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $cum = $oCon->getData();
        unset($oCon);
        $precio_entrada = number_format($item['Precio'] / ((100 - $value['Porcentaje']) / 100), 0, '.', '');
        if ($cum) {
            if ($precio_entrada > $cum['Precio']) {
                $oItem = new complex('Producto_Lista_Ganancia', 'Id_Producto_Lista_Ganancia', $cum['Id_Producto_Lista_Ganancia']);

                $oItem->Precio = $precio_entrada;
                $oItem->Id_Lista_Ganancia = $value['Id_Lista_Ganancia'];
                $oItem->save();
                unset($oItem);
                $id_producto_Ganancia = $cum['Id_Producto_Lista_Ganancia'];
                guardarActListaGanancia($id_producto_Ganancia, $precio_entrada, $cum['Precio']);
            }

        } else {
            $oItem = new complex('Producto_Lista_Ganancia', 'Id_Producto_Lista_Ganancia');
            $oItem->Cum = $cum_producto;
            $oItem->Precio = $precio_entrada;
            $oItem->Id_Lista_Ganancia = $value['Id_Lista_Ganancia'];
            $oItem->save();
            $id_producto_Ganancia = $oItem->getId();
            unset($oItem);
            guardarActListaGanancia($id_producto_Ganancia, $precio_entrada, 0);
        }
    }
}

function guardarActListaGanancia($id_producto_Lista, $precio_entrada, $precio_anterior)
{
    global $funcionario,  $acta_data;
    $oCon = new complex("Actividad_Producto_Lista_Ganancia", "Id_Actividad_Producto_Lista_Ganancia");
    $oCon->Identificacion_Funcionario=$funcionario;
    $oCon->Id_Producto_Lista_Ganancia=$id_producto_Lista;
    $oCon->Precio_Actual=$precio_anterior;
    $oCon->Precio_Nuevo=$precio_entrada;
    $oCon->Fecha=date("Y-m-d H:i:s");
    $oCon->Detalle=$acta_data['Codigo'];
    $oCon->save();
}
