<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
    $util = new Utility();
    
    $tipo= ( isset( $_REQUEST['tiporemision'] ) ? $_REQUEST['tiporemision'] : '' );
    $cliente= ( isset( $_REQUEST['id_destino'] ) ? $_REQUEST['id_destino'] : '' );
    $mes=isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '';
    $tipo_bodega = '';

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
        global $nuevafecha,$condicion_principal,$tipo_bodega;
        $tipo_bodega=isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '';
        $bodega=isset( $_REQUEST['id_origen'] ) ? $_REQUEST['id_origen'] : '';
       
        $tipo_bodega=explode('-',$tipo_bodega);

      
        $condicion='';

       if($tipo_bodega[0]=='Bodega'){
           $criterio_categorias = "SELECT Id_Categoria FROM Bodega_Categoria WHERE Id_Bodega = $req[id_origen]";
           $condicion .= " AND PRD.Id_Categoria IN ($criterio_categorias) ";
            if(($tipo_bodega[1]=='Bodega' || ($bodega =='6' || $bodega =='8' || $bodega =='9')) ){
                $condicion_principal=" WHERE I.Id_Bodega=$req[id_origen]";
            }else{
                $condicion_principal=" WHERE I.Id_Bodega=$req[id_origen] AND I.Fecha_Vencimiento>='$nuevafecha'";
            }
        

       }else if($tipo_bodega[0]=='Punto'){
        $condicion_principal=" WHERE Id_Punto_Dispensacion=$req[id_origen] ";
       }


        if (isset($req['nombre']) && $req['nombre']) {
                $condicion .= " AND (PRD.Nombre_Comercial LIKE '%$req[nombre]%' OR  CONCAT(PRD.Principio_Activo,' ',PRD.Presentacion,' ',PRD.Concentracion,' ', PRD.Cantidad,' ', PRD.Unidad_Medida) LIKE '%$req[nombre]%' )";            
        }

        if (isset($req['cum']) && $req['cum']) {
                $condicion .= " AND PRD.Codigo_Cum LIKE '%".$req['cum']."%'";
        }
        
        if (isset($req['lab_com']) && $req['lab_com']) {
                $condicion .= " AND PRD.Laboratorio_Comercial LIKE '%".$req['lab_com']."%'";
        }
        
        if (isset($req['cod_barra']) && $req['cod_barra'] != '') {
                $condicion .= " AND PRD.Codigo_Barras LIKE '%".$req['cod_barra']."%'";
        }

        return $condicion;
	}


    function GetQuery($tipo){
        global $condicion,$condicion_principal,$cliente, $queryObj,$tipo_bodega;
        $having=" GROUP BY I.Id_Producto HAVING Cantidad_Disponible>0 ORDER BY Nombre_Comercial";
        $id_origen = $_REQUEST['id_origen'];
        $subquery_bodega =  "";
        if($tipo=='Interna'){
            $query='SELECT C.Nombre AS Categoria, C.Separable AS Categoria_Separable, PRD.Id_Categoria,  PRD.Id_Producto,ROUND(IFNULL(AVG(I.Costo),0)) as Precio, PRD.Embalaje,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion, 0 as Seleccionado, NULL as Cantidad, '.$subquery_bodega.' (
                CASE
                WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
                WHEN PRD.Gravado="No"  THEN 0
              END
            ) as Impuesto, (SELECT ROUND(AVG(Costo)) FROM Inventario_Nuevo WHERE Id_Bodega!=0 AND Id_Producto=I.Id_Producto) as Costo
            FROM Inventario_Nuevo I
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto 
            INNER JOIN Categoria C ON PRD.Id_Categoria = C.Id_Categoria '.$condicion_principal.$condicion.$having;
            

     

        }else{
            $query1="SELECT * FROM Cliente WHERE Id_Cliente=".$cliente;
            $queryObj->SetQuery($query1);
            $datoscliente=$queryObj->ExecuteQuery('simple');

            $query=' SELECT T.*, (
                CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN "Si"
            WHEN PRG.Codigo_Cum IS  NULL THEN "No"
           
          END
            ) as Regulado,
            (
                CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
            WHEN PRG.Codigo_Cum IS  NULL THEN 0
          END
            ) as Precio_Regulado FROM (SELECT C.Nombre as Categoria, C.Separable AS Categoria_Separable, PRD.Id_Categoria,  PRD.Id_Producto,
            
            IFNULL((IF (IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0) <  LG.Precio AND IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0)>0 , IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0),LG.Precio   )),0) as Precio, 0 as Seleccionado, NULL as Cantidad,
            
            PRD.Embalaje,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion,

            (
                CASE
                WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
                WHEN PRD.Gravado="No"  THEN 0
              END
            ) as Impuesto, I.Costo  as Costo,  SPLIT_STRING(PRD.Codigo_Cum,"-",1) as Cum 
            FROM Inventario_Nuevo I
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto
            INNER JOIN Categoria C ON PRD.Id_Categoria = C.Id_Categoria   
            INNER JOIN Producto_Lista_Ganancia LG
            ON PRD.Codigo_Cum = LG.Cum '.$condicion_principal.$condicion.' AND LG.Id_Lista_Ganancia ='.$datoscliente['Id_Lista_Ganancia'].$having.'  ) T left JOIN (SELECT Precio, Codigo_Cum,  SPLIT_STRING(Codigo_Cum,"-",1) as Cum FROM Precio_Regulado group  BY Cum ) PRG ON T.Cum=PRG.Cum ';

        

        }   

        return $query;
    }

    function GetLotes($productos){
        global  $queryObj,$condicion_principal,$tipo;

        $resultado=[];
        $having="  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
        $i=-1;
        $pos=0;
        foreach ($productos as  $value) {$i++;
            if($tipo=='Cliente'){
                $productos[$i]['Costo']=GetCosto($value['Id_Producto'],$value['Costo']);
            }

            $query1="SELECT I.Id_Inventario_Nuevo, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,I.Fecha_Vencimiento,$value[Precio] as Precio, 0 as Cantidad_Seleccionada FROM Inventario_Nuevo I 
           ".$condicion_principal." AND I.Id_Producto= $value[Id_Producto] ". $having ;

  
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

    function GetCosto($id,$costo){

        global  $queryObj;

        $query="SELECT
        IFNULL(ROUND(AVG(r.Precio)),$costo) AS Costo
        FROM
        (
        SELECT Precio FROM Producto_Acta_Recepcion PR INNER JOIN Acta_Recepcion AR ON PR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion WHERE PR.Id_Producto=$id  AND AR.Id_Bodega!=0 ORDER BY PR.Id_Producto_Acta_Recepcion DESC LIMIT 3 
        ) r 
        ";

      

        $queryObj->SetQuery($query);
        $costo=$queryObj->ExecuteQuery('simple');


        return $costo['Costo'];

    }
    