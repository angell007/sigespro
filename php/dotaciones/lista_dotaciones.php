<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
//include_once('../../class/class.consulta_paginada.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$cantMes = ( isset( $_REQUEST['cantMes'] ) ? $_REQUEST['cantMes'] : '' );

if($cantMes != ''){
$query = 'SELECT count(*) as CantidadMes, SUM(Costo) as SumaMes FROM Dotacion D where month(Fecha_Entrega)= '.$cantMes.' AND Estado = "Activa"';
            $oCon= new consulta();
            $oCon->setQuery($query);
            $resultado["CantidadMes"] = $oCon->getData();
            unset($oCon);
}

// lista los productos por grupo
$query2 = 'SELECT * FROM Grupo_Inventario GI
            INNER JOIN Inventario_Dotacion ID ON GI.Id_Grupo_Inventario = ID.Id_Grupo_Inventario
            WHERE GI.Id_Grupo_Inventario = "'.$id.'"';
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$resultado["Listado_Grupo_Inventario"] = $oCon->getData();
unset($oCon);


/* lista las cantidades de categorias por grupo */ 
if($id != ''){
    $query = 'SELECT CPD.Nombre Nombre, SUM(Cantidad) Cantidad
            FROM Inventario_Dotacion ID
            INNER JOIN Categoria_Producto_Dotacion CPD ON ID.id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion
            WHERE  ID.Id_Grupo_Inventario = "'.$id.'"
            GROUP BY ID.id_Categoria_Producto_Dotacion, ID.Id_Grupo_Inventario';

}else{
    $query = 'SELECT CPD.Nombre Nombre, SUM(Cantidad) Cantidad
            FROM Inventario_Dotacion ID
            INNER JOIN Categoria_Producto_Dotacion CPD ON ID.id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion
            WHERE  ID.id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion
            GROUP BY ID.id_Categoria_Producto_Dotacion';
}       
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Listado_Grupo_Inventario1"] = $oCon->getData();
unset($oCon);


$where = getCondiciones();
$query = 'SELECT COUNT(*) AS Total
FROM Dotacion D
INNER JOIN Funcionario E ON      E.Identificacion_Funcionario  = D.Identificacion_Funcionario
INNER JOIN Funcionario R ON      R.Identificacion_Funcionario  = D.Funcionario_Recibe
INNER JOIN Producto_Dotacion   PD ON            D.Id_Dotacion  = PD.Id_Dotacion 
INNER JOIN Inventario_Dotacion ID ON PD.Id_Inventario_Dotacion = ID.Id_Inventario_Dotacion  
' .$where;


$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

######### GRUPO-DOTACION  #######

$query = 'SELECT * FROM Grupo_Inventario'; 

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Grupo"] = $oCon->getData();
unset($oCon);
 
######### GRUPO-DOTACION  #######

####### PAGINACIÓN ######## 
$tamPag = 15; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}

$query2 = ' SELECT * , SUM(ID.Cantidad) AS CantidadTotal,  
                       CPD.Nombre AS NombreProducto 
            FROM Categoria_Producto_Dotacion CPD 
            INNER JOIN Inventario_Dotacion ID 
                    ON ID.Id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion 
            GROUP   BY CPD.Nombre
            '; 

$oCon = new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');

$resultado["Cantidades"] = $oCon->getData();
unset($oCon);
    $query = '  SELECT 
                    D.*,
                GROUP_CONCAT(PD.Cantidad , " X  " , ID.Nombre ) AS NombresProducto, GI.Nombre NombreGrupo,
                GROUP_CONCAT(PD.Id_Inventario_Dotacion) AS IID, 
                GROUP_CONCAT(PD.Cantidad) AS Cantidad,
                    SUM(PD.Cantidad * PD.Costo) AS Total,
                    CONCAT(R.Nombres," ",R.Apellidos) as Recibe, 
                    CONCAT(E.Nombres," ",E.Apellidos) as Entrega
                FROM Dotacion D
                INNER JOIN Producto_Dotacion   PD  ON  D.Id_Dotacion			    = PD.Id_Dotacion 
                INNER JOIN Inventario_Dotacion ID  ON  ID.Id_Inventario_Dotacion    = PD.Id_Inventario_Dotacion
                INNER JOIN Grupo_Inventario GI ON  ID.Id_Grupo_Inventario           = GI.Id_Grupo_Inventario
                INNER JOIN Funcionario E ON E.Identificacion_Funcionario            = D.Identificacion_Funcionario
                INNER JOIN Funcionario R ON R.Identificacion_Funcionario            = D.Funcionario_Recibe       
                WHERE ID.Nombre != "" AND D.Id_Dotacion>0 '.$condicion.'  
                GROUP BY D.Id_Dotacion     
                ORDER BY D.Fecha DESC LIMIT '.$limit.','.$tamPag  ;   
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Listado"] = $oCon->getData();
unset($oCon);
 

$query2 = 'SELECT IFNULL(SUM(D.Costo),0) as Valor, count(*) as Cantidad
            FROM Dotacion D
            INNER JOIN Funcionario E ON E.Identificacion_Funcionario = D.Identificacion_Funcionario
            INNER JOIN Funcionario R ON R.Identificacion_Funcionario = D.Funcionario_Recibe
            WHERE D.Estado != "Anulada"  
            '.$condicion  ;   
//AND D.Estado != "Devuelta"
$oCon= new consulta();
$oCon->setQuery($query2);
$resultado["Totales"] = $oCon->getData();
unset($oCon);


// $query3 = 'SELECT SUM(Costo) AS TotalesCostos FROM Producto_Dotacion';
$query3 = 'SELECT count(*) as CantidadAno, SUM(Costo) as SumaAno FROM Dotacion D where Estado = "Activa" ';
$oCon= new consulta();
$oCon->setQuery($query3);
$resultado["Costos"] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;

echo json_encode($resultado);

function getCondiciones() {
    if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
        $condicion .= " AND D.Id_Dotacion LIKE '%$_REQUEST[cod]%'";
    }

    if (isset($_REQUEST['entrega']) && $_REQUEST['entrega'] != "") {
        $condicion .= "AND CONCAT(E.Nombres,' ',E.Apellidos) LIKE '%$_REQUEST[entrega]%'";
    } 


    if (isset($_REQUEST['recibe']) && $_REQUEST['recibe'] != "") {
        $condicion .= "AND CONCAT(R.Nombres,' ',R.Apellidos) LIKE '%$_REQUEST[recibe]%'";
    } 


    if (isset($_REQUEST['detalles']) && $_REQUEST['detalles'] != "") {
        $condicion .= "AND D.Detalles_Entrega LIKE '%$_REQUEST[detalles]%'";
    } 

    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= "AND DATE_FORMAT(D.Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
    
    return $condicion;
}

?>