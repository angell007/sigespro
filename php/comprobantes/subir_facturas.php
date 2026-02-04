<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');

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
$highestColumn = 'H';

$facturas=[];
$i=-1;
for ($row = 1; $row <= $highestRow; $row++){ $i++;
    //  Read a row of data into an array
    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row);
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
foreach ($facturas as $value) {
    foreach ($value as $key => $item) {
        $datos_factura=GetIdFactura($item[2]);
        $bancos=ValidarBancos($item[0]);
        $datos_factura['Id_Factura'] = $datos_factura['Id_Factura'] ? $datos_factura['Id_Factura'] : '0';
        $datos_factura['Codigo'] = $datos_factura['Codigo'] ? $datos_factura['Codigo'] : $item[2];
        if($bancos){
            if( $key==0){
                $retencion=TipoMovimiento('Retencion',$item[0]);
                $descuento=TipoMovimiento('Descuento',$item[0]);
                $mayorpagar=TipoMovimiento('Ajuste',$item[0]);
                if($retencion['Id_Plan_Cuentas']){
                     $tem['Id_Retencion']=$retencion['Id_Retencion'];
                     $tem['Id_Plan_Cuenta']=$retencion['Id_Plan_Cuentas'];
                     $tem['Tipo']='Renta';
                     $tem['Valor']=str_replace(",",".",$item[4]);
                     $tem['Cuenta']=$retencion['Codigo'];
                     $tem['Factura']=$item[2];
                     $retenciones[$r]=$tem;
                     $r++;                    
                }

                if($descuento['Id_Plan_Cuentas']){
                    $des['Id_Cuenta_Descuento']=$descuento['Id_Plan_Cuentas'];
                    $des['ValorDescuento']=str_replace(",",".",$item[4]);
                    $des['Descuento']=$descuento;
                    $des['Cuenta']=$descuento['Codigo'];
                    $des['Factura']=$item[2];
                    $descuentos[$d]=$des;
                    $d++;
                }

                if( $mayorpagar['Id_Plan_Cuentas']){
                    $may['MayorPagar']=$mayorpagar;
                    $may['Cuenta']=$mayorpagar['Codigo_Cuenta'];
                    $may['Id_Cuenta_MayorPagar']=$mayorpagar['Id_Cuenta_MayorPagar'];
                    $may['Valor']=$item[4]!=NULL ? str_replace(",",".",$item[4]) : str_replace(",",".",$item[5]);
                    $may['Factura']=$item[2];
                    $ajustes[$a]=$may;
                }

                if(!$retencion['Id_Plan_Cuentas'] && !$descuento['Id_Plan_Cuentas'] && ! $mayorpagar['Id_Plan_Cuentas']){
                    $fact[$f]=ArmarFactura($item, $datos_factura);
                    $f++;
                }
            }
        }/* else{
            $facturas_no_encontradas[$i]=$value;
            $i++;
        } */
    }
}



$i=-1;
foreach ($fact as $value) {$i++;
    $tem=[];
    $j=0;
    $k=-1;
    foreach ($retenciones as $reten) {$k++;
        if($reten['Factura']==$value['Codigo']){
           $tem[$j]=$reten;
           unset($retenciones[$k]);
           $j++;
        }
    }
    $retenciones=array_values($retenciones);
    $fact[$i]['RetencionesFacturas']=$tem;

    $desc=[];
    $j=0;
    $k=-1;
    foreach ($descuentos as  $d) {$k++;
        if($d['Factura']==$value['Codigo']){
            $desc[$j]=$d;
            unset($descuentos[$k]);
            $j++;
         }
    }
    $descuentos=array_values($descuentos);
    $fact[$i]['DescuentosFactura']=$desc;

    foreach ($ajustes as  $a) {
        if($a['Factura']==$value['Codigo']){
            $fact[$i]['MayorPagar']=$a['MayorPagar']; 
            $fact[$i]['Id_Cuenta_MayorPagar']=$a['Id_Cuenta_MayorPagar']; 
            $fact[$i]['ValorMayorPagar']=$a['Valor']; 
            $fact[$i]['Cuenta_Mayor_Pagar']=$a['Cuenta']; 
         }
    }

}
$file=$MY_FILE . "ARCHIVOS/COMPROBANTES/" . $_filename1;
unlink($file);
$resultado['Facturas']=$fact;
$resultado['Faltantes']=$facturas_no_encontradas;

echo json_encode($resultado);


function GetIdFactura($codigo){

    $query="SELECT
    FT.Codigo,
    FT.Id_Factura,

    IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) as Exenta,

    IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) as Gravada,

    IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) as Iva,
    

    (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0)) AS Total_Compra,

    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0)+IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0))-FT.Cuota) AS Neto_Factura,

    0 ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0)+IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0))-FT.Cuota) /*- ( # Restando lo abonado

    SELECT 
    IFNULL(SUM(MC.Haber),0)
    FROM
    Movimiento_Contable MC
    WHERE
        MC.Nit = FT.Id_CLiente AND MC.Documento = FT.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57

    )*/ AS Por_Pagar,

    /*(

    SELECT 
    IFNULL(SUM(MC.Haber),0)
    FROM
    Movimiento_Contable MC
    WHERE
        MC.Nit = FT.Id_CLiente AND MC.Documento = FT.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57

    )*/ 0 AS Pagado, '' as Cuenta_Mayor_Pagar

    FROM Factura FT

    WHERE FT.Estado_Factura = 'Sin Cancelar' AND FT.Codigo='$codigo' AND YEAR(FT.Fecha_Documento) >= 2019
    /*UNION (
        SELECT
        FCM.Factura,
        FCM.Id_Facturas_Cliente_Mantis AS Id_Factura,
        0 AS Exenta,
        0 AS Gravada,
        0 AS Iva,
        FCM.Saldo AS Total_Compra,
        FCM.Saldo AS Neto_Factura,
        0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,
        IF(FC.Pagado IS NOT NULL, FCM.Saldo - FC.Pagado, FCM.Saldo) AS Por_Pagar,
        IFNULL(FC.Pagado, 0) AS Pagado, '' as Cuenta_Mayor_Pagar
        FROM
        Facturas_Cliente_Mantis FCM
        LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' GROUP BY FC.Id_Factura) FC ON FCM.Id_Facturas_Cliente_Mantis = FC.Id_Factura AND FCM.Factura = FC.Factura
        WHERE FCM.Estado = 'Pendiente'
        AND FCM.Factura = '$codigo'
        )*/";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $factura = $oCon->getData();
    unset($oCon);

    return $factura;
}

function EnviarError($tipo){
    global  $http_response, $response;
    $http_response->SetRespuesta(1, 'Error con Factura', 'La factura '.$tipo.' no existe en el sistema, por favor revise!');
	$response = $http_response->GetRespuesta();
}

function TipoMovimiento($tipo, $codigo){
    $query=GetQuery($tipo,$codigo);
    $oCon= new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function GetQuery($tipo, $codigo){
    switch ($tipo) {
        case 'Retencion':
               $query="SELECT R.Id_Retencion, P.Id_Plan_Cuentas,P.Nombre, P.Codigo FROM Retencion R INNER JOIN Plan_Cuentas P ON R.Id_Plan_Cuenta=P.Id_Plan_Cuentas WHERE P.Codigo='$codigo'";
            break;  
            
        case 'Descuento':
                $query="SELECT P.Id_Plan_Cuentas,P.Nombre, P.Codigo FROM Cuenta_Descuento R INNER JOIN Plan_Cuentas P ON R.Id_Plan_Cuenta=P.Id_Plan_Cuentas WHERE P.Codigo='$codigo'";
              break;

        case 'Ajuste':
                $query="SELECT P.Id_Plan_Cuentas,P.Codigo as Codigo_Cuenta, CONCAT(P.Nombre,' - ',P.Codigo) as Codigo, P.Id_Plan_Cuentas as Id_Cuenta_MayorPagar FROM Cuenta_Ajuste R INNER JOIN Plan_Cuentas P ON R.Id_Plan_Cuenta=P.Id_Plan_Cuentas WHERE P.Codigo='$codigo'";
            break;
      
    }
  
    return $query;
}

function ArmarFactura($item, $datos_factura){    
    $fac['Codigo']=$datos_factura['Codigo'];
    $fac['Id_Factura']=$datos_factura['Id_Factura'];
    $fac['Exenta']=$datos_factura['Exenta'];
    $fac['Gravada']=$datos_factura['Gravada'];
    $fac['Iva']=$datos_factura['Iva'];
    $fac['Total_Compra']=$datos_factura['Total_Compra'];
    $fac['Neto_Factura']=$datos_factura['Neto_Factura'];
    $fac['ValorIngresado']=str_replace(",",".",$item[5]);
    $fac['ValorMayorPagar']= $datos_factura['ValorMayorPagar'] != '' ? $datos_factura['ValorMayorPagar'] : '0';
    $fac['ValorDescuento']= $datos_factura['ValorDescuento'] != '' ? $datos_factura['ValorDescuento'] : '0';
    $fac['Por_Pagar']=$datos_factura['Por_Pagar'];
    $fac['Pagado']=$datos_factura['Pagado'];
    $fac['Cuenta_Mayor_Pagar']=$datos_factura['Cuenta_Mayor_Pagar'];
    return $fac;
}
function ValidarBancos($codigo){
    $estado=true;

    $query="SELECT Id_Plan_Cuentas AS value, CONCAT_WS(' ',Codigo,'-',Nombre) AS label, Id_Plan_Cuentas FROM Plan_Cuentas WHERE Banco = 'S' AND Cod_Banco IS NOT NULL AND Codigo like '$codigo' ";


    $oCon= new consulta();
    $oCon->setQuery($query);
    $banco = $oCon->getData();
    unset($oCon);

    if($banco['Id_Plan_Cuentas']){
        $estado=false;
    }

    

    return $estado;
}
?>