<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once './delete_alerts.php';
require_once '../../config/start.inc.php';
include_once '../../class/class.querybasedatos.php';
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
include_once '../../class/class.consulta.php';
include_once '../../class/class.mipres.php';

// Id_Dispensacion

/**
 * Se inhstancia la clase DeleteAlerts
 */

$serviceDelete = new DeleteAlerts();

// include_once('../../class/class.portal_clientes.php'); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES

$queryObj = new QueryBaseDatos();
// $portalClientes = new PortalCliente($queryObj); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES

$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$func = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');

$datos = (array) json_decode($datos, true);
$resultado = false;

$oItem = new complex('Dispensacion', 'Id_Dispensacion', $datos['Id_Dispensacion']);
$dispensacion = $oItem->getData();
if ($dispensacion['Estado_Dispensacion'] !== 'Anulada') {
    $oItem->Estado_Dispensacion = "Anulada";
    $oItem->save();

    unset($oItem);
/*CAMBIO REALIZADO POR CARLOS CARDONA - NUEVO MODELO INVENTARIO 21/07/2020 */
    $query = "SELECT Id_Inventario_Nuevo, Cantidad_Entregada FROM Producto_Dispensacion WHERE Id_Dispensacion=" . $datos['Id_Dispensacion'] . " AND Lote <> 'Pendiente'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

//CAMBIO REALIZADO POR ROBERTH MORALES 07-10-2021 A PEDIDO DE FREDDY - desligar dispensacion anulada de la autorizacion
    $query = "SELECT id, Id_Dispensacion
          FROM Positiva_Data
          WHERE Id_Dispensacion=" . $datos['Id_Dispensacion'] . " ";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $positiva = $oCon->getData();
    unset($oCon);

    if ($positiva) {
        $query = "UPDATE Positiva_Data SET Id_Dispensacion = NULL  WHERE id = " . $positiva["id"];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $resultado = $oCon->createData();
        unset($oCon);
    }

//Se instancia el servicio para buscar y eliminar las alertas
    $disToDelete = $serviceDelete->search($datos['Id_Dispensacion']);
    $serviceDelete->delete($disToDelete);

    foreach ($productos as $prod) { // Ingresar nuevamente las cantidades al inventario.
        $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $prod['Id_Inventario_Nuevo']);
        $cantidad = number_format($prod['Cantidad_Entregada'], 0, "", "");
        $cantidad_final = $oItem->Cantidad + $cantidad;
        $oItem->Cantidad = number_format($cantidad_final, 0, "", "");
        $oItem->save();
        unset($oItem);
    }

    $ActividadDis["Identificacion_Funcionario"] = $func;
    $ActividadDis["Id_Dispensacion"] = $datos['Id_Dispensacion'];
    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Detalle"] = "Esta dispensacion fue anulada por el siguiente motivo: " . $datos['Motivo_Anulacion'];
    $ActividadDis["Estado"] = "Anulada";

    $oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
    foreach ($ActividadDis as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    unset($oItem);

/* ----- */

// Buscamos el Id_Dispensacion_Mipres
    $query = "SELECT Id_Dispensacion_Mipres FROM Dispensacion WHERE Id_Dispensacion=" . $datos['Id_Dispensacion'];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $Id_Dispensacion_Mipres = $oCon->getData();
    unset($oCon);

//var_dump($Id_Dispensacion_Mipres);
    if ($Id_Dispensacion_Mipres) {
        $mipres = new Mipres();

        $query = "SELECT * FROM Producto_Dispensacion_Mipres WHERE Id_Dispensacion_Mipres=" . $Id_Dispensacion_Mipres["Id_Dispensacion_Mipres"];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo("Multiple");
        $lista = $oCon->getData();
        unset($oCon);

        //var_dump($lista);

        foreach ($lista as $mipres_dis) {
            //echo "esta dentro de lista <br>";
            if ($mipres_dis["IdReporteEntrega"] != '' && $mipres_dis["IdReporteEntrega"] != '0') { //echo "entro a eliminar reporte entrega<br>";
                $res1 = $mipres->AnularReporteEntrega($mipres_dis["IdReporteEntrega"]);
                //var_dump($res1);
            }
            if ($mipres_dis["IdEntrega"] != '' && $mipres_dis["IdEntrega"] != '0') { //echo "entro a eliminar id entrega<br>";
                $res2 = $mipres->AnularEntrega($mipres_dis["IdEntrega"]);
                //var_dump($res2);
            }
            if ($mipres_dis["IdProgramacion"] != '' && $mipres_dis["IdProgramacion"] != '0') { //echo "entro a eliminar programacion<br>";
                $res3 = $mipres->AnularProgramacion($mipres_dis["IdProgramacion"]);
                //var_dump($res3);
            }
            $query = "UPDATE Producto_Dispensacion_Mipres SET IdReporteEntrega=0, IdEntrega=0, IdProgramacion=0  WHERE Id_Producto_Dispensacion_Mipres = " . $mipres_dis["Id_Producto_Dispensacion_Mipres"];
            $oCon = new consulta();
            $oCon->setQuery($query);
            $res = $oCon->createData();
            unset($oCon);
        }

        // Reabrimos el flujo de call center para que vuelva a pasar los filtros del tablero.
        $query = "UPDATE Dispensacion_Mipres 
            SET Estado = 'Pendiente',
                Estado_Callcenter = 'Pendiente',
                Fecha_Contacto = NULL,
                Observaciones_Callcenter = NULL
            WHERE Id_Dispensacion_Mipres = " . $Id_Dispensacion_Mipres["Id_Dispensacion_Mipres"];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $resultado = $oCon->createData();
        unset($oCon);
    }

/* ----- */

//  GuardarDispensacionPortalClientes($datos['Id_Dispensacion']); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES
    //  DESCOMENTAR ESTE METODO PARA GUARDAR EN EL PORTAL CLIENTES
    //  function GuardarDispensacionPortalClientes($idDis){
    //  global $portalClientes;
    //      $response = $portalClientes->ActualizarDispensacion($idDis);
    //  }

    $resu["Mensaje"] = "Anulado Correctamente";
} else {
    $resu["Mensaje"] = "Dispensacion anulada con anterioridad";

}
echo json_encode($resu);
