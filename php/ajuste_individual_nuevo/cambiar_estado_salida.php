<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.configuracion.php');
require_once('./helper.ajuste_individual.php');

$id_ajuste = (isset($_REQUEST['id_ajuste']) ? $_REQUEST['id_ajuste'] : '');
$id_clase_ajuste_individual = (isset($_REQUEST['id_clase_ajuste_individual']) ? $_REQUEST['id_clase_ajuste_individual'] : '');
$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');

$oCon = new complex('Ajuste_Individual','Id_Ajuste_Individual',$id_ajuste);
$ajuste = $oCon->getData();
unset($oCon);

$response = [];
if ($tipo == 'Aprobar') {
    $productos = json_decode($productos, true);
    
    #validar inventario si existe 
    if(!validarBodegaInventario($ajuste['Id_Origen_Destino'])){
        if (validarExistencia()) {
            aprobarSalida();
            guardarActividad($id_ajuste, $funcionario, 'Se Aprobó la salida del ajuste individual', 'Aprobacion');
        } else {
            setResponse('error', 'Error de cantidades disponibles', 'Existe un producto sin cantidades disponible, ¡por favor valide la información!');
        }
    }else{
        setResponse('error','¡No se puede realizar la operación!','En este momento la bodega que seleccionó se encuentra realizando un inventario.',$response); 
    }

} elseif ($tipo == 'Anular') {
    # code...
    anularSalida();
    guardarActividad($id_ajuste, $funcionario, 'Se  Anuló la salida del ajuste individual', 'Anulada');
    setResponse('success', '¡Anulación Exitosa!', '¡El ajuste se ha anulado de manera exitosa!');
} else {
    #error
    setResponse('error', 'Error innesperado', 'Se generó un error innesperado, contaacte el equipo de sistemas');
}


echo json_encode($response);


function aprobarSalida()
{
    global $funcionario, $id_ajuste;
    #APROBAR 
    $query = 'UPDATE Ajuste_Individual 
        SET Estado_Salida_Bodega = "Aprobado",  Funcionario_Autoriza_Salida = ' . $funcionario . ',
         Fecha_Aprobacion_Salida  = NOW()
        WHERE Id_Ajuste_Individual = ' . $id_ajuste;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    #Actualizar Inventario 
    actualizarInventario();
}


function actualizarInventario()
{
    global $productos, $id_ajuste, $id_clase_ajuste_individual, $funcionario;

    foreach ($productos as $key => $producto) {

        $cantidad = number_format($producto["Cantidad"], 0, "", "");
        $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $producto["Id_Inventario_Nuevo"]);
        $Inv = $oItem->getData();
        unset($oItem);

        $cantidad_final = ((int) $Inv['Cantidad']) - $cantidad;

        if ($cantidad_final < 0) {
            $cantidad_final = 0;
        }
        $cantidad_final = number_format($cantidad_final, 0, "", "");
        $query = 'UPDATE Inventario_Nuevo SET Cantidad = ' . $cantidad_final
            . ' WHERE Id_Inventario_Nuevo = ' . $producto["Id_Inventario_Nuevo"];

        $oCon = new consulta();
        $oCon->setQuery(($query));
        $oCon->createData();
        unset($oCon);
    }

    #VALIDAR SI ES CAMBIO DE ESTIBA
    $query = 'SELECT Id_Ajuste_Individual , Cambio_Estiba, Identificacion_Funcionario, Id_Origen_Destino
                FROM Ajuste_Individual  
                WHERE Id_Ajuste_Individual = ' . $id_ajuste;

    $oCon = new consulta();
    $oCon->setQuery(($query));
    $ajuste = $oCon->getData();

    if ($ajuste['Cambio_Estiba']) {
        # Crear Entrada
        crearEntrada($ajuste);
    } else {
        #CONTABILIDAD 

        $datos_movimiento_contable['Id_Registro'] = $id_ajuste;
        $datos_movimiento_contable['Nit'] = $funcionario;
        $datos_movimiento_contable['Tipo'] = "Salida";
        $datos_movimiento_contable['Clase_Ajuste'] = $id_clase_ajuste_individual;
        $datos_movimiento_contable['Productos'] = $productos;

        $contabilizacion = new Contabilizar(true);
        $contabilizacion->CrearMovimientoContable('Ajuste Individual', $datos_movimiento_contable);
        unset($contabilizacion);
        setResponse('success', '¡Ajuste exitoso!', 'El ajuste se ha compleado de manera exitosa');
    }
}

function validarExistencia()
{
    global $productos;

    foreach ($productos as $key => $producto) {
        $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $producto["Id_Inventario_Nuevo"]);
        $Inventario = $oItem->getData();
        unset($oItem);

        $cantidad_disponible = $Inventario['Cantidad'] - ($Inventario['Cantidad_Seleccionada'] + $Inventario['Cantidad_Apartada']);
        if ($cantidad_disponible < $producto['Cantidad']) {
            return false;
        }
    }
    return true;
}
function anularSalida()
{
    global $id_ajuste, $funcionario;
    $query = 'UPDATE Ajuste_Individual SET Estado = "Anulada" , Fecha_Anulacion = NOW() ,
                Estado_Salida_Bodega = "Anulado", Funcionario_Autoriza_Salida = ' . $funcionario . '
                WHERE Id_Ajuste_Individual = ' . $id_ajuste;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}

function crearEntrada($ajuste)
{
    global $productos, $funcionario;

    $id_ajuste_entrada = Save_Encabezado($ajuste);
    /* AQUI GENERA QR 
    $qr = generarqr('ajusteindividual',$id_ajuste,'IMAGENES/QR/');
    $oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual",$id_ajuste);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
    HASTA AQUI GENERA QR */

    Guardar_Producto_Ajuste($id_ajuste_entrada);

    if ($id_ajuste_entrada) {

        setResponse('success', '¡Ajuste exitoso!', '¡Se ha creado el ajuste de entrada, ahora puede acomodar los productos!');
    } else {
        setResponse('error', '¡Error inesperado !', '¡Ha ocurrido un error Creando la Nueva Entrada!');
    }
}


function Save_Encabezado($ajuste)
{
    global $datos, $funcionario;
    $configuracion = new Configuracion();
    $cod = $configuracion->getConsecutivo('Ajuste_Individual', 'Ajuste_Individual');
    unset($configuracion);

    $oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual');
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Codigo = $cod;
    $oItem->Tipo = "Entrada";
    $oItem->Origen_Destino = 'Bodega';
    $oItem->Id_Origen_Destino = $ajuste['Id_Origen_Destino'];
    $oItem->Id_Salida = $ajuste['Id_Ajuste_Individual'];
    $oItem->Estado_Entrada_Bodega = 'Aprobada';
    $oItem->Cambio_Estiba = '1';


    $oItem->save();
    $id_ajuste = $oItem->getId();
    unset($oItem);

    guardarActividad($id_ajuste, $funcionario, 'Se creó la entrada del ajuste individual ' . $cod, 'Creacion');
    return $id_ajuste;
}


function Guardar_Producto_Ajuste($id_ajuste)
{
    global  $productos;

    foreach ($productos as $key => $producto) {

        $oItem = new complex('Producto_Ajuste_Individual', 'Id_Producto_Ajuste_Individual');
        $oItem->Id_Ajuste_Individual = $id_ajuste;
        $oItem->Id_Producto = $producto["Id_Producto"];
        $oItem->Lote = $producto['Lote'];
        $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
        $oItem->Observaciones = $producto['Observaciones'];
        $cantidad1 = number_format($producto["Cantidad"], 0, "", "");
        $oItem->Cantidad = $cantidad1;
        $costo = number_format($producto["Costo"], 0, ".", "");
        $oItem->Costo = $costo;
        $oItem->Id_Inventario_Nuevo = $producto['Id_Inventario_Nuevo'];
        $oItem->Id_Nueva_Estiba = $producto['Id_Nueva_Estiba'];
        $oItem->save();
        unset($oItem);
    }
}

function setResponse($type, $title, $text)
{
    global $response;
    $response['type'] = $type;
    $response['title'] = $title;
    $response['text'] = $text;
}
