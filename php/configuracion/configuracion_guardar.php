<?php

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
// require('../../class/class.guardar_archivos.php');

// //Objeto de la clase que almacena los archivos
// $storer = new FileStorer();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$mod = (isset($_REQUEST['modulo']) ? $_REQUEST['modulo'] : '');
$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$datos = (array) json_decode($datos);

$salario_base = (int) $datos["Salario_Base"];
$salario_50 = $salario_base * 0.5;
$salario_75 = $salario_base * 0.75;

// if( $datos['Salario_Base'] != null){
$oItem = new complex('Configuracion', 'Id_' . $mod, 1);
$config=$oItem->getData();
if ($config['Salario_Base']<$salario_base){
    $bet="Between C.Salario_Base and $salario_base";
}
else{
    $bet="Between $salario_base and C.Salario_Base ";

}

$query = "UPDATE Contrato_Funcionario  CF inner join Configuracion C SET CF.Valor =   $salario_base   WHERE CF.Valor $bet AND CF.Estado LIKE 'Activo' AND 	(CF.Id_Tipo_Contrato != 7 OR CF.Id_Tipo_Contrato !=6 );";

// echo $query; exit;
$oCon = new consulta();
$oCon->setQuery($query);
$resultado = $oCon->createData();
unset($oCon);

$query = ' UPDATE Contrato_Funcionario CF  inner join Configuracion   SET CF.Valor = ' . $salario_75 . ' WHERE CF.Estado LIKE "Activo"  AND Valor <= Configuracion.Salario_Base AND  CF.Id_Tipo_Contrato = 7;	';
$oCon = new consulta();
$oCon->setQuery($query);
$resultado = $oCon->createData();
unset($oCon);

$query = 'UPDATE  Otrosi_Contrato OC 	INNER JOIN Contrato_Funcionario CF SET OC.Salario = ' . $salario_75 . ' WHERE OC.Estado = "Activo" 	AND CF.Id_Tipo_Contrato = 7	AND OC.Id_Contrato_Funcionario = CF.Id_Contrato_Funcionario;';
$oCon = new consulta();
$oCon->setQuery($query);
$resultado = $oCon->createData();
unset($oCon);

$query = 'UPDATE  Otrosi_Contrato OC 	INNER JOIN Contrato_Funcionario CF SET OC.Salario = ' . $salario_50 . ' WHERE OC.Estado = "Activo" 	AND CF.Id_Tipo_Contrato = 6	AND OC.Id_Contrato_Funcionario = CF.Id_Contrato_Funcionario;';
$oCon = new consulta();
$oCon->setQuery($query);
$resultado = $oCon->createData();
unset($oCon);

$query = 'UPDATE Contrato_Funcionario CF inner join Configuracion SET CF.Valor =  ' . ($salario_50) . '  WHERE CF.Valor <= Configuracion.Salario_Base AND CF.Estado LIKE "Activo" AND 	CF.Id_Tipo_Contrato = 6 ;';
$oCon = new consulta();
$oCon->setQuery($query);
$resultado = $oCon->createData();
unset($oCon);

// }

if (!empty($_FILES['Logo_Blanco']['name'])) {
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    //$file_blanco = array($_FILES['Logo_Blanco']);
    // $nombre_archivo = $storer->UploadFileToRemoteServer($file_blanco, 'store_remote_files', 'IMAGENES/LOGOS/');
    // $datos["Logo_Blanco"] = $nombre_archivo[0];
    $posicion1 = strrpos($_FILES['Logo_Blanco']['name'], '.') + 1;
    $extension1 = substr($_FILES['Logo_Blanco']['name'], $posicion1);
    $extension1 = strtolower($extension1);
    $_filename1 = uniqid() . "." . $extension1;
    $_file1 = $MY_FILE . "IMAGENES/LOGOS/" . $_filename1;

    $subido1 = move_uploaded_file($_FILES['Logo_Blanco']['tmp_name'], $_file1);
    if ($subido1) {
        @chmod($_file1, 0777);
        $datos["Logo_Blanco"] = $_filename1;
    }
}

if (!empty($_FILES['Logo_Negro']['name'])) {
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    //$file_negro = array($_FILES['Logo_Negro']);
    // $nombre_archivo = $storer->UploadFileToRemoteServer($file_negro, 'store_remote_files', 'IMAGENES/LOGOS/');
    // $datos["Logo_Negro"] = $nombre_archivo[0];
    $posicion1 = strrpos($_FILES['Logo_Negro']['name'], '.') + 1;
    $extension1 = substr($_FILES['Logo_Negro']['name'], $posicion1);
    $extension1 = strtolower($extension1);
    $_filename1 = uniqid() . "." . $extension1;
    $_file1 = $MY_FILE . "IMAGENES/LOGOS/" . $_filename1;

    $subido1 = move_uploaded_file($_FILES['Logo_Negro']['tmp_name'], $_file1);
    if ($subido1) {
        @chmod($_file1, 0777);
        $datos["Logo_Negro"] = $_filename1;
    }
}

if (!empty($_FILES['Logo_Color']['name'])) {
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    //$file_color = array($_FILES['Logo_Color']);
    // $nombre_archivo = $storer->UploadFileToRemoteServer($file_color, 'store_remote_files', 'IMAGENES/LOGOS/');
    // $datos["Logo_Color"] = $nombre_archivo[0];
    $posicion1 = strrpos($_FILES['Logo_Color']['name'], '.') + 1;
    $extension1 = substr($_FILES['Logo_Color']['name'], $posicion1);
    $extension1 = strtolower($extension1);
    $_filename1 = uniqid() . "." . $extension1;
    $_file1 = $MY_FILE . "IMAGENES/LOGOS/" . $_filename1;

    $subido1 = move_uploaded_file($_FILES['Logo_Color']['tmp_name'], $_file1);
    if ($subido1) {
        @chmod($_file1, 0777);
        $datos["Logo_Color"] = $_filename1;
    }
}

/* ----- */
$oItem = new complex('Configuracion', 'Id_' . $mod, 1);
foreach ($datos as $index => $value) {
    if (gettype($value) == "double") {
	// number_format($total,0,"","");
        $value = number_format($value, 2, ".", "");
    }

    $oItem->$index = $value;
}
$oItem->save();
unset($oItem);
