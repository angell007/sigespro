<?php

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);   


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');
require_once('../class/class.configuracion.php');
include_once('../class/class.consulta.php');
include_once('../class/class.complex.php');
include_once('../class/class.contabilizar.php');
include_once('./notas_credito_nuevo/helper_consecutivo.php');


$facturas= getFacturas();

//echo count($facturas); exit;

$modulo = 'Factura';

echo "<table><tr><td>#</td><td>FACTURA</td><td>NOTA CREDITO</td><td>VALOR FACTURA</td></tr>";

$i=0;
foreach($facturas as $factura){ $i++;
    
    $cliente = $factura["Id_Cliente"];
    $codigoFactura = $factura["Codigo"];
    $funcionario = $factura["Id_Funcionario"];
    $observaciones = 'AUTOMATICO - Nota Credito Total por ERROR DE FACTURACION';
    $productos = getProductosFactura($factura["Id_Factura"]);
    $reso = '24';
    
    $subTotalGeneral = calcularValorNota($productos);
    
    $cod = generarConsecutivo();
    //$cod = 1;

    $query = 'INSERT INTO Nota_Credito_Global (Tipo_Factura,Observaciones,Id_Factura, Valor_Total_Factura,
    Id_Funcionario, Id_Cliente, Codigo_Factura, Codigo)
    VALUES("' . $modulo . '","'.$observaciones.'",'. $factura['Id_Factura'] .  ','. number_format($subTotalGeneral,2,".",'') . ','
    . $funcionario . ',' . $cliente . ',"' . $factura['Codigo'] . '","' . $cod . '")';
   
    $oCon = new consulta();
    $oCon->setQuery($query);
    //echo $query."<br><br>";
    //exit;
    $oCon->createData();
    $id_nota = $oCon->getID();
    //$id_nota=99999999;
    unset($oCon);
    
    
    echo "<tr><td>".$i."</td><td>".$factura["Codigo"]."</td><td>".$cod."</td><td>".number_format($subTotalGeneral,2,".",",")."</td></tr>";
    if ($id_nota) {

        foreach ($productos as $producto) {
         
            $observacion =  'Producto Nota Credito por ERROR DE FACTURACION';
            $producto_set =  utf8_encode($producto['Descripcion']);
            #guardar productos de la factura a la nota credito
            $query = 'INSERT INTO Producto_Nota_Credito_Global (Id_Nota_Credito_Global, Tipo_Producto, Id_Producto,Nombre_Producto, 
            Valor_Nota_Credito, Observacion, Id_Causal_No_Conforme,Impuesto,Precio_Nota_Credito,Cantidad)
            VALUES(' . $id_nota . ',"Producto_Factura",' . $producto['Id_Producto_Factura'] . ',"' . $producto_set . '",'
                . number_format($producto['Subtotal'],2,".","") . ',"' . $observacion . '",10,'
              .$producto['Impuesto'].','.number_format($producto['Precio'],2,".","").','.$producto['Cantidad']. ')';
            $oCon = new consulta();
            //echo $query."<br><br>";
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
            
            
        }
        
        $fecha = date("Y-m-d H:i:s");
        $query = 'UPDATE ' . $modulo . ' SET  Nota_Credito="Si", Estado_Factura="Nota Credito", Valor_Nota_Credito=' . number_format($subTotalGeneral,2,".","")
            . ', Funcionario_Nota=' . $funcionario . ', Fecha_Nota = NOW() WHERE Id_' . $modulo . ' = ' . $factura['Id_Factura'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        //echo $query;
        //exit;
        $oCon->createData();
        unset($oCon); 
    }
}

echo "</table>";

function calcularValorNota ($productos){
    $resultado=0;
    foreach($productos as $producto){
        $subtotal = ((float)($producto['Cantidad']) * (float)($producto['Precio']) ) ;
        $resultado += $subtotal ;
    }
    return $resultado;
}

function getProductosFactura($idFactura){
    $query='SELECT * FROM Producto_Factura WHERE Id_Factura='.$idFactura;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $prods= $oCon->getData();
    unset($oCon);
    
    return $prods;
}

function getFacturas(){

    $query='SELECT * FROM Factura WHERE Fecha_Documento LIKE "%2025-03-06%";';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $fras= $oCon->getData();
    unset($oCon);
    
    return $fras;
}


