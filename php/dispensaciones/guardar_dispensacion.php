<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Request-Headers: *');
header('Content-Type: application/json');

include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once '../../class/class.configuracion.php';
require_once '../../class/class.qr.php'; /* AGREGAR ESTA CLASE PARA GENERAR QR */
require_once '../../config/start.inc.php';

date_default_timezone_set('America/Bogota');

if ($_POST['metodo']) {
    if( Editar()){
        EditarDispensacionPOST();
    }
    else{ 
        echo "No autorizado";
        return http_response_code(401);
    }
    return;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    crearDispensacion();
}
if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    EditarDispensacion();
}

function crearDispensacion()
{
    global $MY_FILE;
    $configuracion = new Configuracion();
    $datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
    $productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
    $func = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
    $punto = (isset($_REQUEST['punto']) ? $_REQUEST['punto'] : '');
    $cie = (isset($_REQUEST['Cie']) ? $_REQUEST['Cie'] : '');
    $producto_entregado = (isset($_REQUEST['producto_entregado']) ? $_REQUEST['producto_entregado'] : '');
    $idauditoria = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
    $celular_paciente = (isset($_REQUEST['celular_paciente']) ? $_REQUEST['celular_paciente'] : '');
    $resultado = [];
    $id_dis = null;

    $datos = (array) json_decode($datos);
    $productos = (array) json_decode($productos, true);

    if (count($productos) > 1) {
        $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
        $nc = $oItem->getData();

        $oItem->Consecutivo = $oItem->Consecutivo + 1;
        $oItem->save();
        $num_dispensacion = $nc["Consecutivo"];
        unset($oItem);

        $cod = "DIS" . sprintf("%05d", $num_dispensacion);

        $cie = str_replace('"', '', $cie);

        $imagen = $datos["Firma_Digital"];

        $fot = '';

        if ($imagen != "") {
            list($type, $imagen) = explode(';', $imagen);
            list(, $imagen) = explode(',', $imagen);
            $imagen = base64_decode($imagen);

            $fot = "firma" . uniqid() . ".jpg";
            $archi = $MY_FILE . "IMAGENES/FIRMAS-DIS/" . $fot;
            file_put_contents($archi, $imagen);
            chmod($archi, 0644);
        }

        $datos["Firma_Reclamante"] = $fot;

        $datos["Identificacion_Funcionario"] = $func;
        $datos["Id_Punto_Dispensacion"] = $punto;

        if ($datos["Tipo"] == "Capita") {
            unset($datos["Tipo_Servicio"]);
        } else {
            //$datos["Fecha_Formula"]="0000-00-00";
        }
        $entregas = $datos["Cantidad_Entregas"];
        $entrega_actual = $datos["Entrega_Actual"];
        $fechaformula = $datos["Fecha_Formula"];
        $datos['Productos_Entregados'] = $producto_entregado;
        $datos['Codigo'] = $cod;
        $datos["Estado"] = "Entregado";
        $datos["Estado_Dispensacion"] = "Activa";
        $datos['Cuota'] = number_format($datos['Cuota'], 0, "", "");
        $datos['Id_Turnero'] = $datos['Id_Turnero'] != '' ? $datos['Id_Turnero'] : '0';
        //$datos["Pendientes"] = 0;
        if ($cie == "undefined") {
            $datos["CIE"] = "";
        } else {
            $datos["CIE"] = $cie;
            $datos["CIE"] = $cie;
            $datos["CIE"] = $cie;
            $datos["CIE"] = $cie;
            $datos["CIE"] = $cie;
        }
        $ActividadDis["Identificacion_Funcionario"] = $func;

        $oItem = new complex("Dispensacion", "Id_Dispensacion");

        foreach ($datos as $index => $value) {
            if ($index != 'Id_Dispensacion') {
                $oItem->$index = $value;
            }

        }
        $oItem->save();
        $id_dis = $oItem->getId();
        $resultado = array();

        /* AQUI GENERA QR */
        $qr = generarqr('dispensacion', $id_dis, 'IMAGENES/QR/');
        $oItem = new complex("Dispensacion", "Id_Dispensacion", $id_dis);
        $oItem->Codigo_Qr = $qr;
        $oItem->save();
        unset($oItem);
        /* HASTA AQUI GENERA QR */

        ## ACTUALIZAR CELULAR PACIENTE
        if ($celular_paciente != '' && $celular_paciente != null) {
            updateCelularPaciente($datos['Numero_Documento'], $celular_paciente);
        }

        if (!empty($_FILES['acta']['name'])) { // Archivo de la Acta de Entrega.
            //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
            $posicion1 = strrpos($_FILES['acta']['name'], '.') + 1;
            $extension1 = substr($_FILES['acta']['name'], $posicion1);
            $extension1 = strtolower($extension1);
            $_filename1 = uniqid() . "." . $extension1;
            $_file1 = $MY_FILE . "ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/" . $_filename1;

            $subido1 = move_uploaded_file($_FILES['acta']['tmp_name'], $_file1);
            if ($subido1) {
                @chmod($_file1, 0777);
                $nombre_archivo = $_filename1;
                $oItem = new complex('Dispensacion', 'Id_Dispensacion', $id_dis);
                $oItem->Acta_Entrega = $nombre_archivo;
                $oItem->save();
                unset($oItem);
            }
        }

        $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
        $ActividadDis["Id_Dispensacion"] = $id_dis;
        $ActividadDis["Detalle"] = "Esta dispensacion fue agregada";
        $ActividadDis["Estado"] = "Creado";

        $oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
        foreach ($ActividadDis as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->save();
        unset($oItem);

        unset($productos[count($productos) - 1]);

        $productos_no_entregados = [];

        foreach ($productos as $producto) {

            if ($producto["Entregar_Faltante"] == "") {
                $producto["Entregar_Faltante"] = "0";
            }
            if ($producto["Id_Inventario"] == "") {
                $producto["Id_Inventario"] = "0";
            }
            if ($datos["Tipo"] == "Capita") {
                unset($producto["Fecha_Autorizacion"]);
            }
            $producto["Id_Dispensacion"] = $id_dis;

            if (validarEntregaProducto($producto["Cantidad_Entregada"], $producto['Id_Inventario'])) {

                if ($producto["Id_Inventario"] != "0") {
                    $producto['Id_Inventario'] = (int) $producto['Id_Inventario'];
                    $oItem = new complex('Inventario', "Id_Inventario", $producto['Id_Inventario']);
                    $inv_act = $oItem->getData();
                    $cantidad = number_format((int) $inv_act["Cantidad"], 0, "", "");
                    $cantidad_entregada = number_format($producto["Cantidad_Entregada"], 0, "", "");
                    $cantidad_total = $cantidad - $cantidad_entregada;
                    if ($cantidad_total < 0) {
                        $cantidad_total = 0;
                        $producto['Cantidad_Entregada'] = $cantidad;
                        $producto['Entregar_Faltante'] = $cantidad_entregada - $cantidad;
                    }
                    $oItem->Cantidad = number_format($cantidad_total, 0, "", "");
                    $oItem->save();
                    unset($oItem);
                }
                $oItem = new complex('Producto_Dispensacion', "Id_Producto_Dispensacion");
                foreach ($producto as $index => $value) {
                    $oItem->$index = $value;
                }
                $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
                $oItem->save();
                unset($oItem);

            } else {
                $oItem = new complex('Producto_Dispensacion', "Id_Producto_Dispensacion");
                $productos_no_entregados[] = $producto;

                $producto['Cantidad_Entregada'] = 0;

                foreach ($producto as $index => $value) {
                    $oItem->$index = $value;
                }
                $oItem->save();
                unset($oItem);
            }

        }
        if ($datos["Id_Dispensacion_Fecha_Entrega"] != "") {
            $fechahoy = date("Y-m-d");
            $oItem = new complex("Dispensacion_Fecha_Entrega", "Id_Dispensacion_Fecha_Entrega", $datos["Id_Dispensacion_Fecha_Entrega"]);
            $oItem->Fecha = $fechahoy;
            $oItem->save();
            unset($oItem);

        } else {

            for ($i = ($entrega_actual - 1); $i < $entregas; $i++) {
                $dias = 30 * $i;
                $fecha = date('Y-m-d');
                $nuevafecha = strtotime('+' . $dias . ' day', strtotime($fecha));
                $nuevafecha = date('Y-m-d', $nuevafecha);

                $oItem = new complex('Dispensacion_Fecha_Entrega', "Id_Dispensacion_Fecha_Entrega");
                $oItem->Id_Paciente = $datos["Numero_Documento"];
                $oItem->Fecha_Entrega = $nuevafecha;
                if ($i == ($entrega_actual - 1)) {
                    $oItem->Fecha = $fecha;
                }
                $oItem->Fecha_Formula = $datos["Fecha_Formula"];
                $datos["Entrega_Actual"] = $i + 1;
                $oItem->Entrega_Actual = $datos["Entrega_Actual"];
                $oItem->Entrega_Total = $entregas;
                $oItem->Id_Dispensacion = $id_dis;
                $oItem->save();
                unset($oItem);
            }
        }

        if ($idauditoria != "undefined" && $idauditoria != '' && $idauditoria != 0) {
            $oItem = new complex("Auditoria", "Id_Auditoria", $idauditoria);
            $oItem->Estado_Turno = "Atendido";
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->save();
            unset($oItem);
        }

        if ($id_dis) {
            $resultado['mensaje'] = "Se ha guardado correctamente la dispensación con codigo: " . $datos['Codigo'];
            $resultado['tipo'] = "success";
            $resultado['titulo'] = "Dispensación Entregada Correctamente";
            $resultado['status'] = getStatus();
            $resultado['productos_no_entregados'] = $productos_no_entregados;
        } else {
            $resultado['mensaje'] = "ha ocurrido un error guardando la información, por favor verifique";
            $resultado['tipo'] = "error";
            $resultado['titulo'] = "Error Entregando Dispensación";
        }

        $resultado['id_dispensacion'] = $id_dis;
    } else {
        $resultado['mensaje'] = "Ha ocurrido un error en listado de productos, por favor contactenos inmediatamente: (037)6421003 (Bucaramanga)";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error Entregando Dispensación";
    }

    echo json_encode($resultado);
}

function guardarActividad_Dispensacion($detalle, $id_dis, $id_funcionario)
{

    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Dispensacion"] = $id_dis;
    $ActividadDis["Detalle"] = $detalle;
    $ActividadDis["Estado"] = "Edicion";
    $ActividadDis["Identificacion_Funcionario"] = $id_funcionario;

    $oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
    foreach ($ActividadDis as $index => $value) {
        $oItem->$index = $value;
    }

    $oItem->save();
}

function EditarDispensacion()
{
    $respuesta = null;
    $_PUT = json_decode(file_get_contents("php://input"), true);

    $productos = ($_PUT['productos']);
    $func = (isset($_PUT['funcionario']) ? $_PUT['funcionario'] : '');
    $productos_editados = [];
    $i = 0;
    $mensaje = "Dispensacion editada ";
    $id_dis = null;
    foreach ($productos as $producto) {
        if ($producto['Cantidad_Formulada'] != $producto['Cantidad_Formulada_Total']) {
            $id_dis = $producto['Id_Dispensacion'];
            if(set_productos($producto)){
                $respuesta['Editado']["$id_dis"] = "Editado con Exito";
                $productos_editados[$i]['Nombre'] = $producto['Nombre_Comercial'];
                $productos_editados[$i]['Inicial'] = $producto['Cantidad_Formulada_Total'];
                $productos_editados[$i]['Final'] = $producto['Cantidad_Formulada'];
                $mensaje .= "
                --- producto $producto[Nombre_Comercial]:
                Cantidad Inicial: $producto[Cantidad_Formulada_Total]
                Cantidad Actual: $producto[Cantidad_Formulada]
                ---
                ";
            }
            else{
                $respuesta['Editado']["$id_dis"] = "No editado por cantidades entregadas";
            }
        }

    }
    $respuesta['Actividad'] = "No se han realizado modificaciones";
    if (count($productos_editados) > 0) {
        guardarActividad_Dispensacion($mensaje, $id_dis, $func);
        $respuesta['Actividad'] = "Actividad Guardada";
        $query = "UPDATE Dispensacion D INNER JOIN Producto_Dispensacion PD ON PD.Id_Dispensacion = D.Id_Dispensacion
        SET D.Pendientes = (SELECT SUM(PD2.Cantidad_Formulada - PD2.Cantidad_Entregada)FROM Producto_Dispensacion PD2 Where PD2.Id_Dispensacion = D.Id_Dispensacion) WHERE D.Id_Dispensacion = $id_dis   ";
    
        $oItem = new consulta();
        $oItem->setQuery($query);
        $oItem->getData();
        unset($oItem);
    }
    header('Content-Type: application/json');
    echo json_encode($respuesta);
}
function EditarDispensacionPOST()
{
    $respuesta = null;
    $_PUT = $_POST;

    $productos = json_decode($_PUT['productos'], true);
    // echo json_encode($productos);exit;

    $func = (isset($_PUT['funcionario']) ? $_PUT['funcionario'] : '');
    $productos_editados = [];
    $i = 0;
    $mensaje = "Dispensacion editada ";
    $id_dis = null;
    foreach ($productos as $producto) {
        if ($producto['Cantidad_Formulada'] != $producto['Cantidad_Formulada_Total']) {
            $id_dis = $producto['Id_Dispensacion'];
            if(set_productos($producto)){

                $respuesta['Editado']["$id_dis"] = "Editado con Exito";
                $productos_editados[$i]['Nombre'] = $producto['Nombre_Comercial'];
                $productos_editados[$i]['Inicial'] = $producto['Cantidad_Formulada_Total'];
                $productos_editados[$i]['Final'] = $producto['Cantidad_Formulada'];
                $mensaje .= "
                --- producto $producto[Nombre_Comercial]:
                Cantidad Inicial: $producto[Cantidad_Formulada_Total]
                Cantidad Actual: $producto[Cantidad_Formulada]
                ---
                ";
            }
            else{
                $respuesta['Editado']["$id_dis"] = "No editado por cantidades entregadas";
            }
        }

    }
    $respuesta['Actividad'] = "No se han realizado modificaciones";
    if (count($productos_editados) > 0) {
        guardarActividad_Dispensacion($mensaje, $id_dis, $func);
        $respuesta['Actividad'] = "Actividad Guardada";

        $query = "UPDATE Dispensacion D INNER JOIN Producto_Dispensacion PD ON PD.Id_Dispensacion = D.Id_Dispensacion
        SET D.Pendientes = (SELECT SUM(PD2.Cantidad_Formulada - PD2.Cantidad_Entregada)FROM Producto_Dispensacion PD2 Where PD2.Id_Dispensacion = D.Id_Dispensacion) WHERE D.Id_Dispensacion = $id_dis   ";
    
        $oItem = new consulta();
        $oItem->setQuery($query);
        $oItem->getData();
        unset($oItem);
    }
    // EditarDispensacion($_PUT);
    echo json_encode($respuesta);
}

function cantidadInventario($id_inventario)
{

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario WHERE Id_Inventario = $id_inventario";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $cantidad = $oCon->getData()['Cantidad'];
    unset($oCon);

    return $cantidad;

}

function validarEntregaProducto($cant_entrega, $id_inventario)
{

    $cantidad_inventario = cantidadInventario($id_inventario);

    if (($cantidad_inventario - $cant_entrega) >= 0) {
        return true;
    }

    return false;

}

function updateCelularPaciente($paciente, $celular)
{
    $oItem = new complex('Paciente', 'Id_Paciente', $paciente, 'Varchar');
    $oItem->Telefono = $celular;
    $oItem->save();
    unset($oItem);

    return true;
}

function getStatus()
{
    global $productos_no_entregados;

    if (count($productos_no_entregados) > 0) {
        return 1;
    } else {
        return 2;
    }
}

function set_productos($producto)
{
    $oItem = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $producto["Id_Producto_Dispensacion"]);
    $p=$oItem->getData();
    if($p['Cantidad_Entregada']==0){

        $oItem->Cantidad_Formulada = $producto['Cantidad_Formulada'];
        $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
        $oItem->save();
        unset($oItem);
        return true;
    }
    return false;

}

function CalcularPendientes($productos)
{
    $pendientes = 0;
    foreach ($productos as $p) {
        $pendientes += ($p['Cantidad_Formulada'] - $p['Cantidad_Entregada']);
    }
    return $pendientes;
}

function Editar(){
    $id_funcionario= $_POST['funcionario'];
    $query = "SELECT Editar FROM Perfil_Funcionario WHERE Identificacion_Funcionario= $id_funcionario AND Titulo_Modulo = 'Dispensaciones'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $editar= $oCon->getData()['Editar'];
    return $editar;
}