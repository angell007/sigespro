<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$iddepartamento = ( isset( $_REQUEST['departamento'] ) ? $_REQUEST['departamento'] : '' );
$id_punto_dispensacion = ( isset( $_REQUEST['punto_dispensacion'] ) ? $_REQUEST['punto_dispensacion'] : '' );
$buscar_inventario = $_REQUEST['inventario'];

$condicion = '';

if (isset($_REQUEST['cod_barra']) && $_REQUEST['cod_barra'] != "") {
  $condicion .= " AND P.Codigo_Barras LIKE '$_REQUEST[cod_barra]%'";
} else {
  if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
    $condicion .= 'AND (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
  }
  if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
      $condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[lab_com]%'";
  }
  if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen']) {
      $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
  }
}


/* $query1 = 'SELECT PNP.Cum
          FROM Producto_NoPos PNP
          Where Id_Departamento = '.$iddepartamento;
          
$oCon= new consulta();
$oCon->setQuery($query1);
$oCon->setTipo('Multiple');
$listcumproducto = $oCon->getData();
unset($oCon); */

$i=-1;

/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
          if ($buscar_inventario === "true") {
            $query2 = 'SELECT 
          CONCAT_WS(" ",
            P.Nombre_Comercial, " - ",
            P.Principio_Activo,
            P.Presentacion,
            P.Concentracion,
            P.Cantidad,
            P.Unidad_Medida
            ) as Nombre,
          P.Laboratorio_Comercial,
          P.Id_Producto,
          P.Codigo_Cum as Cum,
        P.Laboratorio_Generico,
        P.Embalaje,
          I.Fecha_Vencimiento as Vencimiento,
          I.Lote as Lote,
          I.Id_Inventario_Nuevo as IdInventario,
          (I.Cantidad-I.Cantidad_Apartada) AS Cantidad,
          I.Costo as Precio
        FROM Inventario_Nuevo I 
        INNER JOIN Producto P
        ON P.Id_Producto=I.Id_Producto AND I.Id_Punto_Dispensacion = '.$id_punto_dispensacion.'
        '.$condicion.'
        AND (I.Cantidad-I.Cantidad_Apartada) > 0
        Order by I.Fecha_Vencimiento ASC';
        
          } else {
            $query2 = 'SELECT 
          CONCAT_WS(" ",
            P.Nombre_Comercial, " - ",
            P.Principio_Activo,
            P.Presentacion,
            P.Concentracion,
            P.Cantidad,
            P.Unidad_Medida
            ) as Nombre,
          P.Laboratorio_Comercial,
          P.Id_Producto,
          P.Codigo_Cum as Cum,
        P.Laboratorio_Generico,
        P.Embalaje,
        IF((I.Cantidad-I.Cantidad_Apartada) IS NOT NULL AND (I.Cantidad-I.Cantidad_Apartada) <> 0,I.Fecha_Vencimiento,"0000-00-00") as Vencimiento,
          IF((I.Cantidad-I.Cantidad_Apartada) IS NOT NULL AND (I.Cantidad-I.Cantidad_Apartada) <> 0,I.Lote,"Pendiente") AS Lote,
          IF((I.Cantidad-I.Cantidad_Apartada) IS NOT NULL AND (I.Cantidad-I.Cantidad_Apartada) <> 0,I.Id_Inventario_Nuevo,0) as IdInventario,
          IFNULL((I.Cantidad-I.Cantidad_Apartada),0) as Cantidad, I.Costo as Precio
        FROM Producto
        LEFT JOIN (SELECT * FROM Inventario_Nuevo WHERE Id_Punto_Dispensacion='.$id_punto_dispensacion.') I ON P.Id_Producto=I.Id_Producto
        WHERE P.Codigo_Barras IS NOT NULL
        '.$condicion.'
        GROUP BY P.Id_Producto, Lote
        Order by Nombre ASC';
          }
        
        $oCon= new consulta();
        $oCon->setQuery($query2);
        $oCon->setTipo('Multiple');
        $resultado = $oCon->getData();
        unset($oCon);

/* echo $query2;
exit; */
   
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$listaproductos = $oCon->getData();
unset($oCon);



echo json_encode($listaproductos);

?>