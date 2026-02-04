<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.consulta.php');


$id_Estiba = isset($_REQUEST['Id_Estiba']) ? $_REQUEST['Id_Estiba'] : false;



$query = "SELECT  Id_Grupo_Estiba  , Id_Bodega_Nuevo
FROM Estiba
WHERE Id_Estiba = $id_Estiba ";


$oCon = new consulta();

$oCon->setQuery($query);
$idGrupo = $oCon->getData();
unset($oCon);
$idBodega = $idGrupo['Id_Bodega_Nuevo'];
$idGrupo = $idGrupo['Id_Grupo_Estiba'];

//todas las estibas que pertenecen a un grupo
$query =
'SELECT  E.Id_Estiba, E.Nombre AS "nombreEstiba" , G.Id_Grupo_Estiba  , G.Nombre AS "nombreGrupo" 
FROM Estiba E
INNER JOIN  Grupo_Estiba G ON G.Id_Grupo_Estiba=E.Id_Grupo_Estiba
WHERE E.Estado != "Inactiva" and  E.Id_Grupo_Estiba = ' . $idGrupo . ' AND E.Id_Bodega_Nuevo = ' . $idBodega . '  ';


$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$estibasPorGrupo = $oCon->getData();
unset($oCon);



//todos los documentos que pertenecenn a un grupo de la estiba que se encuentren en segundo conteo
$query = 'SELECT I.* , G.Nombre AS "nombreGrupo" , E.Nombre AS "nombreEstiba"
         FROM Doc_Inventario_Fisico I 
         INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
         INNER JOIN  Grupo_Estiba  G ON G.Id_Grupo_Estiba=E.Id_Grupo_Estiba
         WHERE G.Id_Grupo_Estiba= ' . $idGrupo . ' AND I.Estado="Segundo Conteo"';

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$documentos = $oCon->getData();
unset($oCon);


// validar si faltan estibas
$estiasFalt;
foreach ($estibasPorGrupo as $key => $estiba) {
  if(array_search($estiba['Id_Estiba'], array_column($documentos, 'Id_Estiba')) === false) {
    $estibasFalt .= $estiba['nombreEstiba'] . ' , ';
    
  }
}


if (strlen($estibasFalt) > 0) {
  $resultado['titulo'] = "Error";
  $resultado['mensaje'] = "Las siguientes estibas pertenecen al mismo grupo y no se le han hecho el Segundo Conteo:  $estibasFalt ";
  $resultado['tipo'] = "error";
} else {

  $query = 'SELECT I.Id_Doc_Inventario_Fisico , G.Id_Grupo_Estiba,G.Nombre AS "Nombre_Grupo" , E.Id_Estiba, E.Nombre AS "Nombre_Estiba", B.Id_Bodega_Nuevo, B.Nombre AS "Nombre_Bodega", PD.* , (PD.Segundo_Conteo - PD.Cantidad_Inventario) AS "Cantidad_Diferencial",  
  CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto ,
  P.Nombre_Comercial, P.Codigo_Cum

    FROM Doc_Inventario_Fisico I 
    INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
    INNER JOIN Grupo_Estiba  G ON G.Id_Grupo_Estiba=E.Id_Grupo_Estiba
    INNER JOIN Bodega_Nuevo B ON B.Id_Bodega_Nuevo=E.Id_Bodega_Nuevo 
    INNER JOIN Producto_Doc_Inventario_Fisico PD ON PD.Id_Doc_Inventario_Fisico=I.Id_Doc_Inventario_Fisico
    INNER JOIN Producto P ON  P.Id_Producto = PD.Id_Producto
    WHERE G.Id_Grupo_Estiba= ' .$idGrupo. ' AND I.Estado="Segundo Conteo" 
    ORDER BY E.Nombre , P.Nombre_Comercial
    ';

 $oCon = new consulta();
  $oCon->setTipo('Multiple');
  $oCon->setQuery($query);
  $productos = $oCon->getData();
  unset($oCon);

  $resultado['titulo'] = "Operación Exitosa";
  $resultado['mensaje'] = "Los Documentos se encuentran listos para ser ajustados";
  $resultado['data']['productos'] = $productos;
  $resultado['tipo'] = "success";
}


echo json_encode($resultado);
