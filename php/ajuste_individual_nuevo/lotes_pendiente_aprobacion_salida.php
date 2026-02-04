<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');


$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT GROUP_CONCAT(FB.Id_Bodega_Nuevo) AS Bodegas FROM Funcionario_Bodega_Nuevo FB
            WHERE FB.Identificacion_Funcionario = '.$funcionario .'
            GROUP BY FB.Identificacion_Funcionario';

$oCon = new consulta();
$oCon->setQuery($query);
$bodega = $oCon->getData();
$bodegas = $bodega['Bodegas'];

$query = "SELECT A.* , A.Id_Ajuste_Individual ,
            (SELECT COUNT(*) FROM Producto_Ajuste_Individual PA
                WHERE PA.Id_Ajuste_Individual = A.Id_Ajuste_Individual
                GROUP BY PA.Id_Ajuste_Individual ) AS Items,

            CONCAT(F.Nombres,' ' , F.Apellidos)  AS Nombre_Funcionario

            FROM Ajuste_Individual A
            Inner Join Funcionario F ON F.Identificacion_Funcionario = A.Identificacion_Funcionario

            
            WHERE A.Estado = 'Activo' AND A.Estado_Salida_Bodega = 'Pendiente' 
            AND A.Id_Origen_Destino IN ($bodegas)"
            ;

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$ajustes_salida = $oCon->getData();

echo json_encode($ajustes_salida);

?>