<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

$tipo_factura = ( isset( $_REQUEST['tipo_factura'] ) ? $_REQUEST['tipo_factura'] : '' );
$id_tipo_servicio = ( isset( $_REQUEST['servicio'] ) ? $_REQUEST['servicio'] : '' );
$eps = ( isset( $_REQUEST['eps'] ) ? $_REQUEST['eps'] : '' );

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
    $condicion .= ' AND (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}
if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
    $condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[lab_com]%'";
}
if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen']) {
    $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
    $condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}

$query = '';

if ($tipo_factura == 'Factura1') {
   

    $tabla=GetTabla($id_tipo_servicio);

    if($tabla!='Producto_NoPos'){
        $from=" FROM $tabla T INNER JOIN Producto P ON T.Codigo_Cum=P.Codigo_Cum ";
        if($condicion!=''){
            $condicion.=" AND T.Nit_EPS=$eps";
        }
    }else{
        $from=" FROM $tabla T INNER JOIN Producto P ON T.Cum=P.Codigo_Cum ";
    }


    $query = 'SELECT P.Nombre_Comercial,
        IF(CONCAT( P.Nombre_Comercial," ",P.Cantidad, " ",P.Unidad_Medida, " (",P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, ") " )="" OR CONCAT( P.Nombre_Comercial," ", P.Cantidad," ",
                P.Unidad_Medida ," (",P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, ") "
               ) IS NULL, CONCAT(P.Nombre_Comercial), CONCAT( P.Nombre_Comercial," ", P.Cantidad," ",
                P.Unidad_Medida, " (",P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion,") " )) as Nombre,        
                IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado,
                IF(PRG.Precio IS NOT NULL, PRG.Precio, T.Precio) AS Precio_Venta_Factura, P.Nombre_Comercial, P.Laboratorio_Comercial, P.Id_Producto, P.Embalaje, P.Invima, P.Presentacion, P.Cantidad_Presentacion, IFNULL(P.Laboratorio_Generico, "No aplica") AS Laboratorio_Generico, P.Gravado, P.Codigo_Cum, P.Imagen '.$from.' 
                    LEFT JOIN Precio_Regulado PRG ON P.Codigo_Cum = PRG.Codigo_Cum WHERE P.Codigo_Barras IS NOT NULL AND P.Codigo_Barras !="" '.$condicion .' GROUP BY P.Id_Producto';

                    
}else{
    $query = 'SELECT T.*,(
        CASE
          WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
          WHEN PRG.Codigo_Cum IS  NULL THEN 0
          
        END
        ) AS Precio_Venta_Factura,IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado  FROM (SELECT P.Nombre_Comercial,
        IF(CONCAT( P.Nombre_Comercial," ",P.Cantidad, " ",P.Unidad_Medida, " (",P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, ") " )="" OR CONCAT( P.Nombre_Comercial," ", P.Cantidad," ",
                P.Unidad_Medida ," (",P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion, ") "
               ) IS NULL, CONCAT(P.Nombre_Comercial), CONCAT( P.Nombre_Comercial," ", P.Cantidad," ",
                P.Unidad_Medida, " (",P.Principio_Activo, " ",
                P.Presentacion, " ",
                P.Concentracion,") " )) as Nombre,  P.Laboratorio_Comercial, P.Id_Producto, P.Embalaje, P.Invima, P.Presentacion, P.Cantidad_Presentacion, IFNULL(P.Laboratorio_Generico, "No aplica") AS Laboratorio_Generico, P.Gravado, P.Codigo_Cum, P.Imagen, SPLIT_STRING(P.Codigo_Cum,"-",1) as Cum_Medicamento
                FROM Producto P 
                WHERE P.Codigo_Barras IS NOT NULL AND P.Codigo_Barras !="" '.$condicion .' GROUP BY P.Id_Producto) T  
                
                LEFT JOIN (SELECT Precio, Codigo_Cum,  SPLIT_STRING(Codigo_Cum,"-",1) as Cum FROM Precio_Regulado GROUP BY Codigo_Cum) PRG ON T.Codigo_Cum=PRG.Codigo_Cum';

                /*** 06/11/2019 PEDRO CASTILLO
                
                se cambia a consulta por expediente por solicitud de Leonardo P,
                ya que al facturar algunos productos que no tienen cum pero si estan regulados,
                no se regulan y afecta informe sismed.
                
                Codigo Anterior en INNER JOIN:
                
                PRG ON T.Codigo_Cum=PRG.Codigo_Cum
                
                ****/
                
                /**** 31 Marzo 2020 11:40am / Augusto Carrillo
                
                
                Se devuelve a consulta por Cum exacto por solicitud de Leo Porras, dice que asume
                responsabilidad si algun producto no se regula pues debe agregar todos los cum exactos
                
                Aprueba Freddy Arciniegas 
                
                
                Codigo Anterior en Inner Join:
                
                 PRG ON T.Cum_Medicamento=PRG.Cum
                 
                ****/
    
}


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

$i=-1;
foreach($resultados as $resultado){$i++;
        $resultados[$i]["Producto"] = $resultado;
}

echo json_encode($resultados);
function GetTabla($id){
    $query="SELECT Tipo_Lista FROM Tipo_Servicio WHERE Id_Tipo_Servicio=$id";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $lista = $oCon->getData();

	return $lista['Tipo_Lista'];
}

?>