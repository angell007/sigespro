<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );


$query = 'UPDATE '.$mod.' 
          SET Estado = "Estado Anulada" , Compra_Fecha_Anulada= NOW() 
          WHERE Id_'.$mod.' = '.$id  ;

$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());


$query1 = ' SELECT 
            OCI.Id_Funcionario as Funcionario , OCI.Codigo as Codigo, OCI.Id_Bodega as Bodega, OCI.Fecha_Llegada as FechaLlegada,
            OCI.Dia_Entrega as DiaEntrega
            FROM Orden_Compra_Internacional OCI  '  ;
 
          
$result1 = mysql_query($query1) or die('Consulta fallida: ' . mysql_error());

$resultado = [];

while($lista=mysql_fetch_assoc($result1)){
    $resultado[]=$lista;
}
@mysql_free_result($resultado);

mysql_close($link);
echo json_encode($resultado);
?>