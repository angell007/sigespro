<?php 
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    
    require_once('../../../config/start.inc.php');
    include_once('../../../class/class.lista.php');
    include_once('../../../class/class.consulta.php');
    $id_inventario_fisico_nuevo=isset($_REQUEST['Id_Inventario_Fisico_Nuevo']) ? $_REQUEST['Id_Inventario_Fisico_Nuevo'] : false;

    $query='SELECT I.Fecha AS "Fecha_Realizado", E.Nombre AS "Nombre_Estiba", G.Nombre AS "Nombre_Grupo", PD.*, (PD.Segundo_Conteo - PD.Cantidad_Inventario) AS "Cantidad_Diferencial",
             CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto ,
             P.Nombre_Comercial, 
             P.Codigo_Cum as Cum
            From Inventario_Fisico_Nuevo I
            LEFT JOIN Doc_Inventario_Fisico DOC ON DOC.Id_Inventario_Fisico_Nuevo=I.Id_Inventario_Fisico_Nuevo
            LEFT JOIN Estiba E ON E.Id_Estiba=DOC.Id_Estiba
            LEFT JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba=E.Id_Grupo_Estiba
            LEFT JOIN Producto_Doc_Inventario_Fisico PD ON PD.Id_Doc_Inventario_Fisico= DOC.Id_Doc_Inventario_Fisico
            LEFT JOIN Producto P ON  P.Id_Producto = PD.Id_Producto
            WHERE I.Id_Inventario_Fisico_Nuevo = '.$id_inventario_fisico_nuevo.'
             ORDER BY E.Nombre , P.Nombre_Comercial '
            ;


    $oCon=new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $Inventario=$oCon->getData();
    unset($oCon);

    if($Inventario){
      
        $producto["Mensaje"]='Bodegas Encontradas con éxito';
        $resultado["Tipo"]="success";
        $resultado["Inventario"]=$Inventario;
    
    }else{
        $resultado["Tipo"]="error";
        $resultado["Titulo"]="Error al intentar buscar las bodegas";
        $resultado["Texto"]="Ha ocurrido un error inesperado.";
    }

    echo json_encode($resultado);
?>