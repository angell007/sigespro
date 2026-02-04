<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');

$dispensaciones = getDispensaciones();

$i=0;
foreach($dispensaciones as $dis){ $i++;

    $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
    $nc = $oItem->getData();
    $oItem->Consecutivo = $oItem->Consecutivo + 1;
    $oItem->save();
    $num_dispensacion = $nc["Consecutivo"];
    unset($oItem);
    $cod = "DIS" . sprintf("%05d", $num_dispensacion);

    $oItem = new complex('Producto',"Id_Producto",$dis["id_producto"]);
    $prod=$oItem->getData();
    unset($oItem);
    
    $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$dis["id_inventario"]);
    $inv=$oItem->getData();
    unset($oItem);
    
    echo $i." - ".$cod." - ".$dis["NUMERO_IDENTIFICACION"]."<br>";
    
    $oItem = new complex("Dispensacion", "Id_Dispensacion");
    $oItem->Codigo = $cod;
    $oItem->Numero_Documento = $dis["NUMERO_IDENTIFICACION"];
    $oItem->Cuota = 0;
    $oItem->EPS = "COOSALUD EPS SA";
    $oItem->Fecha_Formula = date("Y-m-d");
    $oItem->Fecha_Actual =  date("Y-m-d H:m:s");
    $oItem->Cantidad_Entregas = 1;
    $oItem->Entrega_Actual = 1;
    $oItem->Estado_Dispensacion = "Activa";
    $oItem->Estado_Acta = "Sin Validar";
    $oItem->Observaciones = "Dispensacion Automatica entrega COOSALUD EPS - MUNICIPIO ENTREGA: ".$dis["Municipios"];
    $oItem->Productos_Entregados = $dis["CantidadReal"];
    $oItem->Pendientes = 0;
    $oItem->Identificacion_Funcionario = '12345';
    $oItem->Id_Punto_Dispensacion = '3';
    $oItem->Estado_Facturacion = "Sin Facturar";
    $oItem->Id_Servicio = '2';
    $oItem->Id_Tipo_Servicio = '3';
    $oItem->Paciente = $dis["PRIMER_NOMBRE"]." ".$dis["SEGUNDO_NOMBRE"]." ".$dis["PRIMER_APELLIDO"]." ".$dis["SEGUNDO_APELLIDO"];
    $oItem->Tipo_Entrega = "Domicilio";
    $oItem->save();
    $id_dis = $oItem->getId();
    
    //==========PRODUCTO DISPENSACION
    
    $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
    $oItem->Id_Dispensacion=$id_dis;
    $oItem->Id_Producto=$dis["id_producto"];
    $oItem->Id_Inventario_Nuevo=$dis["id_inventario"];
    $oItem->Costo=$inv["Costo"];
    $oItem->Cum=$inv["Codigo_CUM"];
    $oItem->Cum_Autorizado=$inv["Codigo_CUM"];
    $oItem->Lote=$inv["Lote"];
    $oItem->Cantidad_Formulada=$dis["CantidadReal"];
    $oItem->Cantidad_Entregada=$dis["CantidadReal"];
    $oItem->Numero_Prescripcion=$dis["NoPrescripcion"];
    $oItem->Fecha_Carga=date("Y-m-d H:m:s");
    $oItem->Cantidad_Formulada_Total=$dis["CantidadReal"];
    $oItem->save();
    unset($oItem);
    
    $oItem = new complex('A_Entrega_Nutriciones_2025',"Id_Entrega_Nutriciones",$dis["Id_Entrega_Nutriciones"]);
    $oItem->Estado=1;
    $oItem->DISPENSACION=$cod;
    $oItem->save();
    unset($oItem);
    
    //$ruta = '/home/sigesproph/public_html/DIS2025/'.$cod.'.pdf';
    //$url =  "https://sigesproph.com.co/php/dispensaciones/dispensacion_pdf.php?id={$id_dis}&Ruta={$ruta}";
    //$resultado = file_get_contents($url);
    
}


function getDispensaciones()
{
	$queryObj = new QueryBaseDatos();

	$query_productos =
		'SELECT *
		    FROM A_Entrega_Nutriciones_2025 E
		    WHERE E.Estado=0 AND E.Lote=3
		    ;
		';
		// AND E.TALLA LIKE "%ETAPA 4%"
		/*
		    AND E.Actualizado=1 
		    AND E.CRUCE_JURIDICA = "" 
		    AND E.Columna1 NOT LIKE "NORTE DE SANTANDER"
		*/

	$queryObj->SetQuery($query_productos);
	$resultado = $queryObj->ExecuteQuery('multiple');

	return $resultado;
}

?>