<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idCategoria = ( isset( $_REQUEST['idCategoria'] ) ? $_REQUEST['idCategoria'] : '' );
$idbodega = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT I.Id_Inventario_Fisico, I.Fecha_Inicio, I.Fecha_Fin, I.Letras, I.Estado,
          CONCAT(FD.Nombres," ",FD.Apellidos) as Funcionario_Digita, CONCAT(FC.Nombres," ",FC.Apellidos) as Funcionario_Cuenta, 
          B.Nombre as Bodega, C.Nombre as Categoria, I.Conteo_Productos 
          FROM Inventario_Fisico I
          INNER JOIN Funcionario FD
          ON I.Funcionario_Digita = FD.Identificacion_Funcionario
          INNER JOIN Funcionario FC
          ON I.Funcionario_Cuenta = FC.Identificacion_Funcionario
          INNER JOIN Bodega B
          On I.Bodega = B.Id_Bodega
          LEFT JOIN Categoria C
          ON I.Categoria = C.Id_Categoria
          ORDER BY I.Id_Inventario_Fisico DESC
          ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);


?>