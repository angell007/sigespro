<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');


$id_ajuste = ( isset( $_REQUEST['id_ajuste'] ) ? $_REQUEST['id_ajuste'] : '' );


$query = 'SELECT A.* ,
            (SELECT CONCAT(F.Nombres," " , F.Apellidos) FROM Funcionario F
                WHERE F.Identificacion_Funcionario = A.Identificacion_Funcionario) As Nombre_Funcionario
            FROM Ajuste_Individual A

            WHERE A.Id_Ajuste_Individual = '.$id_ajuste;


$oCon = new consulta();
$oCon->setQuery($query);
$ajustes_salida = $oCon->getData();


$query = 'SELECT PA.*,
           
            CONCAT_WS(
                " ",
                P.Nombre_Comercial,
                CONCAT("(", P.Principio_Activo),
                P.Presentacion,
                P.Concentracion,
                P.Cantidad,
                CONCAT(P.Unidad_Medida, ")"),
                "LAB -",
                P.Laboratorio_Comercial,
                "CUM:",
                P.Codigo_Cum
            ) AS Nombre_Producto,
            P.Embalaje,
            (SELECT E.Nombre FROM Estiba E WHERE E.Id_Estiba = I.Id_Estiba) AS Nombre_Estiba,
            IFNULL( ( SELECT C.Costo_Promedio FROM Costo_Promedio C WHERE C.Id_Producto = P.Id_Producto) , 0 ) AS Costo
            FROM Producto_Ajuste_Individual PA
            INNER JOIN Producto P 
            ON P.Id_Producto = PA.Id_Producto
            INNER JOIN Inventario_Nuevo I 
            ON I.Id_Inventario_Nuevo = PA.Id_Inventario_Nuevo
            
            WHERE PA.Id_Ajuste_Individual = '.$id_ajuste;

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$ajustes_salida['Productos'] = $oCon->getData();

echo json_encode($ajustes_salida);

?>