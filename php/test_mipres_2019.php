<?php

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);


	date_default_timezone_set('America/Bogota');

	require_once('../config/start.inc.php');
	include_once('../class/class.querybasedatos.php');
	include_once('../class/class.paginacion.php');
	include_once('../class/class.http_response.php');
    include_once('../class/class.lista.php');
    include_once('../class/class.complex.php');
    include_once('../class/class.consulta.php');

    

    $archivos = ObtenerArchivo();
    $zzz = 0;
    echo "<table border='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>Autorización</td><td>DIS</td><td>Fecha DIS</td><td>Id_Producto_Dispensacion</td><tr>";
    foreach($archivos as $arch){ $zzz++;
          $query='SELECT GROUP_CONCAT(Id_Producto_Dispensacion) AS Prod_Dis, D.Id_Dispensacion, D.Codigo AS Dis, D.Fecha_Actual AS Fecha
                FROM Producto_Dispensacion PD
                INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
                WHERE PD.Numero_Autorizacion='.$arch["Autorizacion"].'
                AND D.Estado_Dispensacion != "Anulada"
                ';
        
            $oCon = new consulta();
            $oCon->setQuery($query);
            $dis = $oCon->getData();
            unset($oCon);
            
            $oItem= new complex("Z_Mipres_Reporte_2019","Id_Reporte",$arch["Id_Reporte"]);
            $oItem->Actualizado=1;
            $oItem->save();
            unset($oItem);
            
            if($dis["Dis"]!=''){
                
                $oItem= new complex("Z_Relacion_Mipres_2019","Id_Relacion_Mipres_2019");
                $oItem->ID=$arch["ID"];
                $oItem->NoPrescripcion=$arch["NoPrescripcion"];
                $oItem->Id_Dispensacion=$dis["Id_Dispensacion"];
                $oItem->Id_Producto_Dispensacion=$dis["Prod_Dis"];
                $oItem->save();
                unset($oItem);
                
                echo "<tr><td>".$zzz."</td><td>".$arch["NoPrescripcion"]."</td><td>".$arch["ID"]."</td><td>".$arch["Autorizacion"]."</td><td>".$dis["Dis"]."</td><td>".$dis["Fecha"]."</td><td>".$dis["Prod_Dis"]."</td></tr>";
            }else{
                echo "<tr><td>".$zzz."</td><td>".$arch["NoPrescripcion"]."</td><td>".$arch["ID"]."</td><td>".$arch["Autorizacion"]."</td><td colspan='3'>NO SE ENCUENTRA DISPENSACION</td></tr>";
            }
        //sleep(1);
    }       
    
    
    
     function ObtenerArchivo(){
         
         $query='SELECT * FROM `Z_Mipres_Reporte_2019` WHERE Actualizado=0';
        
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $facturas = $oCon->getData();
        unset($oCon);
        
        return ($facturas);
         
     }