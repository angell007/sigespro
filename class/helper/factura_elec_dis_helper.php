<?php

function getDatosDisHelper($Id_Dispensacion, $configuracion, $cliente)
{

    /**
     * Código del prestador de servicios de salud
            2. Tipo de documento de identificación del usuario 1
            3. Número de documento de identificación del usuario1
            4. Primer apellido del usuario1
            5. Segundo apellido del usuario 1
            6. Primer nombre del usuario 1
            7. Segundo nombre del usuario 1
            8. Tipo de usuario / regimen  1
            9. Modalidades de contratación y de pago / del contrato 1
            10. Cobertura o plan de beneficios xxx
            11. Número de autorización xxx
            12. Número de mi prescripción (MIPRES) 1
            13. Número de ID entrega de mi prescripción (MIPRES)
            14. Número de contrato
            15. Número de póliza
            16. Copago
            17. Cuota moderadora
            18. Cuota de recuperación
            19. Pagos compartidos en planes voluntarios de salud
            20. Fecha de inicio del periodo de facturación
            21. Fecha final del periodo de facturación
     * 
     */

    $oCon = new complex('Dispensacion', 'Id_Dispensacion', $Id_Dispensacion);
    $dispensacion = $oCon->getData();
    unset($oCon);


    $query = 'SELECT P.Tipo_Documento, P.Id_Paciente, P.Primer_Apellido , 
                                    P.Segundo_Apellido , P.Primer_Nombre , P.Segundo_Nombre,
                                     R.Nombre AS Regimen
                        FROM Paciente P
                        INNER JOIN Regimen R ON R.Id_Regimen = P.Id_Regimen 
                        WHERE P.Id_Paciente = ' . $dispensacion['Numero_Documento'];
    $oCon = new consulta();
    $oCon->setQuery($query);

    $paciente = $oCon->getData();
    unset($oCon);

    $data = [];
    $data['Codigo_Prestador'] = $configuracion['Codigo'] ?? '';

    // CLIENTE/EPS (NO paciente) - Según XML real de producción
    $data['Tipo_Documento_Identificacion'] = ($cliente['Tipo_Identificacion'] == 'NIT') ? 'NIT' : 'CC';
    $data['Numero_Documento_Identificacion'] = $cliente['Id_Cliente'];

    // Si es persona jurídica (EPS), usar razón social completa en PRIMER_NOMBRE
    if ($cliente['Tipo'] == 'Juridico') {
        $data['Primer_Nombre'] = $cliente['Nombre']; // Razón social completa
        // NO incluir apellidos para persona jurídica
    } else {
        // Persona natural: incluir nombres y apellidos
        $data['Primer_Apellido'] = $paciente['Primer_Apellido'] ?? '';
        $data['Segundo_Apellido'] = $paciente['Segundo_Apellido'] ?? '';
        $data['Primer_Nombre'] = $paciente['Primer_Nombre'] ?? '';
        $data['Segundo_Nombre'] = $paciente['Segundo_Nombre'] ?? '';
    }

    $data['Tipo_Usuario'] = $paciente['Regimen'];

    $oItem = new complex('Modalidad_Contratacion_Pago', 'Id_Modalidad_Contratacion_Pago', 12);
    $modalidadContratacion = $oItem->getData();
    unset($oItem);

    $query = 'SELECT C.Codigo ,  CP.Nombre AS Cobertura
         FROM Contrato C 
         INNER JOIN  Cobertura_Plan_Beneficios CP on  CP.Id_Cobertura_Plan_Beneficios  =  C.Id_Cobertura_Plan_Beneficios 
          WHERE Tipo_Contrato = "EPS" AND C.Estado = "Activo" AND DATE(C.Fecha_Fin) >= CURDATE() AND Id_Cliente = ' . $cliente["Id_Cliente"];
    $oCon = new consulta();
    $oCon->setQuery($query);

    $contrato = $oCon->getData();
    unset($oCon);


    $data['Modalidad_Contratacion'] = $modalidadContratacion['Nombre']; // contrato Modalidad_Contratacion_Pago


    $data['Cobertura_Plan_Beneficios'] = $contrato['Cobertura']; // contrato  

    //miopre
    $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $dispensacion['Id_Dispensacion_Mipres']);
    $disMipres = $oItem->getData();
    unset($oItem);

    $query = 'SELECT GROUP_CONCAT(P.Id_Producto_Dispensacion_Mipres SEPARATOR ",")  AS  Ids_Mipres,
           GROUP_CONCAT(P.Numero_Autorizacion SEPARATOR ";") AS Numero_Autorizacion
            FROM Producto_Dispensacion P 
            WHERE P.Id_Dispensacion = ' . $dispensacion['Id_Dispensacion'] . ' 
             GROUP BY P.Id_Dispensacion';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $proDis = $oCon->getData();

    if ($disMipres) {
        # code...
        $query = 'SELECT GROUP_CONCAT(P.NoPrescripcion SEPARATOR ";")  AS  Nnumero_Mipres,
                                GROUP_CONCAT(P.ID SEPARATOR ";")   AS IDS
            FROM Producto_Dispensacion_Mipres P 
            WHERE P.Id_Dispensacion_Mipres IN (' . $proDis['Ids_Mipres'] . ' ) 
             GROUP BY P.Id_Dispensacion_Mipres';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $prodsDisMipres = $oCon->getData();
    }


    $prodsDis = [];


    $data['Numero_Autorizacion'] = $proDis['Numero_Autorizacion'];
    $data['Numero_Contrato'] = $contrato['Codigo']; // contrato
    $data['Numero_Poliza'] = '0005';  // ???
    
    $data['Copago'] =  0;  // contrato

    $data['Cuota_Moderadora'] = 0;  // contrato
    $data['Cuota_Recuperacion'] = 0;  // contrato
    $data['Pagos_Compartidos'] = 0;  // contrato

    return $data;
}
