<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('../../../class/class.http_response.php');

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';
$http_response = new HttpResponse();


if (!empty($_FILES['archivo']['name'])){ // Archivo de la archivo de Entrega.
    $posicion1 = strrpos($_FILES['archivo']['name'],'.')+1;
    $extension1 =  substr($_FILES['archivo']['name'],$posicion1);
    $extension1 =  strtolower($extension1);
    $_filename1 = uniqid() . "." . $extension1;
    $_file1 = $MY_FILE . "ARCHIVOS/COMPROBANTES/" . $_filename1;
    
    $subido1 = move_uploaded_file($_FILES['archivo']['tmp_name'], $_file1);
        
}

$inputFileName = $MY_FILE . "ARCHIVOS/COMPROBANTES/" . $_filename1;

try {
    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objPHPExcel = $objReader->load($inputFileName);
} catch(Exception $e) {
    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
}


$sheet = $objPHPExcel->getSheet(0); 
$highestRow = $sheet->getHighestRow(); 
$highestColumn = 'I';

$facturas=[];
$i=-1;
for ($row = 1; $row <= $highestRow; $row++){ $i++;
    //  Read a row of data into an array
    try {
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row);
    } catch (\Throwable $th) {
        echo $th->getMessage();
        exit;
    }
    $facturas[$i]=$rowData;
}
$facturas_no_encontradas=[];
$retenciones=[];
$descuentos=[];
$ajustes=[];
$fact=[];
$f=0;
$a=0;
$d=0;
$i=0;
$r=0;
$x = 0;
$y= 0;


foreach ($facturas as $value) {

    foreach ($value as $key => $item) {
        if ($item[0] != '' && $item[1] != '') { // Si el plan y el nit son diferentes de vacío consultamos.
            $valido = true;

            
            $datosTercero = getDatosTercero($item[1]);

            $plan_cuentas = getDatosPlanCuenta($item[0]);
            # $id_plan_cuentas = getIdPlanCuenta($item[0]);


            $centrocosto = getDetalleCentroCosto($item[2]);
            //$id_centro_costo = getIdCentroCosto($item[2]);

            if ( !$datosTercero || !$plan_cuentas || !$centrocosto ) {
                # code...
                $valido = false;
            }

            $factura = [
                #"Id_Plan_Cuentas" => $id_plan_cuentas,
                "Id_Plan_Cuentas" => $plan_cuentas['Id_Plan_Cuentas'],
                #"Cuenta" => getDatosPlanCuenta($item[0]),
                "Cuenta" => $plan_cuentas,
                "Nit_Cuenta" => $item[1],
                "Nit" => $datosTercero,
                "Tipo_Nit" => $datosTercero['Tipo'],
                #"Id_Centro_Costo" =>$id_centro_costo, 
                "Id_Centro_Costo" =>$centrocosto['Id_Centro_Costo'], 
                #"Centro_Costo" => getDetalleCentroCosto($item[2]),
                "Centro_Costo" => $centrocosto,
                "Documento" => $item[3],
                "Concepto" => $item[4],
                "Base" => '0',
                "Debito" => $item[5] != '' ? str_replace(",",".",$item[5]) : '0',
                "Credito" => $item[6] != '' ? str_replace(",",".",$item[6]) : '0',
                "Deb_Niif" => $item[7] != '' ? str_replace(",",".",$item[7]) : '0',
                "Cred_Niif" => $item[8] != '' ? str_replace(",",".",$item[8]) : '0',
                "Valido" => $valido
            ];  
    
            $fact[] = $factura;
        }
    }
    if ($x==200) {
   
        $logFile = fopen('prueba.txt','w') or die("Error creando archivo");;
        fwrite($logFile,$y);
        $y++;
        sleep(5);
        $x=0;
    }

    $x++;

}

$file=$MY_FILE . "ARCHIVOS/COMPROBANTES/" . $_filename1;
unlink($file);
$resultado['Facturas']=$fact;

echo json_encode($resultado);

function getIdPlanCuenta($codigo) {
    $query="SELECT P.Id_Plan_Cuentas FROM Plan_Cuentas P WHERE P.Codigo='$codigo'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado ? $resultado['Id_Plan_Cuentas'] : '0';
}

function getDatosPlanCuenta($codigo) {
    $query = 'SELECT PC.Id_Plan_Cuentas, PC.Id_Plan_Cuentas AS Id, 
    PC.Codigo, PC.Codigo AS Codigo_Cuenta,/*  CONCAT(PC.Nombre," - ",PC.Codigo) as Codigo,  */
    CONCAT(PC.Codigo," - ",PC.Nombre) as Nombre, PC.Centro_Costo
    FROM Plan_Cuentas PC WHERE PC.Codigo = "'.$codigo.'"';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado ? $resultado : [];
}

function getDatosTercero($nit) {
    $query = 'SELECT r.* FROM (
        (
        SELECT C.Id_Cliente AS ID, IF(Nombre IS NULL OR Nombre = "", CONCAT_WS(" ", C.Id_Cliente,"-",Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido),
         CONCAT(C.Id_Cliente, " - ", C.Nombre)) AS Nombre, "Cliente" AS Tipo FROM Cliente C WHERE C.Estado != "Inactivo" AND C.Id_Cliente = '.$nit.' ) 
    
        UNION (SELECT P.Id_Proveedor AS ID, IF(P.Nombre = "" OR P.Nombre IS NULL,
            CONCAT_WS(" ",P.Id_Proveedor,"-",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido),CONCAT(P.Id_Proveedor, " - ", P.Nombre)) AS Nombre,
            "Proveedor" AS Tipo FROM Proveedor P
            WHERE  P.Id_Proveedor = '.$nit.' 
            ) 

        UNION (SELECT F.Identificacion_Funcionario AS ID, CONCAT(F.Identificacion_Funcionario, " - ", F.Nombres," ", F.Apellidos) AS Nombre,
            "Funcionario" AS Tipo FROM Funcionario F
            WHERE  F.Identificacion_Funcionario  = '.$nit.' 
            ) 
        UNION (SELECT CC.Nit AS ID, CONCAT(CC.Nit, " - ", CC.Nombre) AS Nombre, "Caja_Compensacion" AS Tipo FROM Caja_Compensacion CC
            WHERE CC.Nit IS NOT NULL AND
            CC.Nit  = '.$nit.' 
            )
        )   r ';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado ? $resultado : [];
}

function getIdCentroCosto($codigo_centro_costo) {

    $id_centro_costo = '0';
    
    if ($codigo_centro_costo != '') {
        $query = "SELECT Id_Centro_Costo FROM Centro_Costo WHERE Codigo LIKE '%$codigo_centro_costo' LIMIT 1";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $resultado = $oCon->getData();

        if ($resultado) {
            $id_centro_costo = $resultado['Id_Centro_Costo'];
        }
    }

    return $id_centro_costo;
}

function getDetalleCentroCosto($codigo_centro_costo) {

    $res = [];
    
    if ($codigo_centro_costo != '') {
        $query = 'SELECT CONCAT(Codigo, " - ", Nombre) AS Nombre, Id_Centro_Costo FROM Centro_Costo WHERE Movimiento = "Si" AND Estado = "Activo" AND Codigo LIKE "%'.$codigo_centro_costo.'" LIMIT 1' ;

        $oCon= new consulta();
        $oCon->setQuery($query);
        $centrocosto = $oCon->getData();
        unset($oCon);

        if ($centrocosto) {
            $res = $centrocosto;
        }
    }

    return $res;
}

?>