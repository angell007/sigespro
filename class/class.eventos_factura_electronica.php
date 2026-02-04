<?php
require_once __DIR__ . '/../config/start.inc.php';
include_once 'class.lista.php';
include_once 'class.complex.php';
include_once 'class.consulta.php';

class Eventos_Factura_Electronica
{
    private $horario = [], $code_document = '', $datos = [], $evento = '', $id_evento = '', $proveedor = '', $resolucion = '', $configuracion = '', $funcionario = '', $Factura;

    public function __construct($evento, $id_evento, $resolucion_evento)
    {
        $this->evento = $evento;
        $this->id_evento = $id_evento;  
        self::getDatos($evento, $id_evento, $resolucion_evento);
    }

    public function GeneraJson()
    {
        $type_document = '';
        switch ($this->evento) {
            case 'Acuse_Recibo_Factura':
                $type_document = 10;
                $this->code_document = '030';
                break;
            case 'Acuse_Recibo_Bien_Servicio':
                $type_document = 12;
                $this->code_document = '032';
                break;
            case 'Aceptacion_Expresa_Factura':
                $type_document = 9;
                $this->code_document = '033';
                break;
            case 'Rechazo_Factura':
                $type_document = 14;
                $this->code_document = '031';
                break;
            case 'Aceptacion_Tacita':
                $type_document = 13;
                $this->code_document = '034';
                break;
            default:
                $type_document = 0;
                $this->code_document = '0';
                break;
        }
        $this->horario['date'] = date("Y-m-d");
        $this->horario['time'] = date("H:i:s");
        $json_evento["cude_propio"] = $this->getCude();
        $json_evento["type_document_id"] = $type_document;
        

        $this->datos['Id_Tipo_Reclamacion'] ? $json_evento["type_rejection_id"] = $this->datos['Id_Tipo_Reclamacion'] : '';
        $json_evento["code"] = $this->datos['Codigo'];
        $json_evento["number"] = str_replace($this->resolucion['Codigo'], '', $this->datos['Codigo']);
        $json_evento["prefix"] = $this->resolucion['Codigo'];
        $json_evento["file"] = $this->datos['Codigo'];
        $json_evento["time"] = $this->horario['time'];
        $json_evento["date"] = $this->horario['date'];

        /*Proveedor*/
        $json_evento["supplier"] = array(
            "identification_number" => $this->proveedor['Id_Proveedor'],
            "dv" => $this->proveedor['Digito_Verificacion'],
            "type_document_identification_id" => 6,
            "type_organization_id" => (($this->proveedor["Tipo"] == "Juridico") ? 1 : 2),
            "type_regime_id" => (($this->proveedor["Regimen"] == "Comun") ? 2 : 1),
            "tax_id" => (($this->proveedor["Regimen"] == "Comun") ? 1 : 16),
            "name" => $this->proveedor['Nombre'],
        );
 
        /* Persona que genera el evento*/
        $json_evento["person"] = array(
            "identification_number" => $this->funcionario['Identificacion_Funcionario'],
            "firstName" => $this->funcionario['Nombres'],
            "familyName" => $this->funcionario['Apellidos'],
            "type_document_identification_id" => 3,
            "jobTitle" => $this->funcionario['Cargo'],
            "organizationDepartment" => $this->funcionario['Dependencia'],
        );
        $json_evento["reference"] = array(
            
            "type_document_id" =>  $this->Factura['Tipo_Documento'] =='01'? 1:  4,
            "code" => $this->Factura['Codigo_Factura'],
            "cufe" => $this->Factura['Cufe']
        );

        return $json_evento;
    }
    
    private function getCude()
    {
        $NumNE = $this->datos['Codigo'];
        $FecNE = $this->horario['date'];
        $HorNE = $this->horario['time']."-05:00";
        $NitFE = $this->limpiarString(str_replace('-5','',$this->configuracion['NIT']));
        $DocAdq= $this->proveedor['Id_Proveedor'];
        $ResponseCode =  $this->code_document;
        $ID = $this->Factura['Codigo_Factura'];
        $DocumentTypeCode = $this->Factura['Tipo_Documento'];
        $SoftwarePin = '80401';

        return hash('sha384',($NumNE.$FecNE.$HorNE.$NitFE.$DocAdq.$ResponseCode.$ID.$DocumentTypeCode.$SoftwarePin));
        
    }


    private function getDatos($evento, $id_evento, $resolucion_evento)
    {

        $oItem = new complex("Resolucion", "Id_Resolucion", $resolucion_evento);
        $this->resolucion = $oItem->getData();
        unset($oItem);

        $oItem = new complex($evento, "Id_" . $evento, $id_evento);
        $this->datos = $oItem->getData();
        unset($oItem);

        $query = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->configuracion = $oCon->getData();
        unset($oCon);

        $query = "SELECT C.Nombre as Cargo, 
                    D.Nombre AS Dependencia, F.* 
                    FROM Funcionario F
                    INNER JOIN Cargo C on C.Id_Cargo = F.Id_Cargo
                    INNER JOIN Dependencia D ON D.Id_Dependencia = F.Id_Dependencia
                    WHERE F.Identificacion_Funcionario = " . $this->datos['Identificacion_Funcionario'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->funcionario = $oCon->getData();
        unset($oCon);

        if ($evento == 'Aceptacion_Tacita') {

            $oItem = new complex("Factura_Venta", "Id_Factura_Venta", $this->datos['Id_Factura']);
            $this->Factura = $oItem->getData();
            unset($oItem);

            $this->Factura['Codigo_Factura'] = $this->Factura['Codigo'];
            $this->Factura['Tipo_Documento'] = '01';

            $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente as Id_Proveedor, Contribuyente, Autorretenedor,
                (CASE
                    WHEN Tipo = "Juridico" THEN Razon_Social
                    ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

                END) AS Nombre,
                Correo_Persona_Contacto,
                Celular, Tipo, Tipo_Identificacion,
                Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
                Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                FROM Cliente WHERE Id_Cliente =' . $this->Factura['Id_Cliente'];
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->proveedor = $oCon->getData();
        } else {

            $oItem = new complex("Factura_Recibida", "Id_Factura_Recibida", $this->datos['Id_Factura_Recibida']);
            $this->Factura = $oItem->getData();
            unset($oItem);



            $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Proveedor , "No" as Contribuyente, "No" as Autorretenedor,
                (CASE
                WHEN Tipo = "Juridico" THEN Razon_Social
                ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                END) AS Nombre,
                Correo AS Correo_Persona_Contacto,
                Celular, Tipo, "NIT" AS Tipo_Identificacion,
                Digito_Verificacion, Regimen, Direccion ,Telefono,
                Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                FROM Proveedor WHERE Id_Proveedor = ' . $this->Factura['Id_Proveedor'];

            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->proveedor = $oCon->getData();
            unset($oCon);
        }
        if ($this->proveedor['Digito_Verificacion'] == '') {

            $nit = $this->limpiarString($this->Factura['Id_Proveedor']);

            $totalSum = 0;
            $nrosPrimos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

            $carLength = strlen($nit);

            $j = 0;
            for ($i = ($carLength - 1); $i >= 0; $i--) {
                $nro = $nit[$i];
                $totalSum += ($nro * $nrosPrimos[$j]);

                $j++;
            }

            $mod = $totalSum % 11;

            $digito_verificacion = $mod > 1 ? (11 - $mod) : $mod;
            $this->proveedor['Digito_Verificacion'] = $digito_verificacion;
            $oItem = new complex("Proveedor", "Id_Proveedor", $this->proveedor['Id_Proveedor']);
            $oItem->Digito_Verificacion = $digito_verificacion;
            $oItem->save();
            unset($oItem);
        }
    }
    private function limpiarString($nit)
    {

        $car1 = ['.', '-'];
        $clean = ['', ''];

        return str_replace($car1, $clean, $nit);
    }
}
