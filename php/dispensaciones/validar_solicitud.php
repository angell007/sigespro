<?php


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.consulta.php';
include_once '../../class/class.complex.php';

$id_Dispensacion = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$estado = isset($_REQUEST['estado']) ? $_REQUEST['estado'] : 'Pre Auditado';
$solicitud = getAutorizacionesNuevo($id_Dispensacion, $estado);

echo json_encode($solicitud); exit;


function getAutorizacionesNuevo($id_Dispensacion, $estado)
{
      $oItem = new complex('Positiva_Data', 'Id_Dispensacion', $id_Dispensacion);
      $autorizacion = $oItem->getData();
      $solicitud = ['Autorizaciones_No_Asociadas'=>[], 'Dis_Creadas'=>[]];

      if ($autorizacion) {
            $query = "SELECT POS.numeroAutorizacion AS Autorizaciones_No_Asociadas
            FROM Positiva_Data POS 
            Where POS.Id_Dispensacion is null And POS.RLnumeroSolicitudSiniestro = '$autorizacion[RLnumeroSolicitudSiniestro]'";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $solicitud['Autorizaciones_No_Asociadas']=[];
            $p['Autorizaciones_No_Asociadas'] = $oCon->getData();
            foreach ($p['Autorizaciones_No_Asociadas'] as $i => $value) {
                  $v = $value['Autorizaciones_No_Asociadas'];
                  $solicitud['Autorizaciones_No_Asociadas'][$i] = $v;
            }

            $solicitud['Solicitud'] = $autorizacion['RLnumeroSolicitudSiniestro'];
            $query = "SELECT D.Id_Dispensacion as id, A.Id_Auditoria, D.Codigo as 'Value'
                        FROM Dispensacion D 
                        Inner Join Positiva_Data POS
                        INNER JOIN Auditoria A on A.Id_Dispensacion = D.Id_Dispensacion
                        Where D.Id_Dispensacion = POS.Id_Dispensacion
                        and A.Estado ='$estado' 
                        AND D.Estado_Dispensacion != 'Anulada'
                        AND POS.RLnumeroSolicitudSiniestro = '$autorizacion[RLnumeroSolicitudSiniestro]'";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $solicitud['Dis_Creadas'] = $oCon->getData();
      }

      return($solicitud);
}


function getAutorizaciones($id_Dispensacion)
{

      $query = "SELECT
            Concat('[\"', GROUP_CONCAT(IF(POS.Id_Dispensacion IS NULL, POS.numeroAutorizacion, NULL) SEPARATOR '\",\"'), '\"]') AS Autorizaciones_No_Asociadas,
            POS.RLnumeroSolicitudSiniestro as Solicitud,
            CONCAT('[{', GROUP_CONCAT(IF(POS.Id_Dispensacion IS NOT NULL,(
                  CONCAT(
                              '\"id\": \"',
                  (SELECT D.Id_Dispensacion FROM Dispensacion D 
                        INNER JOIN Auditoria A on A.Id_Dispensacion = D.Id_Dispensacion
                        Where D.Id_Dispensacion = POS.Id_Dispensacion
                        and A.Estado ='Pre Auditado' 
                        AND D.Estado_Dispensacion != 'Anulada'
                  ), 
                  '\",\"Value\": \"',
                  (SELECT D.Codigo FROM Dispensacion D 
                        INNER JOIN Auditoria A on A.Id_Dispensacion = D.Id_Dispensacion
                        Where D.Id_Dispensacion = POS.Id_Dispensacion
                        and A.Estado ='Pre Auditado' 
                        AND D.Estado_Dispensacion != 'Anulada'
                  )
                              )), NULL) SEPARATOR '\"},{'), '\"}]') AS Dis_Creadas
            FROM Positiva_Data POS
            GROUP BY POS.RLnumeroSolicitudSiniestro
            having  (GROUP_CONCAT(POS.Id_Dispensacion)) LIKE  '%$id_Dispensacion%'";
      $oCon = new consulta();
      $oCon->setQuery($query);
      $solicitud = $oCon->getData();
      // echo ($query); exit;
      // echo json_encode($solicitud); exit;

      $solicitud['Autorizaciones_No_Asociadas'] = (array) json_decode($solicitud['Autorizaciones_No_Asociadas'], true);
      $solicitud['Dis_Creadas'] = (array) json_decode($solicitud['Dis_Creadas'], true);
      return ($solicitud);
}
