<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idMeta = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false;

if ($idMeta) {
    # code...

    $query = 'SELECT 
                M.Id_Metas,
                M.Anio,
                M.Identificacion_Funcionario, 
                M.Objetivo_General ,
                M.Objetivo_Especifico,
                M.Directriz_Comercial ,
                SUM( IFNULL( Valor_Medicamentos , 0) ) AS Medicamento,
                SUM( IFNULL( Valor_Materiales  , 0 ) ) AS Material,
                UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario
                FROM Metas M
                INNER JOIN Metas_Zonas MZ ON MZ.Id_Meta = M.Id_Metas
                INNER JOIN Objetivos_Meta OM ON OM.Id_Metas_Zonas = MZ.Id_Metas_Zonas
                INNER JOIN Funcionario F ON F.Identificacion_Funcionario = M.Identificacion_Funcionario
                    WHERE Id_Metas = '.$idMeta;





    $oCon = new consulta();
    $oCon->setQuery($query);
    $meta = $oCon->getData();
    unset($oCon);
    if ($meta) {
        $query = 'SELECT 
        MZ.Id_Metas_Zonas,
        MZ.Id_Zona,
        Z.Nombre AS Nombre_Zona
        FROM Metas_Zonas MZ
        INNER JOIN Zona Z ON Z.Id_Zona = MZ.Id_Zona

     
            WHERE Id_Meta = '.$idMeta;

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $zonas = $oCon->getData();

        if($zonas){
            foreach ($zonas as $key => $zona) {
                # code...
                $funcionarios = getFuncionariosMeta($zona['Id_Metas_Zonas']);
                
                foreach ($funcionarios as $key1 => $funcionario) {
                    # code...
                    $query = 'SELECT 
                    O.Valor_Medicamentos,
                    O.Valor_Materiales,
                    O.Mes

                    FROM Objetivos_Meta O
                    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
                        WHERE O.Identificacion_Funcionario = '.$funcionario['Identificacion_Funcionario'].'
                            AND O.Id_Metas_Zonas = '.$zona['Id_Metas_Zonas'].' ORDER BY O.Id_Objetivos_Meta';
        
        
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $oCon->setTipo('Multiple');
                    $Objetivos = $oCon->getData();
                    
                    $funcionarios[$key1]['Objetivos']= $Objetivos;
                    
                }
                $zonas[$key]['Funcionarios'] = $funcionarios;

            }
          
        }
    }
    
    $res['meta'] = $meta;
    $res['zonas'] = $zonas;
    echo  json_encode($res);
}

function getFuncionariosMeta($idMetaZona){
    $query = 'SELECT 
                O.Identificacion_Funcionario,
                UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Nombre_Funcionario
                FROM Objetivos_Meta O
                INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
            WHERE O.Id_Metas_Zonas = '.$idMetaZona.'
            GROUP BY O.Identificacion_Funcionario';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
   return $oCon->getData();
}

