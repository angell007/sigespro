<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php'); 

$Grupo = ( isset( $_REQUEST['nombreGrupo'] ) ? $_REQUEST['nombreGrupo'] : '' );
echo $Grupo;
if($Grupo != ''){
    $oItem = new complex("Grupo_Inventario","Id_Grupo_Inventario");   
    $oItem->Nombre = $Grupo;
    $oItem->save();
    unset($oItem);

    $resultado['title']   = "Grupo Guardado";
    $resultado['mensaje'] = "El Grupo se Guardò de fomar correcta";
    $resultado['tipo']    = "success";
}

$condicion = getCondiciones();

$query = 'SELECT COUNT(*) AS Total
FROM Inventario_Dotacion ID
WHERE Id_Inventario_Dotacion>0  
' . $condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

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

$query = 'SELECT 
            ID.*
            FROM Inventario_Dotacion ID
            WHERE Id_Inventario_Dotacion>0 
            '.$condicion.'          
            ORDER BY ID.Codigo DESC LIMIT '.$limit.','.$tamPag  ;   
      
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Listado"] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;

echo json_encode($resultado);

function getCondiciones() {
    if (isset($_REQUEST['codigo']) && $_REQUEST['codigo'] != "") {
        $condicion .= " AND ID.Codigo LIKE '%$_REQUEST[codigo]%'";
    }

    if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != "") {
        $condicion .= "AND ID.Nombre LIKE '%$_REQUEST[nombre]%'";
    } 


    if (isset($_REQUEST['calidad']) && $_REQUEST['calidad'] != "") {
        $condicion .= "AND ID.Calidad LIKE '%$_REQUEST[calidad]%'";
    } 


    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= "AND ID.Tipo LIKE '%$_REQUEST[tipo]%'";
    } 
    
    return $condicion;
}

?>