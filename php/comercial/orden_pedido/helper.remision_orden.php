<?php

function getGrupos()
{
    $query = 'SELECT * FROM Grupo_Estiba WHERE Id_Bodega_Nuevo IS NOT NULL ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $data = $oCon->getData();
    unset($oCon);
    return $data;
}


function armarRem(array $grupos, array &$productos)
{
    foreach ($grupos as $KGrupo => $grupo) {
        foreach ($productos as $KProducto => &$producto) {
            // $productoTemporal = [];
            //$productoTemporal['Lotes'] = [];
        
            if ($producto['Cantidad_Remision'] < $producto['Cantidad']) {
          
                $inventarios = getInventario($producto['Id_Producto'], $grupo['Id_Grupo_Estiba']);
                foreach ($inventarios as $KInventario => $inventario) {
                  
                    if ($producto['Cantidad_Remision'] < $producto['Cantidad']) {
                      
                        $cantidad = 0;
                        if ($inventario['Cantidad'] > ($producto['Cantidad'] - $producto['Cantidad_Remision'])) {
                            $cantidad = $producto['Cantidad'] - $producto['Cantidad_Remision'];
                        } else {
                            $cantidad = $inventario['Cantidad'];
                        }
                        $producto['Cantidad_Remision'] += $cantidad;
                    
                        
                        $inventarios[$KInventario]['Cantidad'] = $cantidad;
                        $inventarios[$KInventario]['Costo'] =  $productos[$KProducto]['Costo'];
                        $inventarios[$KInventario]['Precio'] =  $productos[$KProducto]['Precio_Orden'];
                        $inventarios[$KInventario]['Impuesto'] =  $productos[$KProducto]['Impuesto'];

                        //$productoTemporal[] = $inventarios[$KInventario];
                        $grupos[$KGrupo]['Productos'][]  = $inventarios[$KInventario];
                    } else {

                        break;
                    }
                }
                // if($productoTemporal) $grupos[$KGrupo]['Productos'][] = $productoTemporal;
            }
        }
    }
    $gtemp = array_filter($grupos, "filter_grupos");

    $grupos =  array_values($gtemp);

    foreach ($grupos as $key => $value) {
        $grupos[$key]['Totales'] = getTotales($value['Productos']);
    }
    return $grupos;
}

function filter_grupos($grupo)
{
    return array_key_exists('Productos',  $grupo);
}


function getInventario($idProducto, $idGrupo)
{
    global $bodega;
    $query = 'SELECT  I.Id_Inventario_Nuevo , I.Codigo_CUM, I.Lote, I.Id_Producto, I.Fecha_Vencimiento,
                    ( I.Cantidad - (I.Cantidad_Seleccionada + I.Cantidad_Apartada) ) AS Cantidad
                    FROM Inventario_Nuevo I
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                    INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba 
                    WHERE I.Id_Producto = ' . $idProducto . ' AND G.Id_Grupo_Estiba = ' . $idGrupo . ' AND E.Id_Bodega_Nuevo = ' . $bodega['Id_Bodega_Nuevo'] . '
                    HAVING Cantidad > 0
                    ';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $inv = $oCon->getData();
    unset($oCon);
    return $inv;
}

function createRemision($grupos)
{

    foreach ($grupos as $keyGroup => $grupo) {
        if (array_key_exists('Productos',  $grupo) && count($grupo['Productos']) > 0) {
        }
    }
}

function getTotales($productos)
{

    $Costo = 0;
    $Subtotal = 0;
    $Impuesto = 0;


    foreach ($productos as $key => $prod) {
        $Costo += $prod['Costo'] * $prod['Cantidad'];
        $Subtotal += $prod['Precio'] * $prod['Cantidad'];
        $Impuesto += $prod['Impuesto'] * $prod['Cantidad'];
    }
    return ['Costo' => $Costo, 'Subtotal' => $Subtotal, 'Impuesto' => $Impuesto];
}
