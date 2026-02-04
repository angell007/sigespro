<?php

//no es
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// require_once('../../config/start.inc.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
// include_once('../../class/class.consulta.php');
// $idremision = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

// $query = 'SELECT PR.Lote, PR.Fecha_Vencimiento, P.Id_Subcategoria, I.Id_Inventario_Nuevo,  E.Nombre AS Nombre_Estiba,
//         IFNULL(CONCAT( P.Principio_Activo, " ",
//             P.Presentacion, " ",
//             P.Concentracion, " (", P.Nombre_Comercial,") ",
//             P.Cantidad," ",
//             P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ), CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) AS Nombre_Producto,
//         PR.Cantidad, PR.Precio, PR.Descuento, PR.Impuesto, PR.Subtotal
// FROM Producto_Remision PR
// INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
// INNER JOIN Inventario_Nuevo I ON PR.Id_Inventario_Nuevo = I.Id_Inventario_Nuevo
// INNER JOIN Estiba E ON E.Id_Estiba =I.Id_Estiba
// WHERE PR.Id_Remision='.$idremision.' ORDER BY E.Nombre DESC, Nombre_Producto';

// $oCon= new consulta();
// $oCon->setQuery($query);
// $oCon->setTipo('Multiple');
// $productos = $oCon->getData();
// unset($oCon);

//  echo json_encode($productos);

// $productosByEstiba = [];





// $productosByEstiba[0]['Nombre_Estiba']=$productos[0]['Nombre_Estiba'];
// $productosByEstiba[0]['productos']=[];

// $XEstiba=0; //index por Estiba
// $XProducto=0; //index Por Producto 



//  foreach ($productos as $key => $producto) {
//    if ($producto['Nombre_Estiba'] == $productosByEstiba[$XEstiba]['Nombre_Estiba'] ) {
//         $productosByEstiba[$XEstiba]['productos'][$XProducto]=$producto;
//         $XProducto++;

//    }else{
//        $XEstiba++;
//        $XProducto=0;
//        $productosByEstiba[$XEstiba]['Nombre_Estiba']=$producto['Nombre_Estiba'];
//        $productosByEstiba[$XEstiba]['productos'][$XProducto]=$producto;
//        $XProducto++;
//    }
//   }
  






// echo json_encode($productosByEstiba);
?>