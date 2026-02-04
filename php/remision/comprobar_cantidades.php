<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
    $util = new Utility();
    
    $id_origen= ( isset( $_REQUEST['id_origen'] ) ? $_REQUEST['id_origen'] : '' );
    $id_producto= ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
    $tipo= ( isset( $_REQUEST['tipo_origen'] ) ? $_REQUEST['tipo_origen'] : '' );
    $mes=isset( $_REQUEST['meses'] ) ? $_REQUEST['meses'] : '';

    if($mes>'0'){
        $hoy=date("Y-m-t", strtotime(date('Y-m-d')));
        $nuevafecha = strtotime ( '+'.$mes.' months' , strtotime ( $hoy) ) ;
        $nuevafecha= date('Y-m-d', $nuevafecha);
        
    }else{
        $nuevafecha=date('Y-m-d');
    }

    $condicion_principal = SetCondiciones();    
   
    $query= GetQuery();
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');



    $productos=GetLotes($productos);

	echo json_encode($productos);

	function SetCondiciones(){
        global $nuevafecha,$tipo,$id_origen,$id_producto;

      $condicion_principal='';

       if($tipo=='Bodega'){
            if($tipo=='Bodega' && ($id_origen =='6' || $id_origen =='8' || $id_origen =='9')  ){
                $condicion_principal.=" WHERE Id_Bodega=$id_origen ";
            }else{
                $condicion_principal.=" WHERE Id_Bodega=$id_origen AND I.Fecha_Vencimiento>='$nuevafecha' ";
            }
        

       }else if($tipo=='Punto_Dispensacion'){
        $condicion_principal .=" WHERE I.Id_Punto_Dispensacion=$id_origen ";
       }

       if($condicion_principal==''){
        $condicion_principal.=" WHERE I.Id_Producto=$id_producto";

       }else{
        $condicion_principal.=" AND I.Id_Producto=$id_producto";

       }

     
      
        return $condicion_principal;
	}


    function GetQuery(){
        global $condicion_principal;
      
            $query='SELECT (SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria) as Categoria,PRD.Id_Categoria,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible
            FROM Inventario I
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto '.$condicion_principal;

            

        return $query;
    }

    function GetLotes($productos){
        global  $queryObj,$condicion_principal,$tipo;

        $resultado=[];
        $having="  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
        $i=-1;
        $pos=0;
        foreach ($productos as  $value) {$i++;
         

            $query1="SELECT I.Id_Inventario, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,I.Fecha_Vencimiento,0 as Cantidad_Seleccionada FROM Inventario I 
           ".$condicion_principal. $having ;

       


  
            $queryObj->SetQuery($query1);
            $lotes=$queryObj->ExecuteQuery('Multiple');

            if(count($lotes)>0){ 
                $resultado[$pos]=$productos[$i];
                $resultado[$pos]['Lotes']=$lotes;
                $pos++;
            }else{
                unset($productos[$i]);
            }
           
        }

        return $resultado;
    }

    


?>