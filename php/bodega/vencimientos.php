<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$year = ( isset( $_REQUEST['year'] ) ? $_REQUEST['year'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : false );
$id_bodega_punto = ( isset( $_REQUEST['id_bodega_punto'] ) ? $_REQUEST['id_bodega_punto'] : false );

$condicion = '';

$condicion = '';
if ($tipo == 'Bodega') {
    if($_REQUEST['id_bodega_punto']!='todos'){
    $condicion .= " WHERE B.Id_Bodega_Nuevo=$_REQUEST[id_bodega_punto]";
    }else{
        $condicion .= " WHERE B.Id_Bodega_Nuevo!=0 ";
    }
} else {
    if($_REQUEST['id_bodega_punto']!='todos'){
        $condicion .= " WHERE E.Id_Punto_Dispensacion=$_REQUEST[id_bodega_punto]";
    }else{
        $condicion .= " WHERE E.Id_Punto_Dispensacion!=0 ";
    }
}

$meses = array('01-Enero','02-Febrero','03-Marzo','04-Abril','05-Mayo','06-Junio','07-Julio','08-Agosto','09-Septiembre','10-Octubre','11-Noviembre','12-Diciembre');

$i=-1;
$resultado = [];
foreach($meses as $mes){ $i++;
    $m=explode("-",$mes);

    $res["Mes"]=$m[1];
    $res["Productos"]=$vencidos;
            
    if ($tipo) {
        if ($tipo == 'Bodega') {
         $query = queryBodega($year,$m);
        } else {    
            $query = queryPunto($year,$m);
        }
    }

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $vencidos = $oCon->getData();
    unset($oCon);
    
    $res["Mes"]=$m[1];
    $res["Productos"]=$vencidos;

    $resultado[]=$res;

}

function queryPunto($year,$m){
    global $condicion ;

    $query='SELECT P.Nombre_Comercial, P.Embalaje, 
    CONCAT( P.Principio_Activo, " ",
    P.Presentacion, " ",
    P.Concentracion, " ",
    P.Cantidad," ",
    P.Unidad_Medida) as Nombre,
    P.Laboratorio_Comercial,
    I.Lote, I.Fecha_Vencimiento,
    CONCAT_WS(" - ", B.Nombre, E.Nombre ) as Bodega,
    (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = I.Id_Punto_Dispensacion) as Punto,
    (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) as Cantidad
    
    FROM Inventario_Nuevo I
    INNER JOIN Producto P
    ON P.Id_Producto = I.Id_Producto
    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
    INNER JOIN Punto_Dispensacion B   ON B.Id_Punto_Dispensacion = E.Id_Punto_Dispensacion 
    '.$condicion.' AND I.Fecha_Vencimiento LIKE "%'.$year."-".$m[0].'%"
    AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) > 0
    ORDER BY I.Fecha_Vencimiento ASC';
  
   /*  $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $vencidos = $oCon->getData();
    unset($oCon); */
    return $query;
    
}
function queryBodega($year,$m){
    global $condicion;

    $query='SELECT P.Nombre_Comercial, P.Embalaje, 
        CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " ",
        P.Cantidad," ",
        P.Unidad_Medida) as Nombre,
        P.Laboratorio_Comercial,
        I.Lote, I.Fecha_Vencimiento,
        CONCAT_WS(" - ", B.Nombre, E.Nombre ) as Bodega,
        "" as Punto,
        (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) as Cantidad
        
        FROM Inventario_Nuevo I
        INNER JOIN Producto P       ON P.Id_Producto = I.Id_Producto
        INNER JOIN Estiba E         ON E.Id_Estiba = I.Id_Estiba
        INNER JOIN Bodega_Nuevo B   ON B.Id_Bodega_Nuevo = E.Id_Bodega_Nuevo 

        '.$condicion.' AND I.Fecha_Vencimiento LIKE "%'.$year."-".$m[0].'%"
        AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) > 0
        ORDER BY I.Fecha_Vencimiento ASC';

    return $query;
}


echo json_encode($resultado);
?>