<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $id_centro = (isset($_REQUEST['id_centro']) && $_REQUEST['id_centro'] != '') ? $_REQUEST['id_centro'] : '';
    $centros_padre = array();
    $centros_hijos = array();
    $centros = '';

    if ($id_centro != '') {
        CicloInverso($id_centro);
        ConsultarHijos($centros_padre);
        //var_dump($centros_padre);
        $centros = ConvertirEnCadena($centros_hijos);
    }


    //$condicion = '';

    /*if ($condicion != "") {
        if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != "") {
            $condicion .= " AND Nombre LIKE '%$_REQUEST[nombre]%'";
        }
    } else {
        if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != "") {
            $condicion .= " WHERE Nombre LIKE '%$_REQUEST[nombre]%'";
        } 
    }

    if ($condicion != "") {
        if (isset($_REQUEST['codigo']) && $_REQUEST['codigo'] != "") {
            $condicion .= " AND Codigo LIKE '%$_REQUEST[codigo]%'";
        }
    } else {
        if (isset($_REQUEST['codigo']) && $_REQUEST['codigo'] != "") {
            $condicion .= " WHERE Codigo LIKE '%$_REQUEST[codigo]%'";
        } 
    }

    if ($condicion != "") {
      if (isset($_REQUEST['cuenta']) && $_REQUEST['cuenta'] != "") {
        $condicion .= " AND Cuenta LIKE '%$_REQUEST[cuenta]%'";
      }
    } else {
      if (isset($_REQUEST['cuenta']) && $_REQUEST['cuenta'] != "") {
        $condicion .= "WHERE Cuenta LIKE '%$_REQUEST[cuenta]%'";
      }
    }

    $query = 'SELECT COUNT(*) AS Total FROM Grupo '.$condicion;

    $oCon= new consulta();

    $oCon->setQuery($query);
    $total = $oCon->getData();
    unset($oCon);

    ####### PAGINACIÓN ######## 
    $tamPag = 20; 
    $numReg = $total["Total"]; 
    $paginas = ceil($numReg/$tamPag); 
    $limit = ""; 
    $paginaAct = "";

    if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
        $paginaAct = 1; 
        $limit = 0; 
    } else { 
        $paginaAct = $_REQUEST['pag']; 
        $limit = ($paginaAct-1) * $tamPag; 
    } 

    $query = 'SELECT *
    FROM Grupo '. $condicion.' 
    ORDER BY  Nombre DESC LIMIT '.$limit.','.$tamPag.'';

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $resultado['costos'] = $oCon->getData();
    unset($oCon);


    $resultado['numReg'] = $numReg;

    echo json_encode($resultado);*/

    //NUEVO CODIGO
    $query = '';

    $condicion = SetCondiciones($_REQUEST); // Obtener filtros.

    if ($id_centro == '') {
        $query = '
            SELECT 
                CC.*,
                (IF(CC.Id_Centro_Padre != 0, (SELECT Nombre FROM Centro_Costo WHERE Id_Centro_Costo = CC.Id_Centro_Padre), "Sin Padre")) AS PadreCentro,
                IF(CC.Id_Tipo_Centro != 0, (SELECT Nombre FROM Tipo_Centro WHERE Id_Tipo_Centro = CC.Id_Tipo_Centro), "") AS Tipo_Centro,
                (CASE CC.Id_Tipo_Centro
                    WHEN 1 THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = CC.Valor_Tipo_Centro)
                    WHEN 2 THEN (SELECT Nombre FROM Departamento WHERE Id_Departamento = CC.Valor_Tipo_Centro)
                    WHEN 3 THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = CC.Valor_Tipo_Centro)
                    WHEN 4 THEN (SELECT Nombre FROM Municipio WHERE Id_Municipio = CC.Valor_Tipo_Centro)
                    WHEN 5 THEN (SELECT Nombre FROM Zona WHERE Id_Zona = CC.Valor_Tipo_Centro)
                    ELSE ""
                END) AS ValorTipoCentro
            FROM 
                Centro_Costo CC '. $condicion . ' ORDER BY Codigo';

    }else{

        if ($centros == '') {
            $centros = 0;
        }

        $query = '
            SELECT 
                CC.*,
                (IF(CC.Id_Centro_Padre != 0, (SELECT Nombre FROM Centro_Costo WHERE Id_Centro_Costo = CC.Id_Centro_Padre), "Sin Padre")) AS PadreCentro,
                IF(CC.Id_Tipo_Centro != 0, (SELECT Nombre FROM Tipo_Centro WHERE Id_Tipo_Centro = CC.Id_Tipo_Centro), "") AS Tipo_Centro,
                (CASE CC.Id_Tipo_Centro
                    WHEN 1 THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = CC.Valor_Tipo_Centro)
                    WHEN 2 THEN (SELECT Nombre FROM Departamento WHERE Id_Departamento = CC.Valor_Tipo_Centro)
                    WHEN 3 THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = CC.Valor_Tipo_Centro)
                    WHEN 4 THEN (SELECT Nombre FROM Municipio WHERE Id_Municipio = CC.Valor_Tipo_Centro)
                    WHEN 5 THEN (SELECT Nombre FROM Zona WHERE Id_Zona = CC.Valor_Tipo_Centro)
                    ELSE ""
                END) AS ValorTipoCentro
            FROM 
                Centro_Costo CC
            WHERE
                CC.Id_Centro_Costo IN('.$centros.') ' . $condicion;

    }

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $res = $oCon->getData();
    $numReg = count($res);
    unset($oCon);

    if ($id_centro == '') {
        
        $resultado['Centros'] = getPagination($query, $_REQUEST, $res);
    } else {
        $resultado['Centros'] = $res;
    }

    $query = "SELECT Id_Centro_Costo AS value, CONCAT(Codigo,' - ',Nombre) AS label FROM Centro_Costo WHERE Movimiento = 'No' AND Estado = 'Activo'"; // Lista para que escojan el padre del centro de costo a crear.

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $resultado['CentrosCostosPadre'] = $oCon->getData();
    unset($oCon);

    $resultado['numReg'] = $numReg;

    echo json_encode($resultado);


    function getPagination($query, $req, $resulset) {
        ####### PAGINACIÓN ######## 
    $tamPag = 20; 
    $numReg = count($resulset); 
    $paginas = ceil($numReg/$tamPag); 
    $limit = ""; 
    $paginaAct = "";

        if (!isset($req['pag']) || $req['pag'] == '') { 
            $paginaAct = 1; 
            $limit = 0; 
        } else { 
            $paginaAct = $req['pag']; 
            $limit = ($paginaAct-1) * $tamPag; 
        }

        $query = $query . " LIMIT $limit,$tamPag";


        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $resultado = $oCon->getData();
        unset($oCon);

        return $resultado;
    }

    function CicloInverso($idCentro){
        global $centros_padre;

        $query = '
            SELECT 
                Id_Centro_Padre
            FROM 
                Centro_Costo
            WHERE
                Id_Centro_Costo = '.$idCentro;

        $oCon= new consulta();
        $oCon->setQuery($query);
        $centro_padre = $oCon->getData();
        $centro_padre = $centro_padre != false ? $centro_padre['Id_Centro_Padre'] : '';
        unset($oCon);

        if ($centro_padre != '') {
            
            array_push($centros_padre, $centro_padre);
            CicloInverso($centro_padre);
        }else{
            return;
        }
    }

    function ConvertirEnCadena($centros){
        $cadena = '';

        if(count($centros) == 0){
            return $cadena;
        }

        for ($i=0; $i <= (count($centros) - 1) ; $i++) { 
            if (($i + 1) == count($centros)) {
                $cadena .= $centros[$i];
            }else if(($i + 1) < count($centros)){
                $cadena .= $centros[$i].",";
            }
        }
        //var_dump($cadena);
        return $cadena;
    }

    function ConsultarHijos($idsPadre){
        global $centros_hijos;
        //$separados = explode(",", $idsPadre);
        //var_dump($idsPadre);

        if (count($idsPadre) > 0) {
            $ind = 0;
            $q = '';     
            $ids = '';       

            foreach ($idsPadre as $v) {

                if ($idsPadre[$ind + 1] != '' || $idsPadre[$ind + 1] != null) {
                    $q = '
                        SELECT 
                            GROUP_CONCAT(Id_Centro_Costo) AS Id_Centros
                        FROM 
                            Centro_Costo
                        WHERE
                            Id_Centro_Padre = '.$idsPadre[$ind + 1];

                    $oCon= new consulta();
                    $oCon->setTipo('Multiple');
                    $oCon->setQuery($q);
                    $ids = $oCon->getData();
                    unset($oCon);
                }else{
                    return;
                }
                    

                //var_dump($ids);

                if ($ids[0]['Id_Centros'] != '') {
                    array_push($centros_hijos, $ids[0]['Id_Centros']);
                }                

                $ind++;
            }
        }
    }
    
    function SetCondiciones($req){
        $condicion = '';
        global $id_centro;

        if ($id_centro == '') {
            if (isset($req['cod']) && $req['cod'] != "") {
                $condicion .= " WHERE Codigo LIKE '%".$req['cod']."%'";
            }
    
            if (isset($req['nom']) && $req['nom'] != "") {
                if ($condicion != "") {
                    $condicion .= " AND Nombre LIKE '%".$req['nom']."%'";
                } else {
                    $condicion .=  " WHERE Nombre LIKE '%".$req['nom']."%'";
                }
            }
        } else {
            if (isset($req['cod']) && $req['cod'] != "") {
                $condicion .= " AND Codigo LIKE '%".$req['cod']."%'";
            }
    
            if (isset($req['nom']) && $req['nom']) {
                $condicion .= " AND Nombre LIKE '%".$req['nom']."%'";
            }
        }
        if ($condicion != "") {
            $condicion .= " AND Estado = 'Activo'";
        } else {
            $condicion .= " WHERE Estado = 'Activo'";
        }



        return $condicion;
    }

?>