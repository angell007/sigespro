<?php
header('Content-Type: application/json');

require_once "/home/sigesproph/public_html/config/start.inc_cron.php";
include_once "/home/sigesproph/public_html/class/class.lista.php";
include_once "/home/sigesproph/public_html/class/class.complex.php";
include_once "/home/sigesproph/public_html/class/class.consulta.php";

try {
    $oItem = new complex("Configuracion", "Id_Configuracion", 1);
    $config = $oItem->getData();
    unset($oItem);

    date_default_timezone_set('America/Bogota');
    ini_set("memory_limit", "8000M");

// $query="SELECT Festivos FROM Configuracion WHERE Id_Configuracion=1";
    // $oCon= new consulta();
    // $oCon->setQuery($query);
    // $festivos = $oCon->getData();
    // unset($oCon);
    $fecha = date('Y-m-d H:i:s', strtotime('-1 day'));
    $fecha_hoy = date("d/m/Y");

    

    $borradores_fails = [];

    $query = 'SELECT B.* FROM Borrador B  WHERE B.Estado != "Eliminado" AND B.Fecha<="' . $fecha . '"  Order by B.Id_Borrador DESC';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    //code...
    $datos = $oCon->getData();
    // unset($oCon);

    // echo json_encode(count($datos));

} catch (\Throwable $th) {

    echo $th->getMessage();
}
$borradores_fails = [];

foreach ($datos as $borrador) {

    $text = (array) json_decode(limpiar($borrador["Texto"]), true);
    $productos = $text["Productos"];
    $tipo = $text["Modelo"]['Tipo'];
    if (count($productos) > 0) {
        foreach ($productos as $prod) {
            $seleccionados = $prod["Lotes_Seleccionados"];
            foreach ($seleccionados as $sel) {
                $tabla = $tipo == 'Contrato' ? 'Inventario_Contrato' : 'Inventario_Nuevo';

                $oItem = new complex("$tabla", "Id_$tabla", $sel["Id_$tabla"]);
                $inv = $oItem->getData();
                $seleccionada = number_format($inv["Cantidad_Seleccionada"], 0, "", "");
                $actual = number_format($sel["Cantidad"], 0, "", "");
                $fin = $seleccionada - $actual;
                if ($fin < 0) {
                    $fin = 0;
                }
                if ($fin > $inv['Cantidad']) {
                    $fin = $inv['Cantidad'];
                }
                $oItem->Cantidad_Seleccionada = number_format($fin, 0, "", "");
                $oItem->save();
                unset($oItem);

            }
        }
        $oItem = new complex('Borrador', "Id_Borrador", $borrador['Id_Borrador']);
        $oItem->Estado = "Eliminado";
        $oItem->save();
        unset($oItem);
    } else {
        if (isset($productos)) {
            $oItem = new complex('Borrador', "Id_Borrador", $borrador['Id_Borrador']);
            $oItem->Estado = "Eliminado";
            $oItem->save();
            unset($oItem);

        }
        // array_push($borradores_fails, $borrador['Id_Borrador']);
    }

}

echo "Eliminado correctamente";
if (count($borradores_fails) == 0) {
} else {
    echo "falta " . implode(",", $borradores_fails);
    // enviarEmailFail($borradores_fails);
}

$id_inventarios = GetProductosNoSeleccionados();
if ($id_inventarios) {
    $queryinventario = 'UPDATE Inventario_Nuevo SET Cantidad_Seleccionada =0
    WHERE Id_Inventario_Nuevo IN (' . $id_inventarios . ')';

    $oCon = new consulta();
    $oCon->setQuery($queryinventario);
    $oCon->createData();
    unset($oCon);
}
$contratos = GetProductosNoSeleccionadosContrato();
// echo json_encode($contratos); exit;
if ($contratos) {
    $queryinventario = "UPDATE Inventario_Contrato SET Cantidad_Seleccionada =0
    WHERE Id_Inventario_Contrato IN ($contratos )";
    $oCon = new consulta();
    $oCon->setQuery($queryinventario);
    $oCon->createData();
    unset($oCon);
}

function enviarEmailFail($borradores_fails)
{
    $to = "desarrollo.proh@gmail.com";
    $subject = "Error al eliminar borradores - SIGESPRO";
    $mensaje = "Error al eliminar los siguientes borradores: " . implode(",", $borradores_fails);
    $cabeceras = 'From: info@sigespro.com.co' . "\r\n" .
    'Reply-To: info@sigespro.com.co' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // echo "Se envió el email con el error. Borradores: " . implode(",", $borradores_fails);
    mail($to, $subject, $mensaje, $cabeceras);
}

function GetProductosNoSeleccionados()
{
    $query = "SELECT
    GROUP_CONCAT(I.Id_Inventario_Nuevo) as Inventarios,
    GROUP_CONCAT( I.Codigo_CUM) as Codigo_CUM,
    I.Lote,
    I.Fecha_Vencimiento,
    I.Cantidad_Seleccionada
FROM
    Inventario_Nuevo I
WHERE
    Cantidad_Seleccionada > 0
        AND NOT EXISTS( SELECT
            Id_Borrador
        FROM
            Borrador
        WHERE
             Estado != 'Eliminado' AND
            Texto LIKE CONCAT('%', I.Lote, '%'))";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);
    return $datos['Inventarios'];
}
function GetProductosNoSeleccionadosContrato()
{
    $query = "SELECT
    GROUP_CONCAT(I.Id_Inventario_Contrato) as Inventarios,
    GROUP_CONCAT(I.Cantidad_Seleccionada) as Cantidad_Seleccionada
FROM
    Inventario_Contrato I
WHERE
    I.Cantidad_Seleccionada > 0
        AND NOT EXISTS( SELECT
            Id_Borrador
        FROM
            Borrador
        WHERE
             Estado != 'Eliminado' AND
            Texto LIKE CONCAT('%', I.Id_Inventario_Contrato, '%'))";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);
    return $datos['Inventarios'];
}
function limpiar($string)
{
    $string = preg_replace("/[\r\n|\n|\r]+/", " ", $string);
    return $string;
}
