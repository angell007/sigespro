<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$iddepartamento = ( isset( $_REQUEST['departamento'] ) ? $_REQUEST['departamento'] : '' );
$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );
$id_auditoria = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query1 = 'SELECT PN.Cum
          FROM Producto_NoPos PN
          Where Id_Departamento = '.$iddepartamento;
          
$oCon= new consulta();
$oCon->setQuery($query1);
$oCon->setTipo('Multiple');
$listcumproducto = $oCon->getData();
unset($oCon);

$query2 = 'SELECT  PA.Nombre,
                   PA.Cantidad_Formulada,
                   A.Nombre_Tipo_Servicio
            FROM Producto_Auditoria PA
            INNER JOIN Auditoria A
                ON PA.Id_Auditoria = A.Id_Auditoria AND A.Punto_Pre_Auditoria ='.$punto.'
            WHERE PA.Id_Auditoria='.$id_auditoria;
  
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productosauditados = $oCon->getData();
unset($oCon);
$i=-1;

$listaproductos=[];
foreach($productosauditados as $productosaudita){$i++;

/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */

$query3 = 'SELECT 
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
            I.Fecha_Vencimiento as Vencimiento,
            I.Lote as Lote,
            I.Id_Inventario_Nuevo as IdInventario,
            I.Cantidad,
            NP.Precio
          FROM Producto_NoPos NP 
          INNER JOIN Producto P ON NP.Cum = P.Codigo_Cum
          LEFT join Inventario_Nuevo I ON NP.Cum= I.Codigo_CUM
          WHERE NP.Cum = P.Codigo_Cum 
          AND CONCAT_WS(" ",
            P.Nombre_Comercial, " - ",
            P.Principio_Activo,
            P.Presentacion,
            P.Concentracion,
            P.Cantidad,
            P.Unidad_Medida
            ) LIKE "%'.$productosaudita["Nombre"].'%"
          Order by I.Fecha_Vencimiento ASC' ;

    $oCon= new consulta();
    $oCon->setQuery($query3);
    $oCon->setTipo('Multiple');
    $productosiguales = $oCon->getData();
    unset($oCon);
    
    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
    $prod["Nombre"]=$productosiguales[0]["Nombre"];
    $prod["Id_Producto"]=$productosiguales[0]["Id_Producto"];
    $prod["Id_Inventario_Nuevo"]=$productosiguales[0]["IdInventario"];
    $prod["Lote"]=$productosiguales[0]["Lote"];
    $prod["Cum"]=$productosiguales[0]["Cum"];
    $prod["Vencimiento"]=$productosiguales[0]["Vencimiento"];
    $prod["precio"]=$productosiguales[0]["Precio"];
    $prod["Numero_Autorizacion"]="";
    $prod["Fecha_Autorizacion"]="";
    $prod["Numero_Prescripcion"]="";
    $prod["Cantidad_Formulada"]=$productosaudita["Cantidad_Formulada"];
    $prod["Cantidad_Entregada"]="";
    $prod["Cantidad"]=$productosiguales[0]["Cantidad"];
    $prod["Entregar_Faltante"]="";
    $prod["Cantidad_Pendiente"]="";
    $prod["Valor_Cuota"]=0;
    
    $listaproductos[$i]["producto"]=$productosiguales[0];
    $listaproductos[$i]["Id_Producto"]=$productosiguales[0]["Id_Producto"];
    $listaproductos[$i]["Id_Inventario_Nuevo"]=$productosiguales[0]["IdInventario"];
    $listaproductos[$i]["Lote"]=$productosiguales[0]["Lote"];
    $listaproductos[$i]["Cum"]=$productosiguales[0]["Cum"];
    $listaproductos[$i]["Vencimiento"]=$productosiguales[0]["Vencimiento"];
    $listaproductos[$i]["Precio"]=$productosiguales[0]["Precio"];
    
    $listaproductos[$i]["Numero_Autorizacion"]='';
    $listaproductos[$i]["Fecha_Autorizacion"]='';
    $listaproductos[$i]["Numero_Prescripcion"]='';
    $listaproductos[$i]["Cantidad_Formulada"]=$productosaudita["Cantidad_Formulada"];
    $listaproductos[$i]["Cantidad_Entregada"]=0;
    $listaproductos[$i]["Cantidad"]=$productosiguales[0]["Cantidad"];
    $listaproductos[$i]["Entregar_Faltante"]="";
    $listaproductos[$i]["Cantidad_Pendiente"]="";
    $listaproductos[$i]["Numero_Autorizacion_D"]=false;
    $listaproductos[$i]["Fecha_Autorizacion_D"]=true;
    $listaproductos[$i]["Numero_Prescripcion_Read"]=true;
    $listaproductos[$i]["Cantidad_Formulada_D"]=true;
    $listaproductos[$i]["Cantidad_Entregada_D"]=false;
    $listaproductos[$i]["Valor_Cuota"]=0;


}
$resultado["Tiposerv"]=$productosauditados[0]["Nombre_Tipo_Servicio"];
$resultado["Productos"]=$listaproductos;


echo json_encode($resultado);
?>