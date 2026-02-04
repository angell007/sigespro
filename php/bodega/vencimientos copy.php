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

if ($tipo) {
   
        if ($tipo == 'Bodega') {
            if($_REQUEST['id_bodega_punto']!='todos'){
            $condicion .= " AND I.Id_Bodega=$_REQUEST[id_bodega_punto]";
            }else{
                $condicion .= " AND I.Id_Bodega!=0 ";
            }

        } else {
          
            if($_REQUEST['id_bodega_punto']!='todos'){
                $condicion .= " AND I.Id_Punto_Dispensacion=$_REQUEST[id_bodega_punto]";
                }else{
                    $condicion .= " AND I.Id_Punto_Dispensacion!=0 ";
                }
        }
    
   
}


$meses = array('01-Enero','02-Febrero','03-Marzo','04-Abril','05-Mayo','06-Junio','07-Julio','08-Agosto','09-Septiembre','10-Octubre','11-Noviembre','12-Diciembre');

$i=-1;
$resultado = [];
foreach($meses as $mes){ $i++;
$m=explode("-",$mes);
    $query='SELECT P.Nombre_Comercial, P.Embalaje, 
            CONCAT( P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " ",
            P.Cantidad," ",
            P.Unidad_Medida) as Nombre,
            P.Laboratorio_Comercial,
            I.Lote, I.Fecha_Vencimiento,
            (SELECT Nombre FROM Bodega WHERE Id_Bodega = I.Id_Bodega) as Bodega,
            (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = I.Id_Punto_Dispensacion) as Punto,
            (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) as Cantidad
            
            FROM Inventario I
            INNER JOIN Producto P
            ON P.Id_Producto = I.Id_Producto
            WHERE I.Fecha_Vencimiento LIKE "%'.$year."-".$m[0].'%"
            '.$condicion.' AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) > 0
            ORDER BY I.Fecha_Vencimiento ASC';

            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $vencidos = $oCon->getData();
            unset($oCon);
            
        $res["Mes"]=$m[1];
        $res["Productos"]=$vencidos;
        
        $resultado[]=$res;
}

echo json_encode($resultado);
?>