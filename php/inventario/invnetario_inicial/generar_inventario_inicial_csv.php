<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$id_func = ( isset( $_REQUEST['id_func'] ) ? $_REQUEST['id_func'] : '' );
$datos = (array) json_decode($datos,true);


function limpiar($String)
{
$String = str_replace("  "," ",$String);
$String = str_replace("á","a",$String);
$String = str_replace("Á","A",$String);
$String = str_replace("Í","I",$String);
$String = str_replace("í","i",$String);
$String = str_replace("é","e",$String);
$String = str_replace("É","E",$String);
$String = str_replace("ó","o",$String);
$String = str_replace("Ó","O",$String);
$String = str_replace("ú","u",$String);
$String = str_replace("Ú","U",$String);
$String = str_replace("ç","c",$String);
$String = str_replace("Ç","C",$String);
$String = str_replace("ñ","n",$String);
$String = str_replace("Ñ","N",$String);
$String = str_replace("Ý","Y",$String);
$String = str_replace("ý","y",$String);
$String = str_replace("'","",$String);
$String = str_replace('"',"",$String);
str_replace('?',"",$String);
$String = trim($String);
return $String;
}

$respuesta=[];
$query_insert='';
$errores='';

if (!empty($_FILES['Archivo']['name'])){

    $handle = fopen($_FILES['Archivo']['tmp_name'], "r");
    
    if($handle){
    $query = "SELECT Id_Producto, Codigo_Cum, Mantis FROM Producto";
    
    $oCon= new consulta();
    $oCon->setTipo("Multiple");
    $oCon->setQuery($query);
    $productos = $oCon->getData();
    unset($oCon);
    $mantis = array_column($productos, 'Mantis');
$i=0;
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) { $i++;
        
	$pos = array_search(str_replace("?","Ñ",limpiar(utf8_decode($data[0]))), $mantis );

            if($pos==''){
               $errores[]=str_replace("?","Ñ",limpiar(utf8_decode($data[0])));  
            }else{
            //var_dump($productos[$pos]['Id_Producto']);
                if($datos["Tipo"]=="Bodega"){
                    $id_bodega=$datos["Id_Bodega"];
                    $id_punto=0;
                }elseif($datos["Tipo"]=="Punto"){
                    $id_bodega=0;
                    $id_punto=$datos["Id_Punto_Dispensacion"];
                }
                
                $query_insert.='('.$productos[$pos]['Id_Producto'].',"'.$productos[$pos]['Codigo_Cum'].'","'.$data[1].'","'.$data[2].'","'.date("Y-m-d H:i:s").'",'.$id_func.','.$id_bodega.','.$id_punto.','.$data[3].',1,'.number_format($data[4],2,".","").',0),';
                
               // echo implode(";",$data)."\n";
              
                          
            }
            
        }
        
        $query_cabecera='INSERT INTO `Inventario_Nuevo` (`Id_Producto`, `Codigo_CUM`, `Lote`, `Fecha_Vencimiento`, `Fecha_Carga`, `Identificacion_Funcionario`, `Id_Bodega`, `Id_Punto_Dispensacion`, `Cantidad`, `Lista_Ganancia`, `Costo`, `Cantidad_Apartada`)';
        
//echo $query_insert.'<br>';
        if($errores==""){
             //consulta personalizada -> elimine inventario que tengan caso bodega: Id_Bodega , Punto_Dispensacion 

            switch($datos['Tipo']){
                case "Bodega":{
                    $query = "DELETE FROM Inventario_Nuevo WHERE Id_Bodega = ".$datos['Id_Bodega'];
                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $productos = $oCon->deleteData();
                    unset($oCon);
                    break;
                }
                case "Punto":{
                    $query = "DELETE FROM Inventario_Nuevo WHERE Id_Punto_Dispensacion = ".$datos['Id_Punto_Dispensacion'];
                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $productos = $oCon->deleteData();
                    unset($oCon);
                    break;
                }
            }
            
            $oCon= new consulta();
            $oCon->setQuery($query_cabecera." VALUES ".trim($query_insert,","));
            $consultas = $oCon->createData();
            unset($oCon);
            
            $respuesta["Tipo"]="success";
            $respuesta["Mensaje"]="Inventario Actualizado Correctamente";
            $respuesta["Titulo"]="Carga Exitosa";
        }else{
            $respuesta["Tipo"]="error";
            $respuesta["Mensaje"]="<div style='text-align:justify;font-size:14px;'><b style='font-weight:bold;'>LOS SIGUIENTES CODIGOS MANTIS NO SE ENCUENTRAN REGUISTRADOS COMO PRODUCTOS EN EL SISTEMA:</b><br><br><strong>".implode(", ",array_unique($errores))."</strong><br><br>LA CARGA NO FUE EXITOSA</div>";
            $respuesta["Titulo"]="Error Con Productos";
        }
        
        
    }else{
        $respuesta["Tipo"]="error";
        $respuesta["Mensaje"]="El Archivo CSV no se deja abrir";
        $respuesta["Titulo"]="Error con Archivo";
    }
    
   
}else{
    $respuesta["Tipo"]="error";
    $respuesta["Mensaje"]="El Archivo CSV no existe o se encuentra vacio";
    $respuesta["Titulo"]="Error con Archivo";
}



echo json_encode($respuesta);
?>