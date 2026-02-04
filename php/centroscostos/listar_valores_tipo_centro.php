<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    //$id_tipo = (isset($_REQUEST['id_tipo']) && $_REQUEST['id_tipo'] != "") ? $_REQUEST['id_tipo'] : '';
    $tipo = (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") ? $_REQUEST['tipo'] : '';

    $result = array();

    switch ($tipo) {
        case 'Tercero':
            
            $query = '
                SELECT
                    Id_Cliente AS Id,
                    Nombre AS Nombre
                FROM Cliente';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $result = $oCon->getData();
            unset($oCon);
            break;

        case 'Departamento':
            
            $query = '
                SELECT
                    Id_Departamento AS Id,
                    Nombre AS Nombre
                FROM Departamento';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $result = $oCon->getData();
            unset($oCon);
            break;

        case 'Punto de Dispensacion':
            
            $query = '
                SELECT
                    Id_Punto_Dispensacion AS Id,
                    Nombre AS Nombre
                FROM Punto_Dispensacion';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $result = $oCon->getData();
            unset($oCon);
            break;
        
        case 'Municipio':
            
            $query = '
                SELECT
                    Id_Municipio AS Id,
                    Nombre
                FROM Municipio ORDER BY Nombre';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $result = $oCon->getData();
            unset($oCon);
            break;
        case 'Zonas':
            
            $query = '
                SELECT
                    Id_Zona AS Id,
                    Nombre
                FROM Zona ORDER BY Nombre';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $result = $oCon->getData();
            unset($oCon);
            break;
        
        default:
            $result = $result;
            break;
    }

    echo json_encode($result);
?>   