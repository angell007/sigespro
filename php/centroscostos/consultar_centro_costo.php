<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $id_centro = (isset($_REQUEST['id_centro']) && $_REQUEST['id_centro'] != "") ? $_REQUEST['id_centro'] : '';
    $opcion = (isset($_REQUEST['opcion']) && $_REQUEST['opcion'] != "") ? $_REQUEST['opcion'] : '';

    $centro_costo = array();

    if ($opcion == 'editar') {
        
        $query = '
        SELECT
            *
        FROM Centro_Costo
        WHERE
            Id_Centro_Costo = '.$id_centro;

        $oCon= new consulta();
        $oCon->setQuery($query);
        $centro_costo = $oCon->getData();
        unset($oCon);

    }else{

        $query = '
        SELECT
            Nombre AS NombreCentro,
            Codigo AS CodigoCentro,
            (IF(CC.Id_Centro_Padre != 0, (SELECT Nombre FROM Centro_Costo WHERE Id_Centro_Costo = CC.Id_Centro_Padre), "Sin Padre")) AS PadreCentro,
            (SELECT Nombre FROM Tipo_Centro WHERE Id_Tipo_Centro = CC.Id_Tipo_Centro) AS TipoCentro
        FROM Centro_Costo CC
        WHERE
            Id_Centro_Costo = '.$id_centro;

        $oCon= new consulta();
        $oCon->setQuery($query);
        $centro_costo = $oCon->getData();
        unset($oCon);

        if ($centro_costo != false) {
            
            switch ($value['TipoCentro']) {
                case 'Tercero':
                    
                    $query = '
                        SELECT
                            Nombre
                        FROM Cliente
                        WHERE
                            Id_Cliente = '.$value['Id_Tipo_Centro'];

                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $nombre_tercero = $oCon->getData();
                    unset($oCon);

                    $centro_costo['ValorTipoCentro'] = $nombre_tercero['Nombre'];
                    break;

                case 'Departamento':
                    
                    $query = '
                        SELECT
                            Nombre
                        FROM Departamento
                        WHERE
                            Id_Departamento = '.$value['Id_Tipo_Centro'];

                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $nombre_departamento = $oCon->getData();
                    unset($oCon);

                    $centro_costo['ValorTipoCentro'] = $nombre_departamento['Nombre'];
                    break;

                case 'Punto de Dispensacion':
                    
                    $query = '
                        SELECT
                            Nombre
                        FROM Punto_Dispensacion
                        WHERE
                            Id_Punto_Dispensacion = '.$value['Id_Tipo_Centro'];

                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $nombre_punto = $oCon->getData();
                    unset($oCon);

                    $centro_costo['ValorTipoCentro'] = $nombre_punto['Nombre'];
                    break;
                
                default:
                    $centro_costo['ValorTipoCentro'] = '';
                    break;
            }
        }
    }
    

    echo json_encode($centro_costo);
?>   