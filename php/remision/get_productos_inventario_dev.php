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
    
    $tipo= ( isset( $_REQUEST['tiporemision'] ) ? $_REQUEST['tiporemision'] : '' );
    $cliente= ( isset( $_REQUEST['id_destino'] ) ? $_REQUEST['id_destino'] : '' );
    $mes=isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '';

    if($mes>'0'){
        $hoy=date("Y-m-t", strtotime(date('Y-m-d')));
        $nuevafecha = strtotime ( '+'.$mes.' months' , strtotime ( $hoy) ) ;
        $nuevafecha= date('Y-m-d', $nuevafecha);
        
    }else{
        $nuevafecha=date('Y-m-d');
    }
    $condicion_principal='';
    $condicion = SetCondiciones($_REQUEST);    
   
    $query= GetQuery($tipo);
    
  
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

    $productos=GetLotes($productos);

	echo json_encode($productos);

	function SetCondiciones($req){
        global $nuevafecha,$condicion_principal;
        $tipo_bodega=isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '';

        $tipo_bodega=explode('-',$tipo_bodega);

       if($tipo_bodega[0]=='Bodega'){
        $condicion_principal=" WHERE Id_Bodega=$req[id_origen] AND I.Fecha_Vencimiento>='$nuevafecha' ";
       }else if($tipo_bodega[0]=='Punto'){
        $condicion_principal=" WHERE Id_Punto_Dispensacion=$req[id_origen] ";
       }

       $condicion='';

        if (isset($req['nombre']) && $req['nombre']) {
                $condicion .= " AND PRD.Nombre_Comercial LIKE '%$req[nombre]%' OR  CONCAT(PRD.Principio_Activo,' ',PRD.Presentacion,' ',PRD.Concentracion,' ', PRD.Cantidad,' ', PRD.Unidad_Medida) LIKE '%$req[nombre]%'";            
        }

        if (isset($req['cum']) && $req['cum']) {
                $condicion .= " AND PRD.Codigo_Cum LIKE '%".$req['cum']."%'";
        }

        return $condicion;
	}


    function GetQuery($tipo){
        global $condicion,$condicion_principal,$cliente, $queryObj;
        $having=" GROUP BY PRD.Id_Producto HAVING Cantidad_Disponible>0 ORDER BY Nombre_Comercial";
        if($tipo=='Interna'){
            $query='SELECT (SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria) as Categoria,  PRD.Id_Producto,IFNULL(AVG(I.Costo),0) as Precio, PRD.Embalaje,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion
            FROM Inventario I
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto '.$condicion_principal.$condicion.$having;
        }else{
            $query1="SELECT * FROM Cliente WHERE Id_Cliente=".$cliente;
            $queryObj->SetQuery($query1);
            $datoscliente=$queryObj->ExecuteQuery('Simple');

            $query='SELECT (SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria) as Categoria,  PRD.Id_Producto,
            
            IFNULL((IF (IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0) <  LG.Precio AND IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0)>0 , IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0),LG.Precio   )),0) as Precio, 
            
            PRD.Embalaje,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion
            FROM Inventario I
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto   
            INNER JOIN Producto_Lista_Ganancia LG
            ON PRD.Codigo_Cum = LG.Cum AND LG.Id_Lista_Ganancia ='.$datoscliente['Id_Lista_Ganancia'].$condicion.$having ;
        }

        return $query;
    }

    function GetLotes($productos){
        global  $queryObj,$condicion_principal;
        $having="  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
        $i=-1;
        foreach ($productos as  $value) {$i++;
            $query1="SELECT I.Id_Inventario, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,I.Fecha_Vencimiento,$value[Precio] as Precio, 0 as Cantidad_Seleccionada FROM Inventario I 
           ".$condicion_principal." AND I.Id_Producto= $value[Id_Producto] ". $having ;

  
            $queryObj->SetQuery($query1);
            $lotes=$queryObj->ExecuteQuery('Multiple');

    var_dump(json_encode($lotes));
    var_dump(json_encode($productos));
    exit;

            if(count($lotes)>0){
                $productos[$i]['Lotes']=$lotes;
            }else{
                unset($productos[$i]);
            }
           
        }

    // var_dump(json_encode($productos));
    // exit;
        return $productos;
    }
    


?>