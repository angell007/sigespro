<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once './delete_alerts.php';
require_once '../../config/start.inc.php';
include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.mipres.php';


$queryObj = new QueryBaseDatos();

$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$func = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$motivo = (isset($_REQUEST['motivo']) ? $_REQUEST['motivo'] : '');
$retornar = (isset($_REQUEST['retornar']) ? $_REQUEST['retornar'] : 'Si');
$resultado = false;


$datos = explode(' ', $datos);

foreach ($datos as $disp) {
    $resu[$disp] = eliminar($disp);
}
echo json_encode($resu);
function eliminar($cod_dispensacion)
{
    global $func, $motivo, $retornar;
    try {

        $query = "SELECT* FROM Dispensacion WHERE Codigo= '" . $cod_dispensacion . "'";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('simple');
        $dis = $oCon->getData();
        $idDis = $dis['Id_Dispensacion'];
        unset($oCon);

        $query = "SELECT Id_Inventario_Nuevo, Cantidad_Entregada FROM Producto_Dispensacion WHERE Id_Dispensacion= " . $idDis . " AND Lote <> 'Pendiente'";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

        $query = "SELECT id, Id_Dispensacion
          FROM Positiva_Data
          WHERE Id_Dispensacion= " . $idDis;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $positiva = $oCon->getData();
        unset($oCon);

        if ($dis['Estado_Facturacion'] && $dis['Estado_Facturacion'] != 'Facturada') {
            if ($dis['Estado_Dispensacion'] && $dis['Estado_Dispensacion'] != 'Anulada') {
                if ($positiva) {
                    $query = "UPDATE Positiva_Data SET Id_Dispensacion = NULL WHERE id = " . $positiva["id"];
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $resultado = $oCon->createData();
                    unset($oCon);
                }

                $serviceDelete = new DeleteAlerts();

                $serviceDelete->delete($cod_dispensacion);

                if(strtolower($retornar)=='si'){
                    foreach ($productos as $prod) { // Ingresar nuevamente las cantidades al inventario.
                        $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $prod['Id_Inventario_Nuevo']);
                        $cantidad = number_format($prod['Cantidad_Entregada'], 0, "", "");
                        $cantidad_final = $oItem->Cantidad + $cantidad;
                        $oItem->Cantidad = number_format($cantidad_final, 0, "", "");
                        $oItem->save();
                        unset($oItem);
                    }
                } else{
                    
                    $query = "UPDATE Producto_Dispensacion  SET Id_Inventario_Nuevo = NULL  WHERE Id_Dispensacion = " . $idDis;
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $resultado = $oCon->createData();
                }

                $query = "UPDATE Dispensacion SET Estado_Dispensacion = 'Anulada' WHERE Id_Dispensacion = " . $idDis;
                $oCon = new consulta();
                $oCon->setQuery($query);
                $resultado = $oCon->createData();
                unset($oCon);

                $ActividadDis["Identificacion_Funcionario"] = $func;
                $ActividadDis["Id_Dispensacion"] = $idDis;
                $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
                $ActividadDis["Detalle"] = "Esta dispensacion fue anulada por el siguiente motivo: " . $motivo;
                $ActividadDis["Estado"] = "Anulada";

                $oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
                foreach ($ActividadDis as $index => $value) {
                    $oItem->$index = $value;
                }
                $oItem->save();
                unset($oItem);


                $query = "SELECT Id_Dispensacion_Mipres FROM Dispensacion WHERE Id_Dispensacion= " . $idDis;
                $oCon = new consulta();
                $oCon->setQuery($query);
                $Id_Dispensacion_Mipres = $oCon->getData();
                unset($oCon);

                if ($Id_Dispensacion_Mipres) {
                    $mipres = new Mipres();

                    $query = "SELECT * FROM Producto_Dispensacion_Mipres WHERE Id_Dispensacion_Mipres=" . $Id_Dispensacion_Mipres["Id_Dispensacion_Mipres"];
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $oCon->setTipo("Multiple");
                    $lista = $oCon->getData();
                    unset($oCon);

                    foreach ($lista as $mipres_dis) {
                        if ($mipres_dis["IdReporteEntrega"] != '' && $mipres_dis["IdReporteEntrega"] != '0') { //echo "entro a eliminar reporte entrega<br>";
                            $res1 = $mipres->AnularReporteEntrega($mipres_dis["IdReporteEntrega"]);
                        }
                        if ($mipres_dis["IdEntrega"] != '' && $mipres_dis["IdEntrega"] != '0') { //echo "entro a eliminar id entrega<br>";
                            $res2 = $mipres->AnularEntrega($mipres_dis["IdEntrega"]);
                        }
                        if ($mipres_dis["IdProgramacion"] != '' && $mipres_dis["IdProgramacion"] != '0') { //echo "entro a eliminar programacion<br>";
                            $res3 = $mipres->AnularProgramacion($mipres_dis["IdProgramacion"]);
                        }
                        $query = "UPDATE Producto_Dispensacion_Mipres SET IdReporteEntrega=0, IdEntrega=0, IdProgramacion=0  WHERE Id_Producto_Dispensacion_Mipres = " . $mipres_dis["Id_Producto_Dispensacion_Mipres"];
                        $oCon = new consulta();
                        $oCon->setQuery($query);
                        $res = $oCon->createData();
                        unset($oCon);
                    }

                    $query = "UPDATE Dispensacion_Mipres 
                        SET Estado = 'Pendiente',
                            Estado_Callcenter = 'Pendiente',
                            Fecha_Contacto = NULL,
                            Observaciones_Callcenter = NULL
                        WHERE Id_Dispensacion_Mipres = " . $Id_Dispensacion_Mipres["Id_Dispensacion_Mipres"];
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $resultado = $oCon->createData();
                    unset($oCon);
                }


                return ("Anulado Correctamente");
            } else {
                return ("Anulada con anterioridad");
            }
        } else {
            return ("Dispensacion ya facturada, no se puede anular");
        }
    } catch (\Throwable $th) {
        return ("No anulado" . $th);
    }
}
