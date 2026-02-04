<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');

$Total_General_Producto_Facturas;
$Subtotal_General_Notas;
if ($id == '') {
    echo json_encode(array());
    return;
}

if ($modelo == 'Factura_Capita' || $modelo == 'Factura_Administrativa' || $modelo == 'Documento_No_Obligados') {
    $modelo_producto = 'Descripcion_' . $modelo;
} else {
    $modelo_producto = 'Producto_' . $modelo;
}
$select = select_db($modelo_producto);
$joins = joins_db($modelo_producto);
$query = $select . ' "true" as Disabled , "' . $modelo . '"  Nombre_Modelo , "' . $modelo_producto . '" 
     Nombre_Modelo_Producto FROM ' . $modelo_producto . ' PF  
                ' . $joins . '
                WHERE PF.Id_' . $modelo . ' =' . $id;


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

buscarProductosEnNota($productos,$id,$modelo);

 $response['Productos'] = $productos;
 $response['Total_General_Producto_Facturas'] =  $Total_General_Producto_Facturas;
 $response['Subtotal_Disponible'] =  $Total_General_Producto_Facturas - $Subtotal_General_Notas;
 $response['Subtotal_General_Notas'] =  $Subtotal_General_Notas;
 
echo json_encode($response);







#funciones

function select_db($modelo_producto)
{
    global $modelo;
    //GENERALES
    $select = 'SELECT PF.Cantidad, PF.Id_' . $modelo_producto . ' AS Id_Modelo, PF.Descuento, PF.Impuesto,';

    //productos y ids modelo producto
    if ($modelo_producto == 'Descripcion_Factura_Capita' || $modelo_producto == 'Descripcion_Factura_Administrativa' || $modelo_producto == 'Descripcion_Documento_No_Obligados') {
        # code...
        $select .= 'PF.Descripcion AS Producto, PF.Id_Descripcion_' . $modelo . ' AS Id_Modelo_Producto,';
    } else {
        $select .= 'IFNULL(CONCAT(P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as Producto, 
            P.Id_Producto,  IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
            P.Presentacion, P.Codigo_Cum as Cum,  
            PF.Id_' . $modelo_producto . ' AS Id_Modelo_Producto,
            ';
    }

    // seleccionar precio
    if ($modelo_producto == 'Producto_Factura_Venta') {
        # code...
        $select .= 'PF.Precio_Venta AS Precio, ';
    } else {
        $select .= 'PF.Precio,';
    }


    return $select;
}

function joins_db($modelo_producto)
{
    $join = '';
    if ($modelo_producto != 'Descripcion_Factura_Capita' && $modelo_producto != 'Descripcion_Factura_Administrativa' && $modelo_producto !== 'Descripcion_Documento_No_Obligados') {
        $join .=  'LEFT JOIN Producto P ON PF.Id_Producto = P.Id_Producto';
    }
    return $join;
}

function buscarProductosEnNota(&$productos,$id_factura,$modelo){
    global   $Total_General_Producto_Facturas, $Subtotal_General_Notas; 
 
    $query='SELECT Id_Nota_Credito_Global FROM Nota_Credito_Global WHERE Id_Factura = '.$id_factura.' AND Tipo_Factura = "'.$modelo.'"';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $notas_credito= $oCon->getData();
    unset($oCon);
    if ($notas_credito) {
        //buscar productos de las notas creditos y armar uno solo
        foreach ($notas_credito as $key => $nota_credito) {
            # code...
           
          
            $query='SELECT * FROM Producto_Nota_Credito_Global WHERE Id_Nota_Credito_Global = '.$nota_credito['Id_Nota_Credito_Global'];
          
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $productos_nota_credito= $oCon->getData();
            unset($oCon);
          
             $notas_credito[$key]['Productos_Nota']=$productos_nota_credito;

            
        }
            #busco productos que tengan notas credito
         $Total_General_Producto_Facturas=0;
         $Subtotal_General_Notas=0;
         foreach ($productos as $key => $producto) {
             
            # code...
            $producto[$key]['Valor_Nota_Credito_Acumulado'] = 0;
            $productos[$key]['Valor_Nota_Acumulado'] = 0;
           
            foreach ($notas_credito as $nota_credito) {
                # code...
                foreach ($nota_credito['Productos_Nota'] as $producto_notas) {
                    # code...
                   
                    if ($producto['Id_Modelo_Producto']==$producto_notas['Id_Producto']) {
                       
                        $productos[$key]['Valor_Nota_Credito_Acumulado'] += calcularValorNota($producto_notas);
                        $productos[$key]['Precio_Nota_Credito_Acumulado'] += $producto_notas['Precio_Nota_Credito'];
                    }
                }
              
            }
            $Total_Producto_Factura = calcularSubtotalProductoFactura($producto);
            $Total_General_Producto_Facturas += $Total_Producto_Factura;
            
            $productos[$key]['Total_Producto_Factura'] = $Total_Producto_Factura;
            $productos[$key]['Subtotal_Producto_Factura_Sin_Iva'] = calcularSubtotalProductoFacturaSinIva($producto);
            if ($productos[$key]['Valor_Nota_Credito_Acumulado']) {
                $Subtotal_General_Notas += $productos[$key]['Valor_Nota_Credito_Acumulado'];
                
                
            }else{
                $productos[$key]['Valor_Nota_Credito_Acumulado'] = 0;
                $productos[$key]['Precio_Nota_Credito_Acumulado'] = 0;
            }

            $productos[$key]['Valor_Nota'] = 0;
            $productos[$key]['Valor_Nota_Total'] = 0;

        } 
     
       
    

    }else{
        
        foreach ($productos as $key => $producto) {
            # code...
            $Total_Producto_Factura = calcularSubtotalProductoFactura($producto);
            $Total_General_Producto_Facturas += $Total_Producto_Factura;
            $productos[$key]['Subtotal_Producto_Factura_Sin_Iva'] = calcularSubtotalProductoFacturaSinIva($producto);
            $productos[$key]['Total_Producto_Factura'] = $Total_Producto_Factura;
            $productos[$key]['Valor_Nota_Credito_Acumulado'] = 0;
            $productos[$key]['Precio_Nota_Credito_Acumulado'] = 0;
            $productos[$key]['Valor_Nota'] = 0;
            $productos[$key]['Valor_Nota_Total'] = 0;
        } 
        $Subtotal_General_Notas=0;
    }
}

function calcularSubtotalProductoFactura($producto){
    $valor_iva = ((float)($producto['Impuesto'])/100) * ( ((float)($producto['Cantidad']) * (float)($producto['Precio']) ) - ( (float)($producto['Cantidad']) * (float)($producto['Descuento']) ) );
    $subtotal = ((float)($producto['Cantidad']) * (float)($producto['Precio']) ) - ( (float)($producto['Cantidad']) * (float)($producto['Descuento']) );
    $resultado = $subtotal + $valor_iva;
  
    return $resultado;
}

function calcularSubtotalProductoFacturaSinIva($producto){
    $subtotal = ((float)($producto['Cantidad']) * (float)($producto['Precio']) ) - ( (float)($producto['Cantidad']) * (float)($producto['Descuento']) );
    $resultado = $subtotal ;
  
    return $resultado;
}

function calcularValorNota ($producto){
    $valor_iva = ((float)($producto['Impuesto'])/100) * ( ((float)($producto['Cantidad']) * (float)($producto['Precio_Nota_Credito']) )  );
    $subtotal = ((float)($producto['Cantidad']) * (float)($producto['Precio_Nota_Credito']) ) ;
    $resultado = $subtotal + $valor_iva;
  
    return $resultado;
}
