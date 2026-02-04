<?php
function getProductosFacturaVentaNCG($id_nota, $tipo_factura){
    $query = 'SELECT
                NT.Id_Factura, PN.Precio_Nota_Credito, PN.Cantidad AS Cantidad_Nota  ,PN.Impuesto AS Impuesto_Nota, 
                PN.Id_Producto,PN.Id_Producto_Nota_Credito_Global, PN.Id_Nota_Credito_Global,
                PF.Precio_Venta AS Precio_Factura, PF.Cantidad AS Cantidad_Factura, 
                COALESCE( (SELECT Costo FROM Producto_Remision PR
                            WHERE PR.Id_Remision = PF.Id_Remision 
                            AND PR.Id_Producto = PF.Id_Producto 
                            LIMIT 1 ), 0) AS Costo,
                ( (PF.Cantidad * PF.Precio_Venta) - (PF.Cantidad * PF.Descuento) ) AS  Subtotal_Con_Descuento_Factura,
                ( PN.Cantidad * PN.Precio_Nota_Credito ) AS  Subtotal_Con_Descuento
                    
                FROM Producto_Nota_Credito_Global PN
                INNER JOIN Nota_Credito_Global NT ON NT.Id_Nota_Credito_Global = PN.Id_Nota_Credito_Global
                INNER JOIN Producto_Factura_Venta PF ON PF.Id_Producto_Factura_Venta = PN.Id_Producto
                WHERE PN.Id_Nota_Credito_Global = ' . $id_nota;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();

    $costoGeneral = 0;
    foreach ( $productos as $key => $producto ) {
      
        $subtotalProductoNota = $producto['Subtotal_Con_Descuento'];
        $subtotalProductoFactura = $producto['Subtotal_Con_Descuento_Factura']; 

        if ( $subtotalProductoNota == $subtotalProductoFactura ) {
        
            $costoGeneral += $producto['Costo'];
            $productos[$key]['Guardar_Costo'] = true;
        } else {
            # Busco todas las notas hechas  a ese producto de la factura
          
            $query = 'SELECT PN.Precio_Nota_Credito, PN.Cantidad AS Cantidad_Nota, PN.Impuesto,
                    PN.Id_Producto,PN.Id_Producto_Nota_Credito_Global, PN.Id_Nota_Credito_Global
                
                    FROM Producto_Nota_Credito_Global PN
                    INNER JOIN Nota_Credito_Global NT ON NT.Id_Nota_Credito_Global = PN.Id_Nota_Credito_Global
           
                    WHERE NT.Id_Nota_Credito_Global != ' . $producto['Id_Nota_Credito_Global'] . ' AND
                        PN.Id_Producto = ' . $producto['Id_Producto'] . ' AND
                        NT.Id_Factura = ' . $producto['Id_Factura'] . ' AND
                        NT.Tipo_Factura = "Factura_Venta" ';
       
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $productosEnNotas = $oCon->getData();

            foreach ( $productosEnNotas as $key => $productoEnNota ) {
                $subtotalProductoNota += ($productoEnNota['Precio_Nota_Credito'] *  $productoEnNota['Cantidad_Nota']);
            }

            if ( $subtotalProductoNota == $subtotalProductoFactura ) {
                $costoGeneral += $producto['Costo'];
                $productos[$key]['Guardar_Costo'] = true;
            } else {
                $productos[$key]['Guardar_Costo'] = false;
            }
        }

    }
 
    return $productos;
}

function GetTotalesFacturasVentaNCG( $productos ){
    $total_general_facturas = 0;
    $factura_5 = 0;
    $factura_19 = 0;
    $result = array();

    foreach ( $productos as $p ) {

        $total_general_facturas += (floatval($p['Cantidad_Nota']) * floatval($p['Precio_Nota_Credito']));

        if (floatval($p['Impuesto_Nota']) > 0) {
            
            if ((int) $p['Impuesto_Nota'] == 19) {

                $factura_19 += (floatval($p['Cantidad_Nota']) * floatval($p['Precio_Nota_Credito'])) * (floatval($p['Impuesto_Nota'])/100);

            }elseif ( (int)  $p['Impuesto_Nota'] == 5) {

                $factura_5 += (floatval($p['Cantidad_Nota']) * floatval($p['Precio_Nota_Credito'])) * (floatval($p['Impuesto_Nota'])/100);

            }        
        }					
    }
    $result['Total_General'] = ($total_general_facturas + $factura_19 + $factura_5);
    $result['Factura_19'] = $factura_19;
    $result['Factura_5'] = $factura_5;

    return $result;
}


function CalcularCostosProductosPorImpuestoNCGFacturaVenta( $productos ){
			
    $costo_por_impuesto = array();

    foreach ( $productos as $value ) {
        if( $value['Guardar_Costo'] ){

            if ( $value['Id_Producto'] == '' ) {
                $costo_producto = 0;
            } else {
                $costo_producto = $value['Costo'];
            }

          	$imp = $value['Impuesto_Nota'];
            if ( !isset($costo_por_impuesto[$imp] )) {
                $costo_por_impuesto[$imp] = $costo_producto * intval($value['Cantidad_Nota']);
            }else{
                $costo_por_impuesto[$imp] += $costo_producto * intval($value['Cantidad_Nota']);
            }
        }
    }

    return $costo_por_impuesto;
}



function getProductosFacturaAdminNCG( $id_nota ){
    $query='SELECT 
      PD.Cantidad AS Cantidades,
      SUM( ( PD.Cantidad * PD.Precio_Nota_Credito ) ) AS Total_Precio_Con_Descuento,
      SUM( ( PD.Cantidad * PD.Precio_Nota_Credito )   * (PD.Impuesto/100 ) ) AS T_Impuesto,
      DF.Id_Plan_Cuenta
      
      FROM Producto_Nota_Credito_Global PD
      INNER JOIN Descripcion_Factura_Administrativa DF 
      ON DF.Id_Descripcion_Factura_Administrativa = PD.Id_Producto
      WHERE Id_Nota_Credito_Global = '.$id_nota.'
      GROUP BY DF.Id_Plan_Cuenta ';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
  
    return $productos;
}


function getProductosFacturaCapitaNCG($id_nota){
    $query = 'SELECT NT.Id_Factura, PN.Precio_Nota_Credito, PN.Cantidad AS Cantidad_Nota  ,PN.Impuesto AS Impuesto_Nota, 
        PN.Id_Producto,PN.Id_Producto_Nota_Credito_Global, PN.Id_Nota_Credito_Global,
        PF.Precio AS Precio_Factura, PF.Cantidad AS Cantidad_Factura, 
        ( ( PF.Cantidad * PF.Precio) - (PF.Cantidad*PF.Descuento) ) AS  Subtotal_Con_Descuento_Factura,
        ( PN.Cantidad * PN.Precio_Nota_Credito ) AS  Subtotal_Con_Descuento
        
        FROM Producto_Nota_Credito_Global PN

        INNER JOIN Nota_Credito_Global NT ON NT.Id_Nota_Credito_Global = PN.Id_Nota_Credito_Global
        INNER JOIN Descripcion_Factura_Capita PF ON PF.Id_Descripcion_Factura_Capita = PN.Id_Producto
        
        WHERE PN.Id_Nota_Credito_Global = '.$id_nota;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
 
    $costoGeneral = 0;
    foreach ( $productos as $key => $producto ) {
      
        $subtotalProductoNota = $producto['Subtotal_Con_Descuento'];
        $subtotalProductoFactura = $producto['Subtotal_Con_Descuento_Factura']; 

        if ( $subtotalProductoNota == $subtotalProductoFactura ) {
        
            $productos[$key]['Guardar_Costo'] = true;

        } else {
            # Busco todas las notas hechas  a ese producto de la factura
            
            $query = 'SELECT PN.Precio_Nota_Credito, PN.Cantidad AS Cantidad_Nota, PN.Impuesto,
                    PN.Id_Producto,PN.Id_Producto_Nota_Credito_Global, PN.Id_Nota_Credito_Global
                
                    FROM Producto_Nota_Credito_Global PN
                    INNER JOIN Nota_Credito_Global NT ON NT.Id_Nota_Credito_Global = PN.Id_Nota_Credito_Global
           
                    WHERE NT.Id_Nota_Credito_Global != ' . $producto['Id_Nota_Credito_Global'] . ' AND
                        PN.Id_Producto = ' . $producto['Id_Producto'] . ' AND
                        NT.Id_Factura = ' . $producto['Id_Factura'] . ' AND
                        NT.Tipo_Factura = "Factura_Capita" ';
       
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $productosEnNotas = $oCon->getData();

            foreach ( $productosEnNotas as $key => $productoEnNota ) {
                $subtotalProductoNota += ($productoEnNota['Precio_Nota_Credito'] *  $productoEnNota['Cantidad_Nota']);
            }

            if ( $subtotalProductoNota == $subtotalProductoFactura ) {
               
                $productos[$key]['Guardar_Costo'] = true;
            } else {
                $productos[$key]['Guardar_Costo'] = false;
            }
        }

    }
 
    return $productos;
}

function CalcularCostosProductosNCGCapita($codFactura,$productos,$asociacionPrincipal,$asociacionGravado0){

    $costo = 0;
    $costo_producto = [];
    
    foreach ($productos as $value) {
        if ($value['Guardar_Costo']) {
       
            $query = 'SELECT * FROM Movimiento_Contable
                     WHERE Numero_Comprobante = "'.$codFactura.'" AND
                        Id_Plan_Cuenta  <> '.$asociacionPrincipal['Id_Plan_Cuenta'].' AND 
                        Id_Plan_Cuenta  <> '.$asociacionGravado0['Id_Plan_Cuenta'].'
                     ';
                   
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $productosEnMov = $oCon->getData();

        }
    }

    return $productosEnMov;
}
