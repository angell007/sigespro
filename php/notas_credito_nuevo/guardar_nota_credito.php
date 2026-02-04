<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.contabilizar.php');
include_once('./helper_consecutivo.php');

include_once('../../class/class.nota_credito_electronica_estructura.php');

//variables 
$modulo = (isset($_REQUEST['modulo']) ? $_REQUEST['modulo'] : '');
$subTotalGeneral = (isset($_REQUEST['subTotalGeneral']) ? (float) $_REQUEST['subTotalGeneral'] : '');
$total = (isset($_REQUEST['total']) ? (float) $_REQUEST['total'] : '');
$cliente = (isset($_REQUEST['cliente']) ? $_REQUEST['cliente'] : '');
$codigoFactura = (isset($_REQUEST['codigoFactura']) ? $_REQUEST['codigoFactura'] : '');
$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$observaciones = (isset($_REQUEST['observaciones']) ? $_REQUEST['observaciones'] : '');

$factura = (isset($_REQUEST['factura']) ? $_REQUEST['factura'] : '');
$factura = json_decode($factura, true);

$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$productos = json_decode($productos, true);

$productosNotas = (isset($_REQUEST['productosNotas']) ? $_REQUEST['productosNotas'] : '');
$productosNotas = json_decode($productosNotas, true);

//$contabilizar = new Contabilizar();
$Subtotal_General_Notas=0;
$nota = buscarProductosEnNota($factura['Id_Factura'],$modulo);
$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : '24');


if (!$nota['tipo']) {
   //$config = new Configuracion();

    //$cod = $config->getConsecutivo('Nota_Credito_Global', 'Nota_Credito_Global');

    $cod = generarConsecutivo();
    //var_dump($cod);
    
    unset($config);
    #crear la nota credito
    $observaciones = utf8_encode($observaciones);
    $query = 'INSERT INTO Nota_Credito_Global (Tipo_Factura,Observaciones,Id_Factura, Valor_Total_Factura,
    Id_Funcionario, Id_Cliente, Codigo_Factura, Codigo)
    VALUES("' . $modulo . '","'.$observaciones.'",'. $factura['Id_Factura'] .  ','. number_format($subTotalGeneral,2,".",'') . ','
    . $funcionario . ',' . $cliente . ',"' . $factura['Codigo'] . '","' . $cod . '")';
//echo $query;
    //exit;
    $oCon = new consulta();
    $oCon->setQuery($query);
    
    $oCon->createData();
    $id_nota = $oCon->getID();
    unset($oCon);

  
    if ($id_nota) {

        foreach ($productosNotas as $producto) {
         
            $observacion =  utf8_encode($producto['Observacion']);
            $producto_set =  utf8_encode($producto['Producto']);
            #guardar productos de la factura a la nota credito
            $query = 'INSERT INTO Producto_Nota_Credito_Global (Id_Nota_Credito_Global, Tipo_Producto, Id_Producto,Nombre_Producto, 
            Valor_Nota_Credito, Observacion, Id_Causal_No_Conforme,Impuesto,Precio_Nota_Credito,Cantidad)
            VALUES(' . $id_nota . ',"' . $producto['Nombre_Modelo_Producto'] . '",' . $producto['Id_Modelo_Producto'] . ',"' . $producto_set . '",'
                . number_format($producto['Valor_Nota_Total'],2,".","") . ',"' . $observacion . '",' . $producto['Id_Motivo'] .','
              .$producto['Impuesto'].','.number_format($producto['Precio_Nota'],2,".","").','.$producto['Cantidad']. ')';
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        }

        //contabilizar
       
      
        $contabilizar = new Contabilizar();

		if($modulo == 'Factura_Capita'){
          	$subtotal = 0;
            foreach ($productosNotas as $producto) {
          		$subtotal +=  $producto['Precio_Nota'] * $producto['Cantidad'];
            }
          	$datos['Subtotal']=$subtotal;
        }	
        $datos['Id_Registro']=$id_nota;
        $datos['Tipo_Factura']= str_replace('_'," ",$modulo);;
        $datos['Nit'] = $cliente;
       
    	$contabilizar->CrearMovimientoContable('Nota Credito Global',$datos);

        unset($contabilizar);
  
  
        if ($subTotalGeneral == $Subtotal_General_Notas) {

            $fecha = date("Y-m-d H:i:s");
            $query = 'UPDATE ' . $modulo . ' SET  Nota_Credito="Si", Valor_Nota_Credito=' . $subTotalGeneral
                . ', Funcionario_Nota=' . $funcionario . ', Fecha_Nota = NOW() WHERE Id_' . $modulo . ' = ' . $factura['Id_Factura'];
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);  

            //cambiar estados
            if ($modulo == 'Factura') {
                //actualizo la dis
                $query = 'UPDATE Dispensacion SET Estado_Facturacion = "Sin Facturar" WHERE Id_Dispensacion = ' . $factura['Id_Dispensacion'];
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);
/*
                $query = 'SELECT Id_Producto_Dispensacion_Mipres 
                                FROM Producto_Dispensacion WHERE Id_Dispensacion = ' . $factura['Id_Dispensacion'];
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $producto= $oCon->getData();
                $oItem = new complex();*/
            }
            if ($modulo == 'Factura_Capita') {
                //busco las dispensaciones asociadas a esa factura capita
                $query = 'Select Id_Dispensacion From Dispensacion WHERE Id_Tipo_Servicio = 7 AND Id_Factura = ' . $factura['Id_Factura'];
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $dispensaciones = $oCon->getData();
                unset($oCon);
                //cambiar el estado de la dis
                foreach ($dispensaciones as $dispensacion) {
                    # code...
                    $oItem = new complex('Dispensacion', 'Id_Dispensacion', $dispensacion['Id_Dispensacion']);
                    $oItem->Estado_Facturacion = 'Sin Facutar';
                    $oItem->save();
                    unset($oItem);
                }
            }
                //cambiar estados
             if ($modulo == 'Factura_Venta') {
                //actualizo la REM 
                $query = 'UPDATE Remision SET Estado = "Enviada" , Id_Factura = NULL 
                              WHERE Id_Factura = ' . $factura['Id_Factura'];

                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);

            }
            
            
        }
        $result['tipo'] = 'success';
        $result['title'] = 'Nota crédito generada exitosamente';
        $result['mensaje'] = 'Se ha generado la nota crédito con el siguiente Codigo: ' . $cod;
        
        $fe = new NotaCreditoElectronica('Nota_Credito_Global', $id_nota, $reso);
        $result['Nota'] = $fe->GenerarNota();
        $result['Id_Nota'] = $id_nota;

        unset($oCon);
    } else {
        $result['tipo'] = 'error';
        $result['title'] = 'Error inesperado';
        $result['mensaje'] = 'Ha ocurrido un error al guardar la nota crédito';
    }
}else{
    $result['tipo'] = 'error';
    $result['title'] = 'Error, factura con nota crédito';
    $result['mensaje'] = $nota['mensaje'];
}

echo json_encode($result);


function validarExistenciaNota($modulo, $factura)
{

    $query = 'SELECT Id_' . $modulo . ' AS Id_Factura, Nota_Credito FROM ' . $modulo . ' WHERE  Id_' . $modulo . '=' . $factura['Id_Factura'];

    $oCon = new consulta();
    $oCon->setQuery($query);
    $factura = $oCon->getData();
    unset($oCon);

    if ($factura['Nota_Credito']) {
        return true;
    } else if ($factura) {
        $result=buscarProductosEnNota($modulo,$factura['Id_Factura']);
        return $result;
    }

    return ['tipo'=>true,'mensaje'=>'A esta factura ya se le realizó nota crédito previamente, por favor verifique' ];
}



function buscarProductosEnNota($id_factura,$modelo){
    global   $Subtotal_General_Facturas, $Subtotal_General_Notas,$Subtotal_General_Notas, $productos; 
 
    $query='SELECT Id_Nota_Credito_Global FROM Nota_Credito_Global WHERE Id_Factura = '.$id_factura.' AND Tipo_Factura = "'.$modelo.'"';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $notas_credito= $oCon->getData();
    unset($oCon);
    if ($notas_credito) {
        //buscar productos de las notas creditos y armar uno solo
        foreach ($notas_credito as $key => $nota_credito) {
            $query='SELECT * FROM Producto_Nota_Credito_Global WHERE Id_Nota_Credito_Global = '.$nota_credito['Id_Nota_Credito_Global'];
          
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $productos_nota_credito= $oCon->getData();
            unset($oCon);
          
             $notas_credito[$key]['Productos_Nota']=$productos_nota_credito;

            
        }
            #busco productos que tengan notas credito
         $Subtotal_General_Facturas=0;
         $Subtotal_General_Notas=0;
         $Subtotal_General_Precios = 0;
         //productos con notas
         foreach ($productos as $key => $producto) {
            # code...
            $productos[$key]['Valor_Nota_Credito_Acumulado_Actualizado'] = 0;
          
            foreach ($notas_credito as $nota_credito) {
                # code...
                foreach ($nota_credito['Productos_Nota'] as $producto_notas) {
                    # code...
                   
                    if ($producto['Id_Modelo_Producto']==$producto_notas['Id_Producto']) {
                       
                        $productos[$key]['Valor_Nota_Credito_Acumulado_Actualizado'] += calcularValorNota($producto_notas);;
                      /*   $productos[$key]['Precio_Acumulado_Actualizado'] += $producto_notas['Precio_Nota_Credito']; */
                   
                    }
                }
              
            }
              
       
            
     
            
            //POR SUBTOTAL
            if ($productos[$key]['Valor_Nota_Credito_Acumulado_Actualizado']) {
                $Subtotal_General_Notas += $productos[$key]['Valor_Nota_Credito_Acumulado_Actualizado'] + $productos[$key]['Valor_Nota_Total'] ;
             /*    $productos[$key]['Valor_Nota'] = $subtotalPorProductoFactura - $productos[$key]['Valor_Nota_Credito_Acumulado']; */
                

            }else{

                $productos[$key]['Valor_Nota_Credito_Acumulado_Actualizado'] = 0;
                $Subtotal_General_Notas += $productos[$key]['Valor_Nota_Total'];
              /*   $productos[$key]['Valor_Nota'] = $subtotalPorProductoFactura; */
            }


        

            

            $nota_total_producto = $productos[$key]['Valor_Nota_Credito_Acumulado_Actualizado'] + $productos[$key]['Valor_Nota_Total'];
           /*  $nota_total_precio_producto = $productos[$key]['Precio_Acumulado_Actualizado'] + $productos[$key]['Precio_Nota']; */

         
        
            if ($nota_total_producto > $productos[$key]['Total_Producto_Factura']) {
                # error la nota no puede ser mayor
                $res=['tipo'=>true,'mensaje'=>'El producto '.$productos[$key]['Producto'].' Supera el valor disponible, actualice y vuelva a intentar' ];
                return $res;
            }
            

        } 

     

        $res=['tipo'=>false];
        return $res;



     
       
    

    }else{
        
        #todo ok para realizar nota
        foreach ($productos as $key => $producto) {
            # code...
            $Subtotal_General_Notas += $productos[$key]['Valor_Nota_Total'];
        }
        $res=['tipo'=>false];
        return $res;
    }
}


function calcularValorNota ($producto){
    $valor_iva = ((float)($producto['Impuesto'])/100) * ( ((float)($producto['Cantidad']) * (float)($producto['Precio_Nota_Credito']) )  );
    $subtotal = ((float)($producto['Cantidad']) * (float)($producto['Precio_Nota_Credito']) ) ;
    $resultado = $subtotal + $valor_iva;
  
    return $resultado;
}

