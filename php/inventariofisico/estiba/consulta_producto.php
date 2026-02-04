<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.paginacion.php');
	include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.utility.php');

    $id_estiba = ( isset( $_REQUEST['Id_Estiba'] ) ? $_REQUEST['Id_Estiba'] : '');
    $codigo = ( isset( $_REQUEST['Codigo'] ) ? $_REQUEST['Codigo'] : '' );

   
	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
	$util = new Utility();

    
	$condicion = SetCondiciones($codigo); 
    $query = 'SELECT  PRD.Id_Producto, IFNULL(CONCAT(PRD.Nombre_Comercial," (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," LAB-",PRD.Laboratorio_Comercial)) as Nombre,
    PRD.Laboratorio_Comercial,
    PRD.Laboratorio_Generico,
    PRD.Cantidad_Presentacion,
    PRD.Embalaje,
    IFNULL(PRD.Id_Categoria,0) as Id_Categoria,IFNULL((SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria),"Sin Categoria") as Categoria,
    PRD.Imagen,
    PRD.Codigo_Cum,
    PRD.Codigo_Barras
    FROM Producto PRD
    LEFT JOIN Inventario_Nuevo I
    ON PRD.Id_Producto=I.Id_Producto '.$condicion."  GROUP BY PRD.Id_Producto";
       
   
    $queryObj->SetQuery($query);
    $producto = $queryObj->ExecuteQuery('simple');
  

    if($producto){
       
            $lotes=ObternerLotes($producto['Id_Producto']);
            $producto['Lotes']=$lotes;
            if(count($lotes)>0){
                $msj="Se encontraron ".count($lotes)." Lotes de este Producto".$pos;
            }else{
                $msj="No se encontraron Lotes de este Producto, Agregue uno nuevo si consiguió";
            }
            $producto["Mensaje"]=$msj;
            $resultado["Tipo"]="success";
            $resultado["Datos"]=$producto;
        
    }else{

        $query = "SELECT count(*)as Total From Producto Where Estado = 'Activo'";
        $queryObj->SetQuery($query);
        $producto = $queryObj->ExecuteQuery('simple');


        $resultado["Tipo"]="error";
        $resultado["Titulo"]="Producto No Encontrado";
        $resultado["Texto"]="El Código de Barras Escaneado no coincide con ninguno de los $producto[Total] productos ACTIVOS que tenemos registrados.";
    }




	echo json_encode($resultado);

	function SetCondiciones($codigo){
        global $util;
        $codigo1=substr($codigo,0,12);

        $condicion=" WHERE (PRD.Codigo_Barras = '$codigo' OR I.Codigo='$codigo1' OR I.Alternativo LIKE '%$codigo1%') AND PRD.Estado = 'Activo' " ;
        

        return $condicion;
    }
    
    function ObternerLotes($id_producto){
        global $queryObj, $id_estiba;

        $query = 'SELECT I.Codigo, 
        I.Id_Inventario_Nuevo,
        I.Id_Producto,
        I.Lote,
        I.Fecha_Vencimiento, 
        I.Cantidad, 
        (I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Final, 
        "" as Cantidad_Encontrada
        FROM Inventario_Nuevo I
        WHERE I.Id_Producto = '.$id_producto.' AND I.Id_Estiba='.$id_estiba.' AND I.Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC';

        $queryObj->SetQuery($query);
        $lotes = $queryObj->ExecuteQuery('Multiple');

        return $lotes;
    }




?>