<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../helper/response.php');

$Id_Estiba = (isset($_REQUEST['Id_Estiba']) ? $_REQUEST['Id_Estiba'] : '');
$Codigo_Barras = (isset($_REQUEST['Codigo_Barras']) ? $_REQUEST['Codigo_Barras'] : '');
$Contador = (isset($_REQUEST['Contador']) ? $_REQUEST['Contador'] : '');
$Digitador = (isset($_REQUEST['Digitador']) ? $_REQUEST['Digitador'] : '');
$Tipo = (isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : '');


$cond = '';
$cond2 = '';
if ($Id_Estiba != "0" && $Id_Estiba != 0) {
    $cond = ' AND PRD.Id_Categoria =' . $Id_Estiba;
}


// $query ="SELECT I.Id_Doc_Inventario_Fisico FROM Doc_Inventario_Fisico I 
// INNER JOIN (SELECT Id_Estiba From Estiba where Codigo_Barras='$Codigo_Barras') E ON E.Id_Estiba=I.Id_Estiba
// WHERE I.Estado NOT IN ('Terminado')";
// $oCon= new consulta();
// $oCon->setQuery($query);
// $Inv = $oCon->getData();
// unset($oCon);

$query = "SELECT I.Id_Doc_Inventario_Fisico FROM Doc_Inventario_Fisico I 
    INNER JOIN (SELECT Id_Estiba From Estiba where Codigo_Barras='$Codigo_Barras') E ON E.Id_Estiba=I.Id_Estiba
    WHERE I.Estado NOT IN ('Terminado')";

$oCon = new consulta();
$oCon->setQuery($query);
$Inv1 = $oCon->getData();
unset($oCon);

$query = "SELECT I.Id_Doc_Inventario_Fisico_Punto, I.Id_Estiba, I.Estado, E.* FROM Doc_Inventario_Fisico_Punto I 
    INNER JOIN ( SELECT E.Id_Estiba, E.Codigo_Barras, E.Id_Bodega_Nuevo, E.Id_Punto_Dispensacion from Estiba E where E.Codigo_Barras='$Codigo_Barras' ) E ON E.Id_Estiba=I.Id_Estiba
    WHERE I.Estado NOT IN ('Terminado')";

// echo $query; exit;
$oCon = new consulta();
$oCon->setQuery($query);
$Inv2 = $oCon->getData();

unset($oCon);

$Inv =  ($Inv1 == null) ? null : $Inv1;
$Inv =  ($Inv == null) ? (($Inv2 == null) ? null : $Inv2)  :   $Inv;

$let = explode("-", $Letras);

$order = 'PRD.Nombre_Comercial';

$fin = '';
foreach ($let as $l) {
    $fin .= $order . ' LIKE "' . $l . '%" OR ';
}
$fin = trim($fin, " OR ");

if ($fin != '') {
    $cond .= ' AND (' . $fin . ') AND I.Cantidad>0 GROUP BY I.Id_Producto';
}

$inicio = date("Y-m-d H:i:s");

if (!isset($Inv)) {
    $oItem = new complex("Funcionario", "Identificacion_Funcionario", $Contador);
    $func_contador = $oItem->getData();
    unset($oItem);

    $oItem = new complex("Funcionario", "Identificacion_Funcionario", $Digitador);
    $func_digitador = $oItem->getData();
    unset($oItem);

    if (isset($func_contador["Identificacion_Funcionario"]) && isset($func_digitador["Identificacion_Funcionario"])) {
        $fromDoc = '';
        $condEstiba = '';
        if ($Tipo == 'Punto') {
            $condEstiba = ' AND E.Id_Punto_Dispensacion IS NOT NULL AND E.Id_Punto_Dispensacion != "" ';
            $fromDoc = '_Punto';
        } else {
            $condEstiba = ' AND E.Id_Bodega_Nuevo IS NOT NULL AND E.Id_Bodega_Nuevo != "" ';
        }


        $query = "SELECT
                        E.Id_Estiba, 
                        E.Estado,
                        E.Nombre as 'Nombre_Estiba'
                       
                    FROM Estiba E
               
                    WHERE E.Codigo_Barras='$Codigo_Barras' $condEstiba
                    ";


        $oCon = new consulta();
        //$oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $Estiba = $oCon->getData();
        unset($oCon);


        if (isset($Estiba['Id_Estiba'])) {
            if ($Estiba['Estado'] == 'Inactiva') {
                $resultado["Tipo"] = "error";
                $resultado["Title"] = "Inventario No Iniciado";
                $resultado["Text"] = "La estiba se encuentra Inactiva, favor validar";
            } else {

                #apartar la estiba para no dejar realizar remisiones, ajustes, etc.


                $oItem = new complex("Estiba", "Id_Estiba", $Estiba['Id_Estiba']);
                $oItem->Estado = 'Inventario';
                $oItem->save();
                unset($oItem);

                #crear el documento

                $oItem = new complex("Doc_Inventario_Fisico$fromDoc", "Id_Doc_Inventario_Fisico$fromDoc");
                $oItem->Fecha_Inicio = $inicio;

                $oItem->Funcionario_Digita = $Digitador;
                $oItem->Funcionario_Cuenta = $Contador;
                $oItem->Id_Estiba = $Estiba['Id_Estiba'];
                $oItem->Estado = 'Pendiente Primer Conteo';
                $oItem->save();
                $id_inv = $oItem->getId();
                unset($oItem);

                if ($Tipo == 'Punto') {

                    guardarHistorialPunto($id_inv, $Estiba['Id_Estiba']);
                } else {
                    guardarHistorial($id_inv, $Estiba['Id_Estiba']);
                }

                $resultado["Id_Doc_Inventario_Fisico"] = $id_inv;
                $resultado["Funcionario_Digita"] = $func_digitador;
                $resultado["Funcionario_Cuenta"] = $func_contador;
                $resultado["Estiba"] = $Estiba;
                $resultado["Inicio"] = $inicio;



                $resultado["Tipo"] = "success";
                $resultado["Title"] = "Inventario Iniciado Correctamente";
                $resultado["Text"] = "Vamos a dar Inicio al Inventario Físico, ¡Muchos Exitos!";
            }
        } else {
            $resultado["Tipo"] = "error";
            $resultado["Title"] = "Error de Estiba";
            $resultado["Text"] = "El Código de Barras de la Estiba, no coincide con los Códigos Registrados en el sistema";
        }
    } else {
        $resultado["Tipo"] = "error";
        $resultado["Title"] = "Error de Funcionario";
        $resultado["Text"] = "Alguna de las Cédulas de los Funcionarios, no coincide con Funcionarios Registrados en el sistema";
    }
} else {
    $resultado["Tipo"] = "error";
    $resultado["Title"] = "Inventario No Iniciado";
    $resultado["Text"] = "Ya hay otro Grupo de Personas Trabajando en un Inventario para la misma Estiba";
}

echo json_encode($resultado);



function guardarHistorial($Id_Doc_Inv, $idEstiba)
{

    $query = 'SELECT * FROM Inventario_Nuevo I WHERE I.Id_Estiba = ' . $idEstiba;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $inventario = $oCon->getData();

    foreach ($inventario as $item) {
        # code...
        $oItem = new complex('Historial_Inventario', 'Id_Historial_Inventario');
        $oItem->Id_Inventario_Nuevo = $item['Id_Inventario_Nuevo'];
        $oItem->Id_Estiba = $item['Id_Estiba'];
        $oItem->Codigo_CUM = $item['Codigo_CUM'];
        $oItem->Lote = $item['Lote'];
        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];
        $oItem->Cantidad = $item['Cantidad'];
        $oItem->Cantidad_Apartada = $item['Cantidad_Apartada'];
        $oItem->Cantidad_Seleccionada = $item['Cantidad_Seleccionada'];
        $oItem->Id_Doc_Inventario_Fisico = $Id_Doc_Inv;
        $oItem->Id_Producto = $item['Id_Producto'];
        $oItem->save();
        unset($oItem);
    }
}

function guardarHistorialPunto($Id_Doc_Inv, $idEstiba)
{

    $query = 'SELECT * FROM Inventario_Nuevo I WHERE I.Id_Estiba = ' . $idEstiba;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $inventario = $oCon->getData();

    foreach ($inventario as $item) {
        # code...
        $oItem = new complex('Historial_Inventario_Punto', 'Historial_Inventario_Punto');
        $oItem->Id_Inventario_Nuevo = $item['Id_Inventario_Nuevo'];
        $oItem->Id_Estiba = $item['Id_Estiba'];
        $oItem->Codigo_CUM = $item['Codigo_CUM'];
        $oItem->Lote = $item['Lote'];
        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];
        $oItem->Cantidad = $item['Cantidad'];
        $oItem->Cantidad_Apartada = $item['Cantidad_Apartada'];
        $oItem->Cantidad_Seleccionada = $item['Cantidad_Seleccionada'];
        $oItem->Id_Doc_Inventario_Fisico_Punto = $Id_Doc_Inv;
        $oItem->Id_Producto = $item['Id_Producto'];
        $oItem->save();
        unset($oItem);
    }
}
