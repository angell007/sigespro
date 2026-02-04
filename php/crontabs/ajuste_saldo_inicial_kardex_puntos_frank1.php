<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','256M');
/* header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); */
//header('Content-Type: application/json');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");

$queryInsert = [];
$punto_select = $_REQUEST['Punto'];
/*$oItem = new complex('Punto_Dispensacion', 'Id_Punto_Dispensacion', 1);
$res = $oItem->getData();
unset($oItem);
*/

//$punto_select=92;
$query = "SELECT Id_Producto, Lote, Fecha_Vencimiento, Id_Bodega, Id_Punto_Dispensacion, Cantidad, Id_Inventario, Costo 
FROM Inventario WHERE  Id_Punto_Dispensacion =$punto_select  /*AND Id_Producto IN (678,730)
/* AND Id_Producto=22000*/
ORDER BY Id_Punto_Dispensacion, Id_Producto, Id_Inventario LIMIT 705,30";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
$j=0;
$k=0;
$texto='';
$total=0;

$oItem = new complex('Punto_Dispensacion', 'Id_Punto_Dispensacion', $punto_select);
$punto_ver = $oItem->getData();
unset($oItem);


echo "\n\n SALDO INICIAL KARDEX PARA PUNTOS ---".$punto_ver["Nombre"]."\n\n <br><br><br>";

foreach($productos as $prod){ $k++;
    $condicion = '';
    $condicion2=''; 
    $condicion3=''; 
    $condicion4=''; 
    $condicion5=''; 
    
    $ruta = '';
    $tabla = '';
    $tablaDest = '';
    $attrFecha = '';
    $query_dispensaciones = '';
    $comprobacion = array();
    $ids_inv = '';
    
   //$fecha1 =date('Y-m-01');
   //$fecha2 =date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha1)),1,date("Y",strtotime($fecha1)))-1));
    $fecha1='2018-10-01';
    $fecha2='2019-04-30';
    $acum=0;
    $resultados =[];
    $producto = $prod["Id_Producto"];
    $tipo = $prod['Id_Bodega'] != 0 ? 'Bodega' : 'Punto_Dispensacion';
    $idTipo = $prod['Id_Bodega'] != 0 ? $prod['Id_Bodega'] : $prod['Id_Punto_Dispensacion'];

    $sql_acta_recepcion_bodegas='';

    if ($tipo == 'Bodega') {
        $condicion .= "  AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Bodega'";
        $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Bodega'";
        $condicion2 .= " AND AR.Id_Bodega=$idTipo";
        $condicion4 .= " AND INF.Bodega=$idTipo";
        $condicion5 .= " AND Id_Bodega=$idTipo";
        $ruta = 'actarecepcionver';
        $tabla = 'Acta_Recepcion';
        $tablaDest = 'Bodega';
        $attrFecha = 'Fecha_Creacion';        
    } else {

    	$query_comprobar = '
    		SELECT 
	    		INF.Id_Inventario_Fisico_Punto AS Id,
				INF.Fecha_Fin
	        	FROM Producto_Inventario_Fisico_Punto PIF 
		        INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto 
		        WHERE 
		        	INF.Estado="Terminado" 
		        	AND PIF.Id_Producto = ' . $producto
		        	." AND ( PIF.Lote='".$prod["Lote"]."'" . ') 
		        	AND INF.Id_Punto_Dispensacion = ' . $idTipo 
		        	. ' AND INF.Fecha_Fin BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" 
	        	GROUP BY PIF.Id_Producto, INF.Fecha_Fin';

    	$oCon= new consulta();
		$oCon->setQuery($query_comprobar);
		$oCon->setTipo('multiple');
		$comprobacion = $oCon->getData();
		unset($oCon);

		if (count($comprobacion) > 0) {

		

			foreach ($comprobacion as $value) {
				
				$query_invs = '
					SELECT 
			    		GROUP_CONCAT(INF.Id_Inventario_Fisico_Punto) AS Ids
		        	FROM Inventario_Fisico_Punto INF
			        WHERE 
			        	INF.Estado="Terminado"
			        	AND INF.Id_Punto_Dispensacion = ' . $idTipo 
			        	. ' AND INF.Fecha_Fin = "'.$value["Fecha_Fin"].'"';

	        	$oCon= new consulta();
				$oCon->setQuery($query_invs);
				$result = $oCon->getData();	
				$ids_inv .= $result['Ids'].",";
				unset($oCon);
			}				

			$ids_inv = trim($ids_inv, ",");
		}else{

			$ids_inv = '0';
		}


	
		//exit;


        $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Punto_Dispensacion'";
        $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Punto'";
        $condicion2 .= " AND AR.Id_Punto_Dispensacion=$idTipo";
        $condicion4 .= " AND INF.Bodega=''";
        $condicion5 .= " AND Id_Punto_Dispensacion=$idTipo";
        $ruta = 'actarecepcionremisionver';
        $tabla = 'Acta_Recepcion_Remision';
        $tablaDest = 'Punto_Dispensacion'; 
        $attrFecha = 'Fecha';
    
        $sql_acta_recepcion_bodegas='UNION ALL (SELECT AR.Id_Acta_Recepcion as ID, '.getOrigenActa('Acta_Recepcion').' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "actarecepcionver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha_Creacion as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
        FROM Producto_Acta_Recepcion PAR
        INNER JOIN Acta_Recepcion AR
        ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
        WHERE  PAR.Lote="'.$prod["Lote"].'" /* AND PAR.Fecha_Vencimiento="'.$prod["Fecha_Vencimiento"].'"*/ AND  PAR.Id_Producto = '.$producto.$condicion2. ')';
 //echo $sql_acta_recepcion_bodegas; exit;
        $query_dispensaciones .= '
	        UNION ALL 
		    		(SELECT 
		    		INF.Id_Inventario_Fisico_Punto AS ID, 
		    		"" AS Nombre_Origen, 
		        	(SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, 
		        	"inventario_fisico_puntos/descarga_pdf.php" AS Ruta, 
		        	"Inventario" AS Tipo, 
		        	CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, 
		        	INF.Fecha_Fin AS Fecha, SUM(PIF.Cantidad_Final) AS Cantidad,
		        	GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, 
		        	GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, 
		        	"" AS Id_Factura, 
		        	"" AS Codigo_Fact 
		        	FROM Producto_Inventario_Fisico_Punto PIF 
			        INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto 
			        WHERE 
			        	INF.Estado="Terminado" 
			        	AND PIF.Id_Producto = ' . $producto
			        	." AND ( PIF.Lote='".$prod["Lote"]."'" . ') 
			        	AND INF.Id_Punto_Dispensacion = ' . $idTipo 
			        	. ' AND INF.Fecha_Fin BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" 
		        	GROUP BY PIF.Id_Producto, INF.Fecha_Fin)
				UNION
					(SELECT 
		    		INF.Id_Inventario_Fisico_Punto AS ID, 
		    		"" AS Nombre_Origen, 
		        	(SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, 
		        	"inventario_fisico_puntos/descarga_pdf.php" AS Ruta, 
		        	"Inventario" AS Tipo, 
		        	CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, 
		        	INF.Fecha_Fin AS Fecha,
		        	0 AS Cantidad,
		        	"" AS Lote, 
		        	"" AS Fecha_Vencimiento, 
		        	"" AS Id_Factura, 
		        	"" AS Codigo_Fact 
		        	FROM Inventario_Fisico_Punto INF
			        WHERE 
			        	INF.Estado="Terminado" 
			        	AND INF.Id_Inventario_Fisico_Punto NOT IN ('.$ids_inv.')		        	
			        	AND INF.Id_Punto_Dispensacion = ' . $idTipo 
			        	. ' AND INF.Fecha_Fin BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" 
		        	GROUP BY INF.Fecha_Fin)

	        UNION ALL 
	        	(SELECT D.Id_Dispensacion AS ID, 
	        	(SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, 
	        	(SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") 
        			FROM Paciente 
	        		WHERE Id_Paciente=D.Numero_Documento) AS Destino, 
	        	"dispensacion" AS Ruta, 
	        	"Salida" AS Tipo, 
	        	D.Codigo, 
	        	IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),
	        	IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),
	        	D.Fecha_Actual)) AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura,
	        "" AS Codigo_Fact FROM Producto_Dispensacion PD 
	        INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
	        INNER JOIN Inventario I ON PD.Id_Inventario=I.Id_Inventario
	        WHERE 
	        	D.Estado_Dispensacion != "Anulada" 
	        	AND  PD.Cantidad_Entregada!=0 
	        	AND PD.Id_Producto = ' . $producto." AND PD.Id_Inventario=".$prod["Id_Inventario"]."" . ' AND I.Id_Punto_Dispensacion = ' . $idTipo . ' 
	        HAVING  Fecha BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59")';


        // var_dump($query_dispensaciones);
        // exit;
    }
    
    
    $condicion .= " AND R.Fecha BETWEEN '$fecha1 00:00:00' AND '$fecha2 23:59:59'";
    $condicion2.= " AND AR.$attrFecha BETWEEN '$fecha1 00:00:00' AND '$fecha2 23:59:59'";
   
    $condicion .= " AND PR.Lote LIKE '%".$prod["Lote"]."%'";
     
    
    $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha1)),1,date("Y",strtotime($fecha1)))-1));

    $query_inicial = 'SELECT SUM(Cantidad) as Total
    FROM Saldo_Inicial_Kardex 
    WHERE Id_Producto = '.$producto.' AND Fecha="'.$ultimo_dia_mes.'" '.$condicion5.' GROUP BY Id_Producto';
    $oCon= new consulta();
    $oCon->setQuery($query_inicial);
    $ress = $oCon->getData();
    unset($oCon);

    $acum=$total=(INT)$ress["Total"];
    
    $query = '(SELECT R.Id_Remision as ID,
    R.Nombre_Origen, 
    (CASE   
          WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino)   
          WHEN R.Tipo="Interna" THEN R.Nombre_Destino   
    END) as Destino,
    "remision" as Ruta, "Salida" as Tipo, CONCAT(R.Codigo," - (", R.Estado,")") AS Codigo, R.Fecha as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
    FROM Producto_Remision PR
    INNER JOIN Remision R
    ON R.Id_Remision = PR.Id_Remision
    LEFT JOIN Factura_Venta F
    ON F.Id_Factura_Venta = R.Id_Factura
    WHERE R.Estado IN ("Pendiente","Alistada","Enviada","Facturada","Recibida") AND PR.Id_Producto = '.$producto.$condicion.' AND PR.Lote = "'.$prod["Lote"].'") 
    
    UNION ALL (SELECT R.Id_Remision as ID,
    R.Nombre_Origen, 
    (CASE   
          WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino)   
          WHEN R.Tipo="Interna" THEN R.Nombre_Destino   
    END) as Destino,
    "remisionantigua" as Ruta, "Salida" as Tipo, R.Codigo, R.Fecha as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
    FROM Producto_Remision_Antigua PR
    INNER JOIN Remision_Antigua R
    ON R.Id_Remision = PR.Id_Remision
    LEFT JOIN Factura_Venta F
    ON F.Id_Factura_Venta = R.Id_Factura
    WHERE PR.Id_Producto = '.$producto.$condicion.' AND PR.Lote = "'.$prod["Lote"].'") 
    
    UNION ALL (SELECT AI.Id_Ajuste_Individual as ID,
    IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.')) AS Nombre_Origen,IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"") as Destino,
    "ajusteinventariover" as Ruta, AI.Tipo, AI.Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_Ajuste_Individual PAI
    INNER JOIN Ajuste_Individual AI
    ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
    WHERE PAI.Id_Producto = '.$producto.$condicion3.' AND PAI.Lote = "'.$prod["Lote"].'") 
    
    UNION ALL (SELECT PAR.Id_Producto_'.$tabla.' as ID, "" as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "'.$ruta.'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$attrFecha.' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_'.$tabla.' PAR
    INNER JOIN '.$tabla.' AR
    ON PAR.Id_'.$tabla.' = AR.Id_'.$tabla.'
    WHERE PAR.Id_Producto = '.$producto.$condicion2. ' AND PAR.Lote = "'.$prod["Lote"].'" /* AND PAR.Fecha_Vencimiento="'.$prod["Fecha_Vencimiento"].'" */ ) 

    '.$sql_acta_recepcion_bodegas.'
    
    UNION ALL (SELECT INF.Id_Inventario_Fisico AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Destino, "inventariofisico/inventario_final_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Segundo_Conteo) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico PIF INNER JOIN Inventario_Fisico INF ON PIF.Id_Inventario_Fisico=INF.Id_Inventario_Fisico WHERE INF.Estado="Terminado" AND PIF.Id_Producto = '.$producto.$condicion4. ' AND PIF.Lote = "'.$prod["Lote"].'" GROUP BY PIF.Id_Inventario_Fisico, INF.Fecha_Fin) '.$query_dispensaciones.' ORDER BY Fecha ASC';
    
    //echo $query; exit;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultados = $oCon->getData();
    unset($oCon);
    
    $i=-1;
    $entrada=0;
    $salida=0;
  	
   	$acum_inv = 0;
    foreach($resultados as $res){ $i++;
        $acum_inv += intval($prod['Costo']);
        if($res["Tipo"]=='Entrada'){
            $acum+=$res["Cantidad"];
            $entrada+=$res["Cantidad"];
        }elseif ($res["Tipo"]=='Salida'){
            $acum-=$res["Cantidad"];
            $salida+=$res["Cantidad"];
        } elseif ($res["Tipo"]=='Inventario') {
            $acum=$res["Cantidad"];
            
        }
       
     echo $i." )". $res['Codigo']." (".$res["ID"].") &nbsp&nbsp ".$res['Tipo']." : ".$res['Cantidad']
     ." &nbsp&nbsp Fecha: --- ".$res['Fecha']
     ." &nbsp&nbsp Saldo: ".$acum
     ." &nbsp&nbsp&nbsp&nbsp Id_Inventario :".$prod['Id_Inventario']
     ." &nbsp&nbsp&nbsp&nbsp Id_Punto: ".$prod['Id_Punto_Dispensacion']
     ." &nbsp&nbsp Costo_prod: ".$prod['Costo']."<br>";
        $resultados[$i]["Saldo"]=$acum;
    }
    
    
     
        echo "<strong>".$k." - ". $j.") - ".$prod["Lote"]." - ".$idTipo." - ".$prod["Id_Producto"]." - ".$prod["Cantidad"]." : (".(INT)$resultados[$i]["Saldo"]." Entradas= ".$entrada." Salidas: --(".$salida." - Costo Inv: ".$acum_inv."</strong>\n <br> <br>";
        $resultados[$i]['Negativo']='No';
        if($resultados[$i]["Saldo"]!=$prod["Cantidad"]){
            echo "Diferente ---------------------------------------- "."<br>";
            $texto.=",".$prod['Id_Inventario'];
        }
  


    if((INT)$resultados[$i]["Saldo"]<0){
            $resultados[$i]["Saldo"] = 0;
            $resultados[$i]['Negativo']='Si';
            
        }
    $cantidad = number_format($resultados[$i]["Saldo"],0,"","");

    $queryInsert[] = "('$fecha2',$prod[Id_Producto],$cantidad,'$prod[Lote]','$prod[Fecha_Vencimiento]',$prod[Id_Bodega],$prod[Id_Punto_Dispensacion])";
    

    if (count($queryInsert) == 5000) {
        registrarSaldos($queryInsert);
        $queryInsert = [];
    }
  
    
   /* if($prod["Cantidad"]!=$cantidad){


         $oItem=new complex('Inventario','Id_inventario',$prod['Id_Inventario'] );
        $cantidad_Antigua=$oItem->Cantidad;
        echo " Se actualiza el siguiente id de inventario :".$prod['Id_Inventario']." Tenia un cantidad de :".$cantidad_Antigua." Y queda con ".$cantidad." diferencia de ajuste".((INT)$cantidad-(INT)$cantidad_Antigua).""."Costo=".$prod['Costo']." Total Ajuste= ".((INT)$cantidad-(INT)$cantidad_Antigua)*($prod['Costo'])." <br>";
        $oItem->Cantidad=$cantidad ;
        $oItem->Negativo= $resultados[$i]['Negativo'];
        //$oItem->save();
         unset($oItem);

       /* $query="SELECT PFI.* FROM Producto_Inventario_Fisico_Punto PFI INNER JOIN Inventario_Fisico_Punto IP WHERE IP.Id_Punto_Dispensacion=44 AND PFI.Id_Inventario=".$prod['Id_Inventario']." AND IP.Fecha_Fin LIKE '%2019-03-15%'";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
       if($inventario){
            //echo $inventario['Id_Producto_Inventario_Fisico_Punto']."<br>";
            if($inventario['Cantidad_Inventario']!=$cantidad){
                echo " Se Actualiza el inventario que tenia una cantidad de: ".$inventario['Cantidad_Inventario']." por ".$cantidad." Costo :".$prod['Costo']." id_Invwentario fisico punto :".$inventario['Id_Producto_Inventario_Fisico']." <br>";
                $oItem=new complex('Producto_Inventario_Fisico_Punto','Id_Producto_Inventario_Fisico',$inventario['Id_Producto_Inventario_Fisico'] );
                $oItem->Cantidad_Inventario=$cantidad;
               // $oItem->save();
                unset($oItem);
            }
        }
       
        
        
     
    }
   

    # COMENTADO PORQUE SE CAMBIÓ LA DINAMICA DE GUARDAR REGISTROS.
    /* $oItem = new complex("Saldo_Inicial_Kardex","Id_Saldo_Inicial_Kardex");
    $oItem->Fecha = $fecha2; // fecha fin
    $oItem->Id_Producto = $prod["Id_Producto"];
    $oItem->Cantidad = number_format($resultados[$i]["Saldo"],0,"","");
    $oItem->Lote = $prod["Lote"];
    $oItem->Fecha_Vencimiento = $prod["Fecha_Vencimiento"];
    $oItem->Id_Bodega = $prod['Id_Bodega'];
    $oItem->Id_Punto_Dispensacion = $prod['Id_Punto_Dispensacion'];
    //$oItem->save(); 
    unset($oItem); */

} 
    
echo "<br>".$texto;

if (count($queryInsert) > 0) {
    registrarSaldos($queryInsert);
    $queryInsert = [];
}

function registrarSaldos($queryInsert){
    /*$query = "INSERT INTO Saldo_Inicial_Kardex (Fecha,Id_Producto,Cantidad,Lote,Fecha_Vencimiento,Id_Bodega,Id_Punto_Dispensacion) VALUES " . implode(',',$queryInsert);

    $oCon = new consulta();
    $oCon->setQuery($query);
    //$oCon->createData();
    unset($oCon);

    return;*/
}
function getOrigenActa($tabla) {

    $string = '""';

    if ($tabla == 'Acta_Recepcion') {
        $string = "(SELECT Nombre FROM Proveedor WHERE Id_Proveedor = AR.Id_Proveedor)";
    } elseif ($tabla == 'Acta_Recepcion_Remision') {
        $string = "(SELECT Nombre_Origen FROM Remision WHERE Id_Remision = AR.Id_Remision)";
    }

    return $string;
    
}
?>