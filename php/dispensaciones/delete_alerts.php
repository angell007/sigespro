<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.mipres.php');


class DeleteAlerts
{
    public function search(string $id)
    {
        try {

            $query = "SELECT * FROM Dispensacion WHERE Id_Dispensacion = '$id' ";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Simple');
            $result = $oCon->getData();
            unset($oCon);
            return  $result[0]['Codigo'];
        } catch (\Throwable $th) {
            echo $th->getMessage();
        }
    }
    public function delete(string $id)
    {
        try {

            $query = "DELETE FROM Alerta WHERE Modulo = '$id'";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);

            return;
        } catch (\Throwable $th) {
            echo $th->getMessage();
        }
    }
}
