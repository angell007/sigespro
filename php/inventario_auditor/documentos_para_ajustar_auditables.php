<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../helper/response.php');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.consulta.php');


$documento = isset($_REQUEST['Id_Bodega']) ? $_REQUEST['Id_Bodega'] : false;

  $query = "SELECT PDA.Lote,  
    PDA.Fecha_Vencimiento, PDA.Cantidad_Inventario, PDA.Primer_Conteo As Cantidad_Encontrada, PDA.Id_Producto,
    PDA.Id_Producto_Doc_Inventario_Auditable,
    P.Nombre_Comercial, E.Nombre As Estiba, E.Id_Estiba, E.Nombre As Nombre_Estiba, 
    GE.Nombre  As Nombre_Grupo, 

    CONCAT(
            IFNULL(P.Principio_Activo, '  '), ' ',
            P.Presentacion,' ',
            IFNULL(P.Concentracion, '  '), ' ',
            P.Cantidad,' ', 
            P.Unidad_Medida,  ' LAB: ', 
            P.Laboratorio_Comercial
            ) AS Nombre_Producto,

    PDA.Primer_Conteo,
    PDA.Segundo_Conteo,
    PDA.Fecha_Primer_Conteo,
    PDA.Fecha_Segundo_Conteo,

  (CASE WHEN (PDA.Segundo_Conteo) < (PDA.Cantidad_Inventario) 
  THEN CONCAT('', PDA.Segundo_Conteo - PDA.Cantidad_Inventario)
  WHEN (PDA.Segundo_Conteo) >= (PDA.Cantidad_Inventario) 
  THEN CONCAT('+', PDA.Segundo_Conteo - PDA.Cantidad_Inventario) 
  END )

  AS Cantidad_Diferencial

  FROM Producto_Doc_Inventario_Auditable  As  PDA
  INNER JOIN Producto As P ON  P.Id_Producto = PDA.Id_Producto
  INNER JOIN Estiba As E ON  E.Id_Estiba = PDA.Id_Estiba
  INNER JOIN Grupo_Estiba As GE ON  E.Id_Grupo_Estiba = GE.Id_Grupo_Estiba
  INNER JOIN Doc_Inventario_Auditable As DA ON  DA.Id_Doc_Inventario_Auditable =   PDA.Id_Doc_Inventario_Auditable
  WHERE DA.Id_Doc_Inventario_Auditable = $documento  AND DA.Estado='Segundo Conteo' ORDER BY Estiba ASC, P.Nombre_Comercial ASC";

 $oCon = new consulta();
  $oCon->setTipo('Multiple');
  $oCon->setQuery($query);
  $productos = $oCon->getData();
  unset($oCon);

  $resultado['titulo'] = "Operación Exitosa";
  $resultado['mensaje'] = "Los Documentos se encuentran listos para ser ajustados";
  $resultado['data']['productos'] = $productos;
  $resultado['tipo'] = "success";
  $resultado['documento'] =$documento;

 show($resultado);
