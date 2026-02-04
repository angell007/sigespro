<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id_bodega']) ? $_REQUEST['id_bodega'] : false;

$query = 'SELECT S.*, C.Nombre As Categoria_Nueva FROM Subcategoria S
    INNER JOIN Categoria_Nueva C ON S.Id_Categoria_Nueva = C.Id_Categoria_Nueva
     ORDER BY Id_Categoria_Nueva ';
          


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();

$resultado=separarPorEstiba($resultado);
 //$porCategorias[$['']]

// foreach ($resultado as $key => $value) {
//     # code...
// }

unset($oCon);
          
echo json_encode($resultado);



 function separarPorEstiba($resultado)
 {
    $porCategorias=[];
    $porCategorias[0]['Categoria_Nueva']=$resultado[0]['Categoria_Nueva'];
    $porCategorias[0]['Subcategorias']=[];
    
     $XEstiba = 0; //index por Estiba
     $XProducto = 0; //index Por Producto 

      foreach ($resultado as $key => $categorias) {
          
          if ($porCategorias[$XEstiba]['Categoria_Nueva'] == $categorias['Categoria_Nueva']) {
              $porCategorias[$XEstiba]['Subcategorias'][$XProducto]['Nombre_Subcategoria'] = $categorias['Nombre'];
              $porCategorias[$XEstiba]['Subcategorias'][$XProducto]['Id_Subcategoria'] = $categorias['Id_Subcategoria'];
              $XProducto++;
           } else {
               $XEstiba++;
               $XProducto = 0;
               $porCategorias[$XEstiba]['Categoria_Nueva'] = $categorias['Categoria_Nueva'];
               $porCategorias[$XEstiba]['Subcategorias'][$XProducto]['Nombre_Subcategoria'] = $categorias['Nombre'];
               
               $porCategorias[$XEstiba]['Subcategorias'][$XProducto]['Id_Subcategoria'] = $categorias['Id_Subcategoria'];
               $XProducto++;
           }
      }
     echo json_encode($porCategorias);exit;
     return $porCategorias;
 }

?>