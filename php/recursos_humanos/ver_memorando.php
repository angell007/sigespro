<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');
header("Content-type: application/pdf");
header("Content-Disposition:attachment;filename='downloaded.pdf'");

include_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.mensajes.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.php_mailer.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
    $query = 'SELECT M.Id_Memorando, M.Fecha Fecha, CONCAT(F.Nombres, " ", F.Apellidos) Nombres, "Memorando" as Tipo, CA.Nombre_Categoria FROM Memorando M
                INNER JOIN Categorias_Memorando CA ON M.Motivo = CA.Id_Categorias_Memorando
                INNER JOIN Funcionario F ON M.Identificacion_Funcionario = F.Identificacion_Funcionario
                INNER JOIN Alerta A ON F.Identificacion_Funcionario = A.Identificacion_Funcionario
                WHERE M.Id_Memorando = '.$id.' LIMIT 1';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $func = $oCon->getData();

    function obtenerFechaEnLetra($fecha){
   
        $num = date("j", strtotime($fecha));
        $anno = date("Y", strtotime($fecha));
        $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $mes = $mes[(date('m', strtotime($fecha))*1)-1];
        return $num.' de '.$mes.' de '.$anno;
    }

    echo json_encode($func);
    


