<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

//require_once('../../config/start.inc.php');
include_once('../../class/class.consulta_paginada.php');


$currentPage = (isset($_REQUEST['pag']) && $_REQUEST['pag'] != '' )? $_REQUEST['pag'] : false;
$params      = (isset($_REQUEST['filtros']) && $_REQUEST['filtros'] != '' )? $_REQUEST['filtros'] : false;
$params      = json_decode($params,true);
$tamPage     = 5;
$limit;
$inner       = '';
$alias       = '';
$where       = '';

$inner.=   ' INNER JOIN `Municipio`    AS MN ON MN.Id_Municipio     =  BHV.Id_Municipio ';
$inner.=   ' INNER JOIN `Departamento` AS DP ON  DP.Id_Departamento =  MN.Id_Departamento ';

$inner.=   ' INNER JOIN `Cargo`        AS CG ON CG.Id_Cargo         =  BHV.Id_Cargo ';
$inner.=   ' INNER JOIN `Dependencia`  AS DN ON DN.Id_Dependencia   =  CG.Id_Dependencia ';

$alias =   ' BHV.Nombre   AS NombreUsuario,
             BHV.Apellido AS ApellidoUsuario,
             DP.Nombre    AS Departamento,
             MN.Nombre    AS Municipio,
             DN.Nombre    AS Dependencia,
             CG.Nombre    AS Cargo,
             BHV.NumeroTelefono AS NumeroTelefono,
             BHV.Id_Banco_Hoja_Vida,
             BHV.Identificacion,
             BHV.Direccion,
             BHV.Tipo,
             BHV.Archivo,
             BHV.Fecha_Creacion,
             DP.Id_Departamento AS Id_Departamento,
             MN.Id_Municipio    AS Id_Municipio,
             DN.Id_Dependencia  AS Id_Dependencia,
             CG.Id_Cargo        AS Id_Cargo,
             BHV.Genero         AS Genero,
             BHV.Aspiracion_Salarial AS Aspiracion';

if ($params) {
  # code...
    if ($params['Nombre']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Nombre LIKE "%'.$params['Nombre'].'%" ';
    }
    if ($params['Apellido']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Apellido LIKE "%'.$params['Apellido'].'%" ';
    }
    if ($params['NumeroTelefono']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.NumeroTelefono LIKE "%'.$params['NumeroTelefono'].'%" ';
    }
    if ($params['Direccion']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Direccion LIKE "%'.$params['Direccion'].'%" ';
    }
    if ($params['Tipo']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Tipo = "'.$params['Tipo'].'" ';
    }
    if ($params['Identificacion']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Identificacion LIKE "%'.$params['Identificacion'].'%" ';
    }
    if ($params['Genero']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Genero LIKE "%'.$params['Genero'].'%" ';
    }
    if ($params['Aspiracion_Salarial']) {
        # code...
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= ' BHV.Aspiracion_Salarial LIKE "%'.$params['Aspiracion_Salarial'].'%" ';
    }
    if ($params['Departamento']) {
        //////////////////////////////////////////////////////////////////////
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= '           DP.Id_Departamento  =  "'.$params['Departamento'].'"';
        if ($params['Municipio']) {
            $where.= ' AND   BHV.Id_Municipio    =  "'.$params['Municipio'].'"';
        }////// FINAL IF Municipio

        //////////////////////////////////////////////////////////////////////
    }////FINAL IF DEPARTAMENTO
    if ($params['Dependencia']) {
        //////////////////////////////////////////////////////////////////////
        $where.= $where == '' ? ' WHERE' : ' AND ';
        $where.= '           DN.Id_Dependencia   =  "'.$params['Dependencia'].'"';
        if ($params['Cargo']) {
            $where.= ' AND   BHV.Id_Cargo        =  "'.$params['Cargo'].'"';
        }////// FINAL IF Municipio

        //////////////////////////////////////////////////////////////////////
    }////FINAL IF DEPARTAMENTO



}//// FINAL IF PARAMS
 
//////
if( !$currentPage){
    $limit = 0;
}else{
    $limit = ($currentPage-1)*$tamPage;
} //// FINAL IF PAGINATION
$where.= $where == '' ? ' WHERE' : ' AND ';
$where.= ' BHV.Estado = "Activo" ';

$query = " SELECT  SQL_CALC_FOUND_ROWS $alias FROM `Banco_Hoja_Vida` AS BHV $inner $where LIMIT  $limit , $tamPage " ;
//echo $query;
//var_dump($query);

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data = $oCon->getData();

//echo $contador = mysqli_num_rows($data);
unset($oCon);
// $arrayInfo = array( 'Info'  => $data,
//                     'Query' => $query  );

echo json_encode($data);

?>
