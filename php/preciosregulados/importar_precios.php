<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$datos = (array) json_decode($datos, true);


function limpiar($String)
{
    $String = str_replace("  ", " ", $String);
    $String = str_replace("á", "a", $String);
    $String = str_replace("Á", "A", $String);
    $String = str_replace("Í", "I", $String);
    $String = str_replace("í", "i", $String);
    $String = str_replace("é", "e", $String);
    $String = str_replace("É", "E", $String);
    $String = str_replace("ó", "o", $String);
    $String = str_replace("Ó", "O", $String);
    $String = str_replace("ú", "u", $String);
    $String = str_replace("Ú", "U", $String);
    $String = str_replace("ç", "c", $String);
    $String = str_replace("Ç", "C", $String);
    $String = str_replace("ñ", "n", $String);
    $String = str_replace("Ñ", "N", $String);
    $String = str_replace("Ý", "Y", $String);
    $String = str_replace("ý", "y", $String);
    $String = str_replace("'", "", $String);
    $String = str_replace('"', "", $String);
    $String = str_replace('\'', " ", $String);
    $String = str_replace('º', " ", $String);
    str_replace('?', "", $String);
    $String = utf8_encode(strtoupper(trim($String)));
    return $String;
}

$respuesta = [];
$query_insert = '';
$errores = [];
$insert = true; // Variable bandera para insertar.

if (!empty($_FILES['Archivo']['name'])) {

    $handle = fopen($_FILES['Archivo']['tmp_name'], "r");

    if ($handle) {
        $query = "SELECT Codigo_Cum FROM Precio_Regulado";

        $oCon = new consulta();
        $oCon->setTipo("Multiple");
        $oCon->setQuery($query);
        $precios = $oCon->getData();
        unset($oCon);
        $codigos = array_column($precios, 'Codigo_Cum');

      

        $i = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $i++;

            if (count($data) == 2) {
               /*  $pos = array_search(trim($data[0]), $codigos); */
                $pos = array_search(trim($data[0]), $codigos);
/* 
                if ($pos != '') {

                   
                    $oItem = new complex('Precio_Regulado', 'Codigo_Cum', trim($data[0]), 'Varchar');
                    $oItem->Codigo_Cum = trim($data[0]);
                    $oItem->Precio = number_format($data[1], 2, ".", "");

                    $oItem->save();

                    unset($oItem);
                } else {
 */
                    $query_insert .= "('$data[0]'," . number_format($data[1], 2, ".", "") . "),";
              /*   } */
            } else {
                $insert = false;
            }
        }

        if ($insert) {
            limpiarPrecios();
            $query_cabecera = 'INSERT IGNORE INTO `Precio_Regulado` (`Codigo_Cum`, `Precio`)';

            // echo $query_cabecera." VALUES ".trim($query_insert,",");
            // exit;

            if ($query_insert != '') {
                $oCon = new consulta();
                $oCon->setQuery($query_cabecera . " VALUES " . trim($query_insert, ","));
                $consultas = $oCon->createData();
                unset($oCon);
            }

            $respuesta["Tipo"] = "success";
            $respuesta["Mensaje"] = "Precios Regulados Importados Correctamente";
            $respuesta["Titulo"] = "Carga Exitosa";
        } else {
            $respuesta["Tipo"] = "error";
            $respuesta["Mensaje"] = "El numero de columnas no corresponde.";
            $respuesta["Titulo"] = "Error con Archivo";
        }
    } else {
        $respuesta["Tipo"] = "error";
        $respuesta["Mensaje"] = "El Archivo CSV no se deja abrir";
        $respuesta["Titulo"] = "Error con Archivo";
    }
} else {
    $respuesta["Tipo"] = "error";
    $respuesta["Mensaje"] = "El Archivo CSV no existe o se encuentra vacio";
    $respuesta["Titulo"] = "Error con Archivo";
}



echo json_encode($respuesta);

function limpiarPrecios(){
    $oCon = new consulta();
    $oCon->setQuery('TRUNCATE TABLE Precio_Regulado');
    $consultas = $oCon->createData();
    unset($oCon);
}