<?php
require_once __DIR__ . '/../config/start.inc.php';
include_once 'class.lista.php';
include_once 'class.complex.php';
include_once 'class.consulta.php';
require_once 'class.qr.php';
require_once 'class.php_mailer.php';

require_once __DIR__ . '/helper/factura_elec_dis_helper.php';

class FacturaElectronica
{
    private $resolucion = '', $factura = '', $configuracion = '', $productos = [], $cliente = '', $totales = '', $tipo_factura = '', $id_factura = '';

    public function __construct($tipo_factura, $id_factura, $resolucion_facturacion)
    {
        $this->tipo_factura = $tipo_factura;
        $this->id_factura = $id_factura;
        self::getDatos($tipo_factura, $id_factura, $resolucion_facturacion);
    }

    public function __destruct()
    {
    }

    public function GenerarFactura()
    {

        $datos = $this->GeneraJson($this->tipo_factura);
        return ($datos);
    }
    private function GetMunicipio($idMunicipio)
    {
        $query = 'SELECT municipalities_id FROM Municipio WHERE Id_Municipio = ' . $idMunicipio;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $mun = $oCon->getData();
        return $mun['municipalities_id'];
    }

    private function GeneraJson($tipo_factura)
    {

        $resultado["cufe_propio"] = $this->getCufe();
        $resultado["number"] = (int) str_replace($this->resolucion['Codigo'], "", $this->factura['Codigo']);
        $resultado["type_document_id"] = 1;
        $resultado["resolution_id"] = $this->resolucion["resolution_id"];
        $resultado["date"] = date("Y-m-d", strtotime($this->factura["Fecha_Documento"]));
        $resultado["time"] = date("H:i:s", strtotime($this->factura["Fecha_Documento"]));
        //$resultado["send"]=true;
        $resultado["file"] = $this->getNombre();

        //nuevo

        $cliente['municipality_id'] = (int) $this->GetMunicipio($this->cliente['Id_Municipio']);
        $cliente['country_id'] = 46;

        $cliente["identification_number"] = $this->cliente["Id_Cliente"];
        //$cliente["dv"]=$this->cliente["Id_Cliente"];

        $cliente["name"] = trim($this->cliente["Nombre"]);
        $cliente["phone"] = (($this->cliente["Telefono"] != "" && $this->cliente["Telefono"] != "NULL") ? trim($this->cliente["Telefono"]) : "0000000");
        // $cliente["phone"]="0000000";

        $cliente["type_organization_id"] = (($this->cliente["Tipo"] == "Juridico") ? 1 : 2); /* Juridica 1 - Natural 2*/
        $cliente["type_document_identification_id"] = (($this->cliente["Tipo_Identificacion"] == "NIT") ? 6 : 3); /* 6 NIT - 3 Cedula */

        if ($this->cliente["Tipo_Identificacion"] == "NIT") {
            $cliente["dv"] = $this->cliente["Digito_Verificacion"];
        }

        $cliente["type_regime_id"] = (($this->cliente["Regimen"] == "Comun") ? 2 : 1); /* 1 Simplificado - 2 Comun */
        // $cliente["type_liability_id"]=2;
        //

        $cliente["type_liability_id"] = 122;

        if ($this->cliente["Contribuyente"] == "Si") {
            $cliente["type_liability_id"] = 118;
        }

        if ($this->cliente["Regimen"] == "Simplificado") {
            $cliente["type_liability_id"] = 121;
        }

        if ($this->cliente["Autorretenedor"] == "Si") {
            $cliente["type_liability_id"] = 119;
        }

        $cliente["address"] = trim((($this->cliente["Direccion"] != "" && $this->cliente["Direccion"] != "NULL") ? trim($this->cliente["Direccion"]) : "SIN DIRECCION"));
        $cliente["email"] = trim((($this->cliente["Correo_Persona_Contacto"] != "" && $this->cliente["Correo_Persona_Contacto"] != "NULL") ? trim($this->cliente["Correo_Persona_Contacto"]) : "facturacionelectronica@prohsa.com"));
        $cliente["merchant_registration"] = "No Tiene";

        //NUEVO
        $metodo_pago = [];
        //contado 2 efectivo 1
        $metodo_pago['payment_form_id'] = $this->factura['Condicion_Pago'] > 1 ? 2 : 1;

        $metodo_pago['payment_method_id'] = $this->factura['Condicion_Pago'] > 1 ? 30 : 31;
        $metodo_pago['payment_due_date'] = $this->factura['Fecha_Pago'];

        $metodo_pago['duration_measure'] = $this->factura['Condicion_Pago'];

        $resultado["customer"] = $cliente;

        $finales["line_extension_amount"] = number_format($this->totales["Total"]-$this->totales["Descuento"], 2, ".", "");
        $finales["tax_exclusive_amount"]  = number_format($this->totales["Total"]-$this->totales["Descuento"], 2, ".", "");
        $finales["tax_inclusive_amount"]  = number_format($this->totales["Total"]-$this->totales["Descuento"] + $this->totales["Total_Iva"], 2, ".", "");
        $finales["allowance_total_amount"] = number_format(0, 2, ".", "");
        $finales["charge_total_amount"] = 0;
        $finales["payable_amount"] = number_format(($this->totales["Total"] + $this->totales["Total_Iva"] - $this->totales["Descuento"]), 2, ".", "");

        $resultado["legal_monetary_totals"] = $finales;
        $j = -1;
        $produstos_finales = [];
        $base_imp = 0;
        $tot_imp = 0;

        $base_imp2 = 0;
        $tot_imp2 = 0;

        $base_des = 0;
        $tot_des = 0;

        $descue = [];
        foreach ($this->productos as $pro) {
            $j++;

            $descuento = 0;
            $base_gravable=0;
            if ($tipo_factura == "Factura_Venta") {
                $descuento = ($pro["Cantidad"] * $pro["Precio_Venta"]) * $pro["Descuento"] / 100;
                
                $base_gravable=$pro["Cantidad"] * $pro["Precio_Venta"]-$descuento;
                
                $tot = $pro["Cantidad"] * $pro["Precio_Venta"];
                $precio = $pro["Precio_Venta"];
            } else {
                $descuento = $pro["Cantidad"] * $pro["Descuento"];
                $base_gravable = $pro["Cantidad"] * $pro["Precio"]-$descuento;
                $tot = $pro["Cantidad"] * $pro["Precio"];
                $precio = $pro["Precio"];
            }
            if ($tot == 0) {
                $tot = 1;
            }
            if ($base_gravable == 0) {
                $base_gravable = 1;
            }
            
            // if($this->factura['Codigo']=='FEIN12827' &&  $pro["Descuento"] >0){
            //     echo $tot; exit;
            //     echo json_encode($pro); exit;
            // }

            $imp = $base_gravable * $pro["Impuesto"] / 100;
            if ($imp > 0) {
                $base_imp += $base_gravable;
                $tot_imp += $imp;
            } else {
                $base_imp2 += $base_gravable;
                $tot_imp2 += $imp;
            }
            $descuentos=[];
            if ($descuento > 0) {
                $descuentos[0]["charge_indicator"] = false;
                $descuentos[0]["allowance_charge_reason"] = 'Discount';
                $descuentos[0]["amount"] = number_format($descuento, 2, ".", "");
                $descuentos[0]["base_amount"] = number_format($tot, 2, ".", "");

                $base_des += $tot;
                $tot_des += $descuento;

            }

            $impuestos[0]["tax_id"] = 1;
            $impuestos[0]["tax_amount"] = number_format($imp, 2, ".", "");
            $impuestos[0]["taxable_amount"] = number_format($base_gravable, 2, ".", "");

            // $impuestos[0]["taxable_amount"] = number_format(1, 2, ".", "");
            $impuestos[0]["percent"] = $pro["Impuesto"];

            $productos_finales[$j]["unit_measure_id"] = 70;
            $productos_finales[$j]["invoiced_quantity"] = $pro["Cantidad"];
            $productos_finales[$j]["line_extension_amount"] = number_format($base_gravable, 2, ".", "");

            $productos_finales[$j]["free_of_charge_indicator"] = false;
            $productos_finales[$j]["reference_price_id"] = 1;

            if ((int) $precio == 0) {
                $productos_finales[$j]["free_of_charge_indicator"] = true;
                $productos_finales[$j]["reference_price_id"] = 1;
                $productos_finales[$j]["price_amount"] = number_format(1, 2, ".", "");


            } else {
                $productos_finales[$j]["free_of_charge_indicator"] = false;
                $productos_finales[$j]["reference_price_id"] = 1;
                $productos_finales[$j]["price_amount"] = number_format($precio, 2, ".", "");
            }

            $productos_finales[$j]["allowance_charges"] = $descuentos;

            $productos_finales[$j]["tax_totals"] = $impuestos;

            $productos_finales[$j]["description"] = trim($pro["Producto"]);
            $productos_finales[$j]["code"] = trim($pro["CUM"]);
            $productos_finales[$j]["type_item_identification_id"] = 3;

            $productos_finales[$j]["base_quantity"] = $pro["Cantidad"];
        }

        if ($tot_imp > 0) {
            $primero["tax_id"] = 1;
            $primero["tax_amount"] = number_format($tot_imp, 2, ".", "");
            $primero["taxable_amount"] = number_format($base_imp, 2, ".", "");
            $primero["percent"] = "19";

            $impues[] = $primero;
        }

        if ($base_imp2 > 0) {
            $segundo["tax_id"] = 1;
            $segundo["tax_amount"] = number_format($tot_imp2, 2, ".", "");
            $segundo["taxable_amount"] = number_format($base_imp2, 2, ".", "");
            $segundo["percent"] = "0";

            $impues[] = $segundo;
        }

        $healt_sector = null;
        $resultado["tax_totals"] = $impues;
        $resultado["allowance_charges"] = $descue;
        $resultado["invoice_lines"] = $productos_finales;
        $resultado["payment_form"] = $metodo_pago;
        $resultado["healt_sector"] = $healt_sector;
        //var_dump($resultado);
        //exit;
        return ($resultado);
    }

    private function getCUFE()
    {
        $nit = self::getNit();
        $fecha = $this->factura['Fecha_Documento'];
        $neto = number_format($this->totales['Total'] + $this->totales['Total_Iva'], 2, ".", "");
        $variable = $this->factura['Codigo'] . "" . str_replace(" ", "", $fecha) . "-05:00" . number_format($this->totales['Total'], 2, ".", "") . "01" . number_format($this->totales['Total_Iva'], 2, ".", "") . "040.00030.00" . $neto . $nit . $this->cliente['Id_Cliente'] . $this->resolucion['Clave_Tecnica'] . '1';
        return hash('sha384', $variable);
    }

    private function getDatos($tipo_factura, $id_factura, $resolucion_facturacion)
    {

        $oItem = new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
        $this->resolucion = $oItem->getData();
        unset($oItem);

        $oItem = new complex($tipo_factura, "Id_" . $tipo_factura, $id_factura);
        $this->factura = $oItem->getData();
        unset($oItem);

        $query = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->configuracion = $oCon->getData();
        unset($oItem);

        /*  if($tipo_factura=="Factura_Administrativa"){
        if($this->factura['Tipo_Cliente']=='Funcionario'){
        $tipo_id = 'C.Identificacion_Funcionario';
        }else{
        $tipo_id = "C.Id_".$this->factura['Tipo_Cliente'];
        }
        $query="SELECT C.*,(SELECT D.Nombre FROM  Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
        (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad
        FROM ".$this->factura['Tipo_Cliente']." C WHERE " .$tipo_id. " = ".$this->factura['Id_Cliente'];

        }else{

        $query="SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente'];

        }
        $oCon=new consulta();
        $oCon->setQuery($query);
        $this->cliente=$oCon->getData();
        unset($oCon); */

        #CARLOS CARDONA ---------------

        if ($tipo_factura == "Factura_Administrativa") {

            $this->cliente = $this->getTercero();
        } else {

            $this->cliente = $this->getCliente();
        }

        if ($tipo_factura != "Factura_Capita" && $tipo_factura != "Factura_Administrativa") {
            $query = 'SELECT PF.*, P.Codigo_Cum as CUM, IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion," (LAB- ", P.Laboratorio_Comercial,") ", P.Invima, " CUM:", P.Codigo_Cum, " - Lote: ",PF.Lote), CONCAT(P.Nombre_Comercial, " (LAB-", P.Laboratorio_Comercial, ") - Lote: ",PF.Lote)) as Producto
                    FROM Producto_' . $tipo_factura . ' PF
                    INNER JOIN Producto P ON PF.Id_Producto=P.Id_Producto
                    WHERE Id_' . $tipo_factura . '=' . $id_factura
                . '--         GROUP by id_Producto';

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);

            $tip = '';
            $descuento = "Cantidad*Descuento";
            if ($tipo_factura == "Factura_Venta") {
                $tip = '_Venta';
                $descuento = "Cantidad*Descuento*Precio_Venta/100";
            }
            $query = "SELECT
                        IFNULL(SUM(if(Cantidad*Precio$tip = 0, 1, Cantidad*Precio$tip)),0) AS Total, 
                        IFNULL(SUM(if(Cantidad*Precio$tip*(1- Descuento/100 ) = 0, 1, Cantidad*Precio$tip)*(Impuesto/100)*(1- Descuento/100 )   ),0)  AS Total_Iva,
                        IFNULL(SUM(ROUND($descuento)),0) AS Descuento, Impuesto
                        FROM Producto_$tipo_factura WHERE Id_$tipo_factura = $id_factura";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        } elseif ($tipo_factura == "Factura_Capita") {

            $query = 'SELECT PF.*, IFNULL(F.Mes,"") as CUM, PF.Descripcion as Producto
                    FROM Descripcion_' . $tipo_factura . ' PF
                    INNER JOIN ' . $tipo_factura . ' F ON F.Id_' . $tipo_factura . ' = PF.Id_' . $tipo_factura . '
                    WHERE PF.Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);

            $tip = '';

            $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto
                    FROM Descripcion_' . $tipo_factura . '
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        } elseif ($tipo_factura == "Factura_Administrativa") {

            $query = 'SELECT PF.*, PF.Referencia as CUM, PF.Descripcion as Producto
                    FROM Descripcion_' . $tipo_factura . ' PF
                    INNER JOIN ' . $tipo_factura . ' F ON F.Id_' . $tipo_factura . ' = PF.Id_' . $tipo_factura . '
                    WHERE PF.Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);

            $tip = '';

            $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto
                    FROM Descripcion_' . $tipo_factura . '
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        }
    }

    private function getNombre()
    {
        $nit = self::getNit();
        $codigo = (int) str_replace($this->resolucion['Codigo'], "", $this->factura['Codigo']);
        $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
        return $nombre;
    }

    public function getNit()
    {
        $nit = explode("-", $this->configuracion['NIT']);
        $nit = str_replace(".", "", $nit[0]);
        return $nit;
    }

    public function getFecha($tipo)
    {
        $fecha = explode(" ", $this->factura['Fecha_Documento']);

        if ($tipo == 'Fecha') {
            return $fecha[0];
        } elseif ($tipo == 'Hora') {
            return $fecha[1];
        }
    }

    private function getImpuesto()
    {
        $query = 'SELECT * FROM Impuesto WHERE Valor>0 LIMIT 1';
        $oCon = new Consulta();
        $oCon->setQuery($query);
        $iva = $oCon->getData();

        return $iva['Valor'];
    }

    private function GetQr($cufe)
    {
        $fecha = str_replace(":", "", $this->factura['Fecha_Documento']);
        $fecha = str_replace("-", "", $fecha);
        $fecha = str_replace(" ", "", $fecha);

        $qr = "NumFac: " . $this->factura['Codigo'] . "\n";
        $qr .= "FecFac: " . $fecha . "\n";
        $qr .= "NitFac: " . $this->getNit() . "\n";
        $qr .= "DocAdq: " . $this->factura['Id_Cliente'] . "\n";
        $qr .= "ValFac: " . number_format($this->totales['Total'], 2, ".", "") . "\n";
        $qr .= "ValIva: " . number_format($this->totales['Total_Iva'], 2, ".", "") . "\n";
        $qr .= "ValOtroIm: 0.00 \n";
        $qr .= "ValFacIm: " . number_format(($this->totales['Total_Iva'] + $this->totales['Total']), 2, ".", "") . "\n";
        $qr .= "CUFE: " . $cufe . "\n";
        $qr = generarqrFE($qr);

        return ($qr);
    }

    private function GetTercero()
    {
        $cliente = [];
        $query = 'SELECT * FROM Factura_Administrativa WHERE Id_Factura_Administrativa = ' . $this->factura['Id_Factura_Administrativa'];
        $oCon = new consulta();
        $oCon->setQuery($query);

        $facturaAdmin = $oCon->getData();
        unset($oCon);

        $query = '';
        switch ($facturaAdmin['Tipo_Cliente']) {
            case 'Funcionario':
                $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                        CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
                                        Correo AS Correo_Persona_Contacto , Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
                        "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion, Telefono,
                        IFNULL(Id_Municipio,99) AS Id_Municipio , 1 AS Condicion_Pago
                            FROM Funcionario WHERE Identificacion_Funcionario = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Proveedor':
                $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,

                                    (CASE
                                        WHEN Tipo = "Juridico" THEN Razon_Social
                                        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

                                    END) AS Nombre,
                                    Correo AS Correo_Persona_Contacto,
                                        Celular, Tipo, "NIT" AS Tipo_Identificacion,
                                    Digito_Verificacion, Regimen, Direccion ,Telefono,
                    Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                        FROM Proveedor WHERE Id_Proveedor = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Cliente':
                return $this->getCliente();
                break;

            default:

                break;
        }

        $oCon = new consulta();
        $oCon->setQuery($query);

        $cliente = $oCon->getData();
        unset($oCon);

        return $cliente;
    }

    private function getCliente()
    {
        /*   $query="SELECT C.*,
        (SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
        (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad
        FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente']; */
        #correo_persona_contacto

        $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente, Contribuyente, Autorretenedor,
                            (CASE
                                WHEN Tipo = "Juridico" THEN Razon_Social
                                ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

                            END) AS Nombre,
                            Correo_Persona_Contacto,
                            Celular, Tipo, Tipo_Identificacion,
                            Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
                Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                 FROM Cliente WHERE Id_Cliente =' . $this->factura['Id_Cliente'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $cliente = $oCon->getData();

        unset($oCon);
        return $cliente;
    }

}
