<?php
include_once('class.querybasedatos.php');
include_once('helper.contabilizar_nota_credito_global_factura.php');
include_once('helper.contabilizar_nota_credito_global.php');

class Contabilizar
{

	private $queryObj;
	private $id_modulo;
	private $id_registro_modulo;
	private $numero_comprobante;
	private $nit;
	private $tipo_nit;
	private $save_fecha;
	private $datos_funcionario;
	private $centro_costo;
	private $fecha_movimiento;

	// ============================================
	// SISTEMA DE LOGGING
	// ============================================
	private function logError($mensaje, $contexto = [], $nivel = 'ERROR')
	{
		$logDir = dirname(__DIR__) . '/../storage/logs';
		if (!is_dir($logDir)) {
			@mkdir($logDir, 0777, true);
		}
		$logFile = $logDir . '/contabilizar_errors_' . date('Y-m-d') . '.log';
		$timestamp = date('Y-m-d H:i:s');
		$contextoStr = !empty($contexto) ? ' | Contexto: ' . json_encode($contexto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
		$logMessage = "[{$timestamp}] [{$nivel}] {$mensaje}{$contextoStr}" . PHP_EOL;
		@file_put_contents($logFile, $logMessage, FILE_APPEND);
	}

	private function logInfo($mensaje, $contexto = [])
	{
		$this->logError($mensaje, $contexto, 'INFO');
	}

	function __construct($save_fecha = false)
	{

		$this->queryObj = new QueryBaseDatos();
		$this->save_fecha = $save_fecha;
		$this->centro_costo = null;
		$this->tipo_nit = null;
		$this->id_modulo = null;
		$this->id_registro_modulo = null;
		$this->nit = null;
		$this->datos_funcionario = null;
	}

	function __destruct()
	{

		$this->queryObj = null;
		unset($this->queryObj);
	}

	public function CrearMovimientoContable($tipo, $datos, $id_nacionalizacion = null)
	{

		try {
			// Validar parámetros de entrada
			if (empty($tipo)) {
				$this->logError('CrearMovimientoContable: tipo vacío', ['tipo' => $tipo]);
				return false;
			}

			if (!is_array($datos) || empty($datos)) {
				$this->logError('CrearMovimientoContable: datos no es un array o está vacío', ['tipo' => $tipo]);
				return false;
			}

			// Validar campos requeridos según el tipo
			$campos_requeridos = ['Id_Registro', 'Nit'];
			foreach ($campos_requeridos as $campo) {
				if (!isset($datos[$campo]) || $datos[$campo] === null || $datos[$campo] === '') {
					$this->logError("CrearMovimientoContable: campo requerido '{$campo}' no encontrado", [
						'tipo' => $tipo,
						'datos_keys' => array_keys($datos)
					]);
					return false;
				}
			}

			switch ($tipo) {
				case 'Factura':

					$this->GetIdModulo($tipo);
					if (!is_numeric($this->id_modulo)) {
						$this->logError('CrearMovimientoContable: módulo no encontrado para Factura');
						return false;
					}
					$this->id_registro_modulo = isset($datos['Id_Registro']) ? $datos['Id_Registro'] : null;
					$this->nit = isset($datos['Nit']) ? $datos['Nit'] : null;
					if ($this->nit) {
						$this->BuscarTipoNit($this->nit);
					}

					$this->CrearMovimientosFacturaMultitipo($datos);
					break;
				case 'Nota Credito Global':
					if (!isset($datos['Tipo_Factura'])) {
						$this->logError('CrearMovimientoContable: Tipo_Factura no encontrado en datos', ['tipo' => $tipo]);
						return false;
					}

					if ($datos['Tipo_Factura'] == 'Factura') {
						//id_registro_modulo es id nota credito global
						$this->id_registro_modulo = isset($datos['Id_Registro']) ? $datos['Id_Registro'] : null;
						$this->nit = isset($datos['Nit']) ? $datos['Nit'] : null;
						if ($this->nit) {
							$this->BuscarTipoNit($this->nit);
						}
						$this->CrearMovimientosNotaCreditoGlobalFactura($datos);
					} elseif ($datos['Tipo_Factura'] == 'Factura Venta') {
						//modulo de la Factura
						$this->GetIdModulo($datos['Tipo_Factura']);
						if (!is_numeric($this->id_modulo)) {
							$this->logError('CrearMovimientoContable: módulo no encontrado para Factura Venta');
							return false;
						}
						//modulo nota credito
						$this->id_registro_modulo = isset($datos['Id_Registro']) ? $datos['Id_Registro'] : null;
						$this->nit = isset($datos['Nit']) ? $datos['Nit'] : null;
						if ($this->nit) {
							$this->BuscarTipoNit($this->nit);
						}

						$this->CrearMovimientoNCGFacturaVenta($datos);
					} elseif ($datos['Tipo_Factura'] == 'Factura Administrativa') {
						$this->GetIdModulo($tipo);
						if (!is_numeric($this->id_modulo)) {
							$this->logError('CrearMovimientoContable: módulo no encontrado para Nota Credito Global');
							return false;
						}
						$this->id_registro_modulo = isset($datos['Id_Registro']) ? $datos['Id_Registro'] : null;
						$this->nit = isset($datos['Nit']) ? $datos['Nit'] : null;

						$this->CrearMovimientosNCGFacturaAdministrativa($datos);
					} elseif ($datos['Tipo_Factura'] == 'Factura Capita') {
						$this->GetIdModulo($datos['Tipo_Factura']);
						if (!is_numeric($this->id_modulo)) {
							$this->logError('CrearMovimientoContable: módulo no encontrado para Factura Capita');
							return false;
						}

						$this->id_registro_modulo = isset($datos['Id_Registro']) ? $datos['Id_Registro'] : null;
						$this->nit = isset($datos['Nit']) ? $datos['Nit'] : null;
						if ($this->nit) {
							$this->BuscarTipoNit($this->nit);
						}

						$this->CrearMovimientosNCGFacturaCapita($datos);
					}


					break;


				case 'Factura Venta':

					$this->GetIdModulo($tipo);
					if (!is_numeric($this->id_modulo)) {
						$this->logError("Contabilizar: modulo no encontrado para {$tipo}");
						return false;
					}
					$this->id_registro_modulo = isset($datos['Id_Registro']) ? $datos['Id_Registro'] : null;
					$this->nit = isset($datos['Nit']) ? $datos['Nit'] : null;

					if ($this->nit) {
						$this->BuscarTipoNit($this->nit);
					}

					$datos['Nro_Factura'] = $this->GetCodigoFactura($this->id_registro_modulo);

					$this->CrearMovimientosFacturaVenta($datos);
					break;

				case 'Factura Capita':
					$this->GetIdModulo($tipo);

					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosFacturaCapita($datos);
					break;
				case 'Factura Administrativa':
					$this->GetIdModulo($tipo);

					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];


					$this->CrearMovimientosFacturaAdministrativa($datos);
					break;

				case 'Nota Credito':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosNotaCredito($datos);
					break;

				case 'Nota Contable':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);
					break;

				case 'Comprobante Ingreso':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosComprobante($datos);
					break;

				case 'Comprobante Egreso':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosComprobante($datos);
					break;

				case 'Comprobante':
					$this->GetIdModulo($tipo . ' ' . $datos['Tipo_Comprobante']);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosComprobante($datos);
					break;

				case 'Ajuste Individual':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosAjusteIndividual($datos);
					break;

				case 'Inventario Fisico':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientoInventarioFisico($datos);
					break;

				case 'Inventario Fisico Punto':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientoInventarioFisicoPunto($datos);
					break;

				case 'Acta Recepcion':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->numero_comprobante = $datos['Numero_Comprobante'];
					$this->BuscarTipoNit($datos['Nit']);

					$this->CrearMovimientosActa($datos);
					break;

				case 'Devolucion Acta':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];

					$this->CrearMovimientosDevolucionActa($datos);
					break;
				case 'Nomina':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->tipo_nit = 'Funcionario';
					$this->CrearMovimientosNomina($datos);
					break;

				case 'Liquidacion Funcionario':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->nit = $datos['Nit'];
					$this->tipo_nit = 'Funcionario';
					$this->CrearMovimientosLiquidacionFuncionario($datos);
					break;

				case 'Acta Internacional':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->CrearMovimientosActaInternacional($datos);
					break;

				case 'Parcial Acta Internacional':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->CrearMovimientosParcialActaInternacional($datos, $id_nacionalizacion);
					break;

				case 'Activo Fijo':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->CrearMovimientosActivoFijo($datos);
					break;
				case 'Depreciacion':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->CrearMovimientosDepreciacion($datos);
					break;
				case 'Cierre Anio':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->CrearMovimientosCierreAnio($datos);
					break;
				case 'Legalizacion_Gastos':
					$this->GetIdModulo($tipo);
					$this->id_registro_modulo = $datos['Id_Registro'];
					$this->CrearMovimientosLegalizacionGastos($datos);
					break;
				default:
					//ENVIAR NOTIFICACION DE QUE SE ESCOGIO UNA OPCION ERRONEA
					$this->logError('CrearMovimientoContable: tipo no reconocido', ['tipo' => $tipo]);
					return false;
					break;
			}

			return true;
		} catch (Exception $e) {
			$this->logError('CrearMovimientoContable: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'tipo' => $tipo
			]);
			return false;
		}
	}

	/*CREAR MOVIMIENTO*/
	private function CrearMovimientosLegalizacionGastos($datos)
	{

		foreach ($datos['gastos'] as $gasto) {

			$valor = $gasto['Total'];
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $gasto['Id_Plan_Cuenta_Gasto'];
			$oItem->Id_Centro_Costo = $gasto['Id_Centro_Costo'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Debe = round(floatval($valor));
			$oItem->Debe_Niif = round(floatval($valor));
			$oItem->Nit = $gasto['Nit'];
			$oItem->Tipo_Nit = $gasto['Tipo_Nit'];
			$oItem->Documento = $gasto['Documento'];
			$oItem->Detalles = $gasto['Concepto'];
			$oItem->Numero_Comprobante = $datos['Numero_Comprobante'];
			//if ($this->save_fecha)
			//$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'No_Conforme');
			$oItem->save();

			unset($oItem);
		}
		if (isset($datos['cuentas_adicionales'])) {
			foreach ($datos['cuentas_adicionales'] as $gasto) {

				$debito = $gasto['Debito'];
				$credito = $gasto['Credito'];
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $gasto['Cuenta']['Id_Plan_Cuentas'];
				//$oItem->Id_Centro_Costo = $gasto['Id_Centro_Costo'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = round(floatval($credito));
				$oItem->Haber_Niif = round(floatval($credito));
				$oItem->Debe = round(floatval($debito));
				$oItem->Debe_Niif = round(floatval($credito));
				$oItem->Nit = $gasto['Nit'];
				$oItem->Tipo_Nit = $gasto['Tipo_Nit'];
				$oItem->Documento = $gasto['Documento'];
				$oItem->Detalles = $gasto['Concepto'];
				$oItem->Numero_Comprobante = $datos['Numero_Comprobante'];
				//if ($this->save_fecha)
				//$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'No_Conforme');
				$oItem->save();

				unset($oItem);
			}
		}



		$credito = $datos['total_gasto'];
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = 74;
		//$oItem->Id_Centro_Costo = $gasto['Id_Centro_Costo'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Haber = round(floatval($credito));
		$oItem->Haber_Niif = round(floatval($credito));
		$oItem->Debe = 0;
		$oItem->Debe_Niif = 0;
		$oItem->Nit = $datos['nit_responsable'];
		$oItem->Tipo_Nit = $datos['tipo_nit_responsable'];
		$oItem->Documento = $datos['anticipo'];
		$oItem->Detalles = $datos['anticipo'];
		$oItem->Numero_Comprobante = $datos['Numero_Comprobante'];
		//if ($this->save_fecha)
		//$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'No_Conforme');
		$oItem->save();
	}


	private function CrearMovimientosFacturaMultitipo($datos)
	{


		$facturaObj = $this->GetTipoFactura($datos['Id_Registro']);
		$tipo_servicios = $this->GetTipoServicios();
		$cuota_moderadora = $facturaObj['Cuota'];

		$tipo_factura = 'Factura Evento';
		$tipo_factura_nopos = $facturaObj['Tipo'];
		if (strtolower($facturaObj['Tipo_Dispensacion']) != 'evento'  && strtolower($facturaObj['Tipo_Dispensacion']) != 'positiva'  && strtolower($facturaObj['Tipo_Dispensacion']) != 'cohortes'  && strtolower($facturaObj['Tipo_Dispensacion']) != 'axa colpatria') {

			//	if (strtolower($facturaObj['Tipo_Dispensacion']) != 'evento' && strtolower($facturaObj['Tipo_Dispensacion']) != 'cohortes') {

			$tipo_factura = 'Factura ' . $this->GetTipoServicio($facturaObj['Id_Tipo_Servicio']);
		}

		$datos_dis['Id_Regimen'] = $datos['Id_Regimen'];
		$datos_dis['Id_Dispensacion'] = $facturaObj['Id_Dispensacion'];
		$datos_dis['Id_Punto_Dispensacion'] = $datos['Id_Punto_Dispensacion'];

		$this->GetIdModulo($tipo_factura);

		$this->GuardarMovimientoFacturaMultitipo($facturaObj['Codigo'], $datos['Id_Registro'], $tipo_factura_nopos, $cuota_moderadora, $datos_dis);
	}


	private function CrearMovimientosNotaCreditoGlobalFactura($datos)
	{


		$facturaObj = GetTipoFacturaNotaCreditoGlobal($datos['Id_Registro']);
		$tipo_servicios = $this->GetTipoServicios();
		$cuota_moderadora = $facturaObj['Cuota'];


		$tipo_factura = 'Factura Evento';
		$tipo_factura_nopos = $facturaObj['Tipo'];

		if (strtolower($facturaObj['Tipo_Dispensacion']) != 'evento'  && strtolower($facturaObj['Tipo_Dispensacion']) != 'positiva' && strtolower($facturaObj['Tipo_Dispensacion']) != 'cohortes' && strtolower($facturaObj['Tipo_Dispensacion']) != 'axa colpatria') {

			$tipo_factura = 'Factura ' . $this->GetTipoServicio($facturaObj['Id_Tipo_Servicio']);
		}



		$datos_dis['Id_Regimen'] = $facturaObj['Id_Regimen'];
		$datos_dis['Id_Dispensacion'] = $facturaObj['Id_Dispensacion'];
		$datos_dis['Id_Punto_Dispensacion'] = $facturaObj['Id_Punto_Dispensacion'];

		//la variable id_modulo representa EL ID DELTIPO DE FACTURA asociada a la nota (NO ES EL MODULO ID_NOTA)
		$this->GetIdModulo($tipo_factura);


		$this->GuardarMovimientoNotaCreditoGlobalFactura($datos['Id_Registro'], $tipo_factura_nopos, $cuota_moderadora, $datos_dis);
	}


	private function getCuotaReal($id_dispensacion)
	{
		$query = "SELECT Cuota FROM Dispensacion WHERE Id_Dispensacion = $id_dispensacion";

		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');

		return $datos['Cuota'];
	}

	private function CrearMovimientosFacturaVenta($datos)
	{
		try {
			if (!isset($datos['Productos']) || !is_array($datos['Productos'])) {
				$this->logError('CrearMovimientosFacturaVenta: Productos no encontrado o no es array', ['datos_keys' => array_keys($datos)]);
				return;
			}

			$productos = $datos['Productos'];
			if (!isset($datos['Nro_Factura'])) {
				$this->logError('CrearMovimientosFacturaVenta: Nro_Factura no encontrado');
				return;
			}

			$this->GuardarMovimientoFacturaVenta($productos, $datos['Nro_Factura']);
			$this->TotalIvaProductos($productos, 'factura venta', $datos['Nro_Factura']);
		} catch (Exception $e) {
			$this->logError('CrearMovimientosFacturaVenta: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}

	private function CrearMovimientoNCGFacturaVenta($datos)
	{
		try {
			if (!isset($datos['Id_Registro'])) {
				$this->logError('CrearMovimientoNCGFacturaVenta: Id_Registro no encontrado');
				return;
			}

			$datos['Nota'] = getNotaCreditoGlobal($datos['Id_Registro']);
			if (!is_array($datos['Nota']) || empty($datos['Nota'])) {
				$this->logError('CrearMovimientoNCGFacturaVenta: Nota no encontrada', ['Id_Registro' => $datos['Id_Registro']]);
				return;
			}

			$productos = getProductosFacturaVentaNCG($datos['Id_Registro'], 'Factura Venta');
			if (!is_array($productos) || empty($productos)) {
				$this->logError('CrearMovimientoNCGFacturaVenta: productos no encontrados', ['Id_Registro' => $datos['Id_Registro']]);
				return;
			}

			if (!isset($datos['Nota']['Codigo'])) {
				$this->logError('CrearMovimientoNCGFacturaVenta: Codigo no encontrado en Nota');
				return;
			}

			$this->GuardarMovimientoNCGFacturaVenta($productos, $datos['Nota']['Codigo']);
		} catch (Exception $e) {
			$this->logError('CrearMovimientoNCGFacturaVenta: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}

	private function CrearMovimientosFacturaCapita($datos)
	{
		try {
			if (!isset($datos['Id_Registro'])) {
				$this->logError('CrearMovimientosFacturaCapita: Id_Registro no encontrado');
				return;
			}

			$facturaObj = $this->GetFacturaCapita($datos['Id_Registro']);
			if (!is_array($facturaObj) || !isset($facturaObj['Codigo'])) {
				$this->logError('CrearMovimientosFacturaCapita: factura no encontrada', ['Id_Registro' => $datos['Id_Registro']]);
				return;
			}

			$subtotal = isset($datos['Subtotal']) ? $datos['Subtotal'] : 0;
			$cuota = isset($datos['Cuota']) ? $datos['Cuota'] : 0;

			$this->GuardarMovimientoFacturaCapita($facturaObj['Codigo'], $datos['Id_Registro'], $subtotal, $cuota);
		} catch (Exception $e) {
			$this->logError('CrearMovimientosFacturaCapita: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}

	private function CrearMovimientosNCGFacturaCapita($datos)
	{
		try {
			if (!isset($datos['Id_Registro'])) {
				$this->logError('CrearMovimientosNCGFacturaCapita: Id_Registro no encontrado');
				return;
			}

			$nota = getNotaCreditoGlobal($datos['Id_Registro']);
			if (!is_array($nota) || empty($nota)) {
				$this->logError('CrearMovimientosNCGFacturaCapita: nota no encontrada', ['Id_Registro' => $datos['Id_Registro']]);
				return;
			}

			$subtotal = isset($datos['Subtotal']) ? $datos['Subtotal'] : 0;
			$cuota = isset($datos['Cuota']) ? $datos['Cuota'] : 0;

			$this->GuardarMovimientoNCGFacturaCapita($nota, $datos['Id_Registro'], $subtotal, $cuota);
		} catch (Exception $e) {
			$this->logError('CrearMovimientosNCGFacturaCapita: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}


	private function CrearMovimientosFacturaAdministrativa($datos)
	{
		try {
			if (!isset($datos['Id_Registro'])) {
				$this->logError('CrearMovimientosFacturaAdministrativa: Id_Registro no encontrado');
				return;
			}

			$facturaObj = $this->GetFacturaAdministrativa($datos['Id_Registro']);
			if (!is_array($facturaObj)) {
				$this->logError('CrearMovimientosFacturaAdministrativa: factura no encontrada', ['Id_Registro' => $datos['Id_Registro']]);
				return;
			}

			$this->centro_costo = isset($facturaObj['Id_Centro_Costo']) ? $facturaObj['Id_Centro_Costo'] : null;
			$this->tipo_nit = isset($facturaObj['Tipo_Cliente']) ? $facturaObj['Tipo_Cliente'] : null;

			$codigo = isset($facturaObj['Codigo']) ? $facturaObj['Codigo'] : '';
			$activos_fijos = isset($facturaObj['Activos_Fijos']) ? $facturaObj['Activos_Fijos'] : '';
			$fecha = isset($facturaObj['Fecha_Sola']) ? $facturaObj['Fecha_Sola'] : date('Y-m-d');

			$this->GuardarMovimientoFacturaAdministrativa($codigo, $activos_fijos, $datos['Id_Registro'], $fecha);
		} catch (Exception $e) {
			$this->logError('CrearMovimientosFacturaAdministrativa: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}


	private function CrearMovimientosNCGFacturaAdministrativa($datos)
	{
		try {
			if (!isset($datos['Id_Registro'])) {
				$this->logError('CrearMovimientosNCGFacturaAdministrativa: Id_Registro no encontrado');
				return;
			}

			$nota = getNotaCreditoGlobal($datos['Id_Registro']);
			if (!is_array($nota) || !isset($nota['Id_Factura'])) {
				$this->logError('CrearMovimientosNCGFacturaAdministrativa: nota no encontrada o sin Id_Factura', ['Id_Registro' => $datos['Id_Registro']]);
				return;
			}

			$facturaObj = $this->GetFacturaAdministrativa($nota['Id_Factura']);
			if (!is_array($facturaObj)) {
				$this->logError('CrearMovimientosNCGFacturaAdministrativa: factura no encontrada', ['Id_Factura' => $nota['Id_Factura']]);
				return;
			}

			$this->centro_costo = isset($facturaObj['Id_Centro_Costo']) ? $facturaObj['Id_Centro_Costo'] : null;
			$this->tipo_nit = isset($facturaObj['Tipo_Cliente']) ? $facturaObj['Tipo_Cliente'] : null;

			$fecha = isset($nota['Fecha']) ? $nota['Fecha'] : date('Y-m-d');

			$this->GuardarMovimientoNCGFacturaAdministrativa($nota, $fecha);
		} catch (Exception $e) {
			$this->logError('CrearMovimientosNCGFacturaAdministrativa: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}


	private function CrearMovimientosDevolucionActa($datos)
	{

		$costos = $this->calcularCostosProductosDevolucion($datos['productos']);
		$totales_iva = $this->calcularTotalIva($datos['productos']);
		$totales_factura = $this->calcularTotalFactura($datos['productos']);
		$totales_retenciones = $this->calcularRetencionesActa($datos['productos'], $datos['datos']['acta']);
		$totales_neto_factura = $this->calcularNetoFactura($totales_factura, $totales_retenciones, $totales_iva);
		$perdidas = $this->calcularPerdidasProductos($datos['productos'], []);
		$retenciones_perdidas = $this->calcularRetencionesActaPerdidas($datos['productos'], $datos['datos']['acta']);
		$neto_perdidas = $this->calcularNetoFactura($perdidas, $retenciones_perdidas, []);
		$cuenta_prpal = $this->BuscarInformacionParaMovimiento('principal', 'facturas');

		foreach ($totales_neto_factura as $factura => $valor) {
			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $cuenta_prpal['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Debe = round(floatval($valor));
			$oItem->Debe_Niif = round(floatval($valor));
			$oItem->Nit = $datos['datos']['Id_Proveedor'];
			$oItem->Tipo_Nit = 'Proveedor';
			$oItem->Documento = $factura;
			$oItem->Numero_Comprobante = $datos['datos']['Codigo'];
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Devolucion_Compra');
			$oItem->save();
			unset($oItem);
		}

		foreach ($totales_retenciones as $factura => $retenciones) {
			foreach ($retenciones as $id_plan => $ret) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $id_plan;
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Debe = round(floatval($ret));
				$oItem->Debe_Niif = round(floatval($ret));
				$oItem->Nit = $datos['datos']['Id_Proveedor'];
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $factura;
				$oItem->Numero_Comprobante = $datos['datos']['Codigo'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Devolucion_Compra');
				$oItem->save();
				unset($oItem);
			}
		}

		foreach ($costos as $factura => $totales_costos) {
			foreach ($totales_costos as $tipo => $valor) {
				if ($valor > 0) {
					$cuenta = $this->BuscarInformacionParaMovimiento($tipo);
					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Haber = round(floatval($valor));
					$oItem->Haber_Niif = round(floatval($valor));
					$oItem->Nit = $datos['datos']['Id_Proveedor'];
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $factura;
					$oItem->Numero_Comprobante = $datos['datos']['Codigo'];
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Devolucion_Compra');
					$oItem->save();
					unset($oItem);
				}
			}
		}

		$cuenta = $this->BuscarInformacionParaMovimiento('iva 19');
		foreach ($totales_iva as $facturas => $valor) {
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = round(floatval($valor));
			$oItem->Haber_Niif = round(floatval($valor));
			$oItem->Nit = $datos['datos']['Id_Proveedor'];
			$oItem->Tipo_Nit = 'Proveedor';
			$oItem->Documento = $factura;
			$oItem->Numero_Comprobante = $datos['datos']['Codigo'];
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Devolucion_Compra');
			$oItem->save();
			unset($oItem);
		}

		foreach ($neto_perdidas as $factura => $valor) {
			if ($valor > 0) {
				$cuenta = $this->BuscarInformacionParaMovimiento("perdidas en inventario");
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = round(floatval($valor));
				$oItem->Debe_Niif = round(floatval($valor));
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Nit = $datos['datos']['Id_Proveedor'];
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $factura;
				$oItem->Detalles = "Perdidas en inventario Fisico";
				$oItem->Numero_Comprobante = $datos['datos']['Codigo'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Devolucion_Compra');
				$oItem->save();
				unset($oItem);
			}
		}
		foreach ($retenciones_perdidas as $factura => $retenciones) {
			foreach ($retenciones as $id_plan => $ret) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $id_plan;
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Debe = round(floatval($ret));
				$oItem->Debe_Niif = round(floatval($ret));
				$oItem->Nit = $datos['datos']['Id_Proveedor'];
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $factura;
				$oItem->Numero_Comprobante = $datos['datos']['Codigo'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Devolucion_Compra');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function calcularCostosProductos($productos)
	{
		$totales = [];

		foreach ($productos as $i => $value) {
			if (!array_key_exists($value['Factura'], $totales)) {
				$totales[$value['Factura']] = [
					"gravado 19" => 0,
					"gravado 0" => 0
				];
			}
			if ($value['Impuesto'] != 0) {
				$totales[$value['Factura']]['gravado 19'] += $value['Subtotal'];
			} else {
				$totales[$value['Factura']]['gravado 0'] += $value['Subtotal'];
			}
		}

		return $totales;
	}
	private function calcularCostosProductosDevolucion($productos)
	{
		$totales = [];

		foreach ($productos as $i => $value) {
			if (!array_key_exists($value['Factura'], $totales)) {
				$totales[$value['Factura']] = [
					"gravado 19" => 0,
					"gravado 0" => 0
				];
			}
			if ($value['Impuesto'] != 0) {
				$totales[$value['Factura']]['gravado 19'] += $value['Cantidad'] * $value['Costo'] * (1 + $value['Impuesto'] / 100);
			} else {
				$totales[$value['Factura']]['gravado 0'] +=  $value['Cantidad'] * $value['Costo'] * (1 + $value['Impuesto'] / 100);
			}
		}

		return $totales;
	}
	private function calcularPerdidasProductos($productos, $retenciones)
	{
		$totales = [];

		foreach ($productos as $i => $value) {
			if (!array_key_exists($value['Factura'], $totales)) {
				$totales[$value['Factura']] = 0;
			}
			$totales[$value['Factura']] += ($value['Cantidad']) * ($value['Costo'] - $value['Costo_Reconocido']);
		}

		return ($totales);
	}

	private function calcularTotalFactura($productos)
	{
		$totales = [];

		foreach ($productos as $i => $value) {
			if (!array_key_exists($value['Factura'], $totales)) {
				$totales[$value['Factura']] = $value['Subtotal'];
			} else {
				$totales[$value['Factura']] += $value['Subtotal'];
			}
		}

		return $totales;
	}

	private function calcularNetoFactura($totales_facturas, $totales_retenciones, $totales_iva)
	{

		foreach ($totales_facturas as $value) {
			foreach ($totales_iva as $factura => $iva) {
				if (isset($totales_facturas[$factura])) {
					$totales_facturas[$factura] += $iva;
				}
			}
		}

		foreach ($totales_facturas as $value) {
			foreach ($totales_retenciones as $factura => $retenciones) {
				foreach ($retenciones as $id_plan => $valor) {
					if (isset($totales_facturas[$factura])) {
						$totales_facturas[$factura] -= $valor;
					}
				}
			}
		}

		return $totales_facturas;
	}

	private function calcularTotalIva($productos)
	{
		$totales = [];

		foreach ($productos as $i => $value) {
			if ($value['Impuesto'] != 0) {
				if (!array_key_exists($value['Factura'], $totales)) {
					$totales[$value['Factura']] = $value['Subtotal'] * ($value['Impuesto'] / 100);
				} else {
					$totales[$value['Factura']] += $value['Subtotal'] * ($value['Impuesto'] / 100);
				}
			}
		}

		return $totales;
	}

	private function calcularRetencionesActaPerdidas($productos, $id_acta)
	{
		$query = "SELECT R.Id_Plan_Cuenta, R.Porcentaje, (SELECT Factura FROM Factura_Acta_Recepcion WHERE Id_Factura_Acta_Recepcion = F.Id_Factura) AS Factura FROM Factura_Acta_Recepcion_Retencion F INNER JOIN Retencion R ON R.Id_Retencion = F.Id_Retencion WHERE F.Id_Acta_Recepcion = $id_acta";

		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		$retenciones = $oCon->getData();
		unset($oCon);

		$totales = [];
		$cuenta_prpal = $this->BuscarInformacionParaMovimiento('impuestos asumidos');

		if (count($retenciones) > 0) {
			foreach ($retenciones as $i => $value) {
				foreach ($productos as $j => $prod) {
					if ($value['Factura'] == $prod['Factura']) {
						$valor = $prod['Cantidad'] * ($prod['Costo'] - $prod['Costo_Reconocido']) * ($value['Porcentaje'] / 100);
						// echo (" $prod[Cantidad] * ( $prod[Costo] - $prod[Costo_Reconocido]) * ($value[Porcentaje]/100); "); exit;


						if (!array_key_exists($value['Factura'], $totales)) {
							// $totales[$value['Factura']] = [];
							$totales[$value['Factura']][$cuenta_prpal['Id_Plan_Cuenta']] = $valor;
						} else {
							if (!array_key_exists($cuenta_prpal['Id_Plan_Cuenta'], $totales[$value['Factura']])) {
								$totales[$value['Factura']][$cuenta_prpal['Id_Plan_Cuenta']] = $valor;
							} else {
								$totales[$value['Factura']][$cuenta_prpal['Id_Plan_Cuenta']] += $valor;
							}
						}
					}
				}
			}
		}
		return $totales;
	}
	private function calcularRetencionesActa($productos, $id_acta)
	{
		$query = "SELECT R.Id_Plan_Cuenta, R.Porcentaje, (SELECT Factura FROM Factura_Acta_Recepcion WHERE Id_Factura_Acta_Recepcion = F.Id_Factura) AS Factura FROM Factura_Acta_Recepcion_Retencion F INNER JOIN Retencion R ON R.Id_Retencion = F.Id_Retencion WHERE F.Id_Acta_Recepcion = $id_acta";

		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		$retenciones = $oCon->getData();
		unset($oCon);

		$totales = [];

		if (count($retenciones) > 0) {
			foreach ($retenciones as $i => $value) {
				foreach ($productos as $j => $prod) {
					if ($value['Factura'] == $prod['Factura']) {
						$valor = $prod['Subtotal'] * ($value['Porcentaje'] / 100);
						if (!array_key_exists($value['Factura'], $totales)) {
							$totales[$value['Factura']] = [];
							$totales[$value['Factura']][$value['Id_Plan_Cuenta']] = $valor;
						} else {
							if (!array_key_exists($value['Id_Plan_Cuenta'], $totales[$value['Factura']])) {
								$totales[$value['Factura']][$value['Id_Plan_Cuenta']] = $valor;
							} else {
								$totales[$value['Factura']][$value['Id_Plan_Cuenta']] += $valor;
							}
						}
					}
				}
			}
		}

		return $totales;
	}

	private function CrearMovimientosActa($datos)
	{

		$productos = $datos['Productos'];
		$facturas = $datos['Facturas'];


		$this->GuardarRetencionesFacturas($facturas, $productos, 'acta');
		$this->TotalIvaProductos($productos, 'acta');
		$this->Ajuste_Peso('acta');
	}

	private function CrearMovimientosNotaCredito($datos)
	{

		$productos = $datos['Productos'];
		$codigo_factura = $this->GetCodigoNotaCredito($datos['Id_Registro']);
		$this->fecha_movimiento = $datos['Fecha'];
		$this->GuardarMovimientoNotaCredito($productos, $codigo_factura);
	}

	private function CrearMovimientosComprobante($datos)
	{

		$codigo_comprobante = $this->GetCodigoComprobante($datos['Id_Registro']);

		if ($datos['Tipo_Comprobante'] == 'Ingreso') {

			$this->GuardarMovimientoComprobanteIngreso($datos, $codigo_comprobante);
		} else {

			$this->GuardarMovimientoComprobanteEgreso($datos, $codigo_comprobante);
		}
	}

	private function CrearMovimientosAjusteIndividual($datos)
	{

		$codigo_ajuste = $this->GetCodigoAjuste($datos['Id_Registro']);

		$clase_ajuste = $this->getDescripcionClaseAjuste($datos['Clase_Ajuste']);

		$this->GuardarMovimientoAjusteIndividual($datos['Productos'], $codigo_ajuste, $datos['Tipo'], $clase_ajuste);
	}

	private function getDescripcionClaseAjuste($id)
	{
		$query = "SELECT Descripcion FROM Clase_Ajuste_Individual WHERE Id_Clase_Ajuste_Individual = $id";

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result['Descripcion'];
	}

	private function CrearMovimientoInventarioFisico($datos)
	{
		$codigo_inventario = 'INVF' . $datos['Id_Registro'];
		$this->GuardarMovimientoInventarioFisico($datos['Productos'], $codigo_inventario);
	}

	private function CrearMovimientoInventarioFisicoPunto($datos)
	{
		$codigo_inventario = 'INVFP' . $datos['Id_Registro'];
		$this->GuardarMovimientoInventarioFisicoPunto($datos['Productos'], $codigo_inventario, $datos['Con_Inventario']);
	}

	/*FIN CREAR MOVIMIENTO*/

	/*ACTAS RECEPCION*/
	private function GuardarRetencionesFacturas($facturas, $productos, $tipo)
	{

		switch ($tipo) {
			case 'acta':

				$totales_facturas = $this->GetTotalesFacturasActa($productos, $facturas);
				$asociacion_ppal = $this->BuscarInformacionParaMovimiento('principal');
				$info_acta_recepcion = $this->infoActaRecepcion($this->id_registro_modulo);
				$tipo_centro_costo = $info_acta_recepcion['Tipo'] == 'Bodega' ? 'Principal' : 'Punto_Dispensacion';
				$this->centro_costo = $this->getIdCentroCostoByTipo($tipo_centro_costo, $info_acta_recepcion['Id']);

				foreach ($totales_facturas['Total_Facturas'] as $fact => $total) {
					//GUARDAR EL MOVIMIENTO CONTABLE
					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion_ppal['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Haber = round(floatval($total));
					$oItem->Haber_Niif = round(floatval($total));
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = $fact;
					$oItem->Numero_Comprobante = $this->numero_comprobante;
					if ($this->isEnableCentroCostoByPUC($asociacion_ppal['Id_Plan_Cuenta']))
						$oItem->Id_Centro_Costo = $this->centro_costo;

					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion');
					$oItem->save();
					unset($oItem);
				}

				$asociacion_facturas_19 = $this->BuscarInformacionParaMovimiento('iva 19');

				foreach ($totales_facturas['Facturas_19'] as $fact => $iva_total) {

					if ($iva_total > 0) {

						//GUARDAR EL MOVIMIENTO CONTABLE
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $asociacion_facturas_19['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = round(floatval($iva_total));
						$oItem->Debe_Niif = round(floatval($iva_total));
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $fact;
						$oItem->Numero_Comprobante = $this->numero_comprobante;
						if ($this->isEnableCentroCostoByPUC($asociacion_facturas_19['Id_Plan_Cuenta']))
							$oItem->Id_Centro_Costo = $this->centro_costo;

						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion');
						$oItem->save();
						unset($oItem);
					}
				}


				foreach ($facturas as $factura) {
					foreach ($factura['Retenciones'] as $rt) {
						if ($rt['Valor'] > 0) {
							if ($rt['Tipo'] == 'Renta') {

								$asociacion = $this->BuscarInformacionParaMovimiento('rete fuente ' . number_format($rt['Porcentaje'], 1, ".", ""), 'facturas');

								//GUARDAR EL MOVIMIENTO CONTABLE
								$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
								$oItem->Id_Plan_Cuenta = isset($rt['Id_Plan_Cuenta']) ? $rt['Id_Plan_Cuenta'] : $asociacion['Id_Plan_Cuenta'];
								$oItem->Id_Modulo = $this->id_modulo;
								$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
								$oItem->Debe = "0";
								$oItem->Debe_Niif = "0";
								$oItem->Haber = round($rt['Valor']);
								$oItem->Haber_Niif = round($rt['Valor']);
								$oItem->Nit = $this->nit;
								$oItem->Tipo_Nit = $this->tipo_nit;
								$oItem->Documento = $factura['Factura'];
								$oItem->Numero_Comprobante = $this->numero_comprobante;
								$cuenta = isset($rt['Id_Plan_Cuenta']) ? $rt['Id_Plan_Cuenta'] : $asociacion['Id_Plan_Cuenta'];
								if ($this->isEnableCentroCostoByPUC($cuenta))
									$oItem->Id_Centro_Costo = $this->centro_costo;

								if ($this->save_fecha)
									$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion');
								$oItem->save();
								unset($oItem);
							} elseif ($rt['Tipo'] == 'Iva') {

								$asociacion = $this->BuscarInformacionParaMovimiento('rete iva', 'facturas');

								//GUARDAR EL MOVIMIENTO CONTABLE
								$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
								$oItem->Id_Plan_Cuenta = isset($rt['Id_Plan_Cuenta']) ? $rt['Id_Plan_Cuenta'] : $asociacion['Id_Plan_Cuenta'];
								$oItem->Id_Modulo = $this->id_modulo;
								$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
								$oItem->Debe = "0";
								$oItem->Debe_Niif = "0";
								$oItem->Haber = round(floatval($rt['Valor']));
								$oItem->Haber_Niif = round(floatval($rt['Valor']));
								$oItem->Nit = $this->nit;
								$oItem->Tipo_Nit = $this->tipo_nit;
								$oItem->Documento = $factura['Factura'];
								$oItem->Numero_Comprobante = $this->numero_comprobante;
								$cuenta = isset($rt['Id_Plan_Cuenta']) ? $rt['Id_Plan_Cuenta'] : $asociacion['Id_Plan_Cuenta'];
								if ($this->isEnableCentroCostoByPUC($cuenta))
									$oItem->Id_Centro_Costo = $this->centro_costo;

								if ($this->save_fecha)
									$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion');
								$oItem->save();
								unset($oItem);
							} elseif ($rt['Tipo'] == 'Ica') {

								$asociacion = $this->BuscarInformacionParaMovimiento('rete ica compras', 'facturas');

								//GUARDAR EL MOVIMIENTO CONTABLE
								$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
								$oItem->Id_Plan_Cuenta = isset($rt['Id_Plan_Cuenta']) ? $rt['Id_Plan_Cuenta'] : $asociacion['Id_Plan_Cuenta'];
								$oItem->Id_Modulo = $this->id_modulo;
								$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
								$oItem->Debe = "0";
								$oItem->Debe_Niif = "0";
								$oItem->Haber = round(floatval($rt['Valor']));
								$oItem->Haber_Niif = round(floatval($rt['Valor']));
								$oItem->Nit = $this->nit;
								$oItem->Tipo_Nit = $this->tipo_nit;
								$oItem->Documento = $factura['Factura'];
								$oItem->Numero_Comprobante = $this->numero_comprobante;
								$cuenta = isset($rt['Id_Plan_Cuenta']) ? $rt['Id_Plan_Cuenta'] : $asociacion['Id_Plan_Cuenta'];

								if ($this->isEnableCentroCostoByPUC($cuenta))
									$oItem->Id_Centro_Costo = $this->centro_costo;

								if ($this->save_fecha)
									$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion');
								$oItem->save();
								unset($oItem);
							}
						}
					}
				}



				break;

			default:
				# code...
				break;
		}
	}

	private function Ajuste_Peso($tipo)
	{

		$query = "SELECT MC.Numero_Comprobante, MC.Fecha_Movimiento, MC.Id_Modulo, MC.Id_Registro_Modulo, SUM(MC.Debe) AS Debe, 
			SUM(MC.Haber) AS Haber, SUM(MC.Debe_Niif) AS Debe_Niif, SUM(MC.Haber_Niif) AS Haber_Niif, (SUM(MC.Debe) - SUM(MC.Haber)) AS Diferencia_PCGA, 
			(SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif)) AS Diferencia_NIIF 
			FROM Movimiento_Contable  MC 
			INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta 
			WHERE MC.Numero_Comprobante LIKE '%" . $this->numero_comprobante . "%'
			GROUP BY MC.Numero_Comprobante HAVING (Debe != Haber OR Debe_Niif != Haber_Niif)";

		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		$movimientos = $oCon->getData();
		unset($oCon);

		foreach ($movimientos as $i => $value) {
			$debe = 0;
			$haber = 0;
			$haber_niif = 0;
			$debe_niif = 0;

			if ($value['Diferencia_PCGA'] > 0) {
				$haber = number_format(abs($value['Diferencia_PCGA']), 2, ".", "");
				$debe = 0;
			} elseif ($value['Diferencia_PCGA'] < 0) {
				$debe = number_format(abs($value['Diferencia_PCGA']), 2, ".", "");
				$haber = 0;
			}
			if ($value['Diferencia_NIIF'] > 0) {
				$haber_niif = number_format(abs($value['Diferencia_NIIF']), 2, ".", "");
				$debe_niif = 0;
			} elseif ($value['Diferencia_NIIF'] < 0) {
				$debe_niif = number_format(abs($value['Diferencia_NIIF']), 2, ".", "");
				$haber_niif = 0;
			}

			if (($debe > 0 && $debe <= 500) || ($debe_niif > 0 && $debe_niif <= 500)) {
				$valuesInsert[] = "(NULL,656,'$value[Fecha_Movimiento]',$value[Id_Modulo],$value[Id_Registro_Modulo],$debe,$haber,$debe_niif,$haber_niif,804016084,'Cliente','Activo','$value[Numero_Comprobante]','AJUSTE AL PESO POR DESCUADRE',NOW(),NULL,'No','$value[Numero_Comprobante]')";
			}

			if (($haber > 0 && $haber <= 500) || ($haber_niif > 0 && $haber_niif <= 500)) {
				$valuesInsert[] = "(NULL,390,'$value[Fecha_Movimiento]',$value[Id_Modulo],$value[Id_Registro_Modulo],$debe,$haber,$debe_niif,$haber_niif,804016084,'Cliente','Activo','$value[Numero_Comprobante]','AJUSTE AL PESO POR DESCUADRE',NOW(),NULL,'No','$value[Numero_Comprobante]')";
			}
		}

		if (count($valuesInsert) > 0) {


			$queryInsert = "INSERT INTO Movimiento_Contable VALUES " . implode(',', $valuesInsert);
			$oCon = new consulta();
			$oCon->setQuery($queryInsert);
			$oCon->createData();
			unset($oCon);
		}
	}

	private function infoActaRecepcion($id_acta)
	{
		$query = "SELECT * FROM Acta_Recepcion WHERE Id_Acta_Recepcion = $id_acta";

		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');

		$response = [
			"Tipo" => $datos['Id_Bodega_Nuevo'] != 0 ? 'Bodega' : 'Punto_Dispensacion',
			"Id" => $datos['Id_Bodega_Nuevo'] != 0 ? null : $datos['Id_Punto_Dispensacion']
		];

		return $response;
	}

	private function GetTotalesFacturasActa($data, $facturas_ret)
	{
		$t_facturas = array();
		$ivas_facturas = array();
		$total_general_facturas = 0;
		$facturas_19 = array('Total' => 0, 'Facturas' => '');
		$facturas = '';
		$retenciones = 0;
		$result = array();


		foreach ($data as $value) {

			foreach ($value['producto'] as $v) {

				if ($v['Factura'] != '') {

					$nro_factura = trim($v['Factura']);

					$total_general_facturas += (floatval($v['Cantidad']) * floatval($v['Precio']));

					if (!$t_facturas[$nro_factura]) {

						$t_facturas[$nro_factura] = (floatval($v['Cantidad']) * floatval($v['Precio']));
					} else {

						$t_facturas[$nro_factura] += (floatval($v['Cantidad']) * floatval($v['Precio']));
					}

					if (!$ivas_facturas[$nro_factura]) {

						$ivas_facturas[$nro_factura] = floatval($v['Iva']);
						$t_facturas[$nro_factura] += floatval($v['Iva']);
					} else {

						$ivas_facturas[$nro_factura] += floatval($v['Iva']);
						$t_facturas[$nro_factura] += floatval($v['Iva']);
					}
				}
			}
		}

		foreach ($facturas_ret as $factura) {

			foreach ($factura['Retenciones'] as $rt) {

				$nro_factura = trim($factura['Factura']);

				if (array_key_exists($nro_factura, $t_facturas)) {

					/* if ($rt['Tipo'] == 'Renta' || $rt['Tipo'] == 'Ica') {
							
							$r = floatval($rt['Valor']);
							$t_facturas[$nro_factura] -= $r;
							$retenciones += $r;							

						}elseif($rt['Tipo'] == 'Iva'){

							$r = floatval($rt['Valor']);
							$t_facturas[$nro_factura] -= $r;
							$retenciones += $r;

						} */

					$r = floatval($rt['Valor']);
					$t_facturas[$nro_factura] -= $r;
					$retenciones += $r;
				}

				$retenciones += $r;
			}
		}

		// var_dump($retenciones);
		// exit;

		$result['Total_General'] = ($total_general_facturas + $facturas_19['Total']) - $retenciones;
		$result['Total_Facturas'] = $t_facturas;
		$result['Facturas_19'] = $ivas_facturas;

		return $result;
	}

	/*FIN ACTAS RECEPCION*/

	/*DEVOLUCION ACTAS*/

	private function GetMovimientosActa($idActa)
	{
		$modulo_acta = $this->GetIdModulo('acta recepcion');

		$query = '
				SELECT
					*
				FROM Movimiento_Contable
				WHERE
					Id_Registro_Modulo = ' . $idActa . ' AND Id_Modulo = ' . $modulo_acta;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('multiple');

		return $result;
	}

	/*FIN DEVOLUCION ACTAS*/

	/*FACTURAS VENTAS*/

	private function GuardarMovimientoFacturaVenta($productos, $nroFactura)
	{

		$totales_facturas = $this->GetTotalesFacturasVenta($productos);

		$costos_productos_impuesto = $this->CalcularCostosProductosPorImpuestoFacturaVenta($productos);

		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');
		if (!$asociacion || !isset($asociacion['Id_Plan_Cuenta'])) {
			error_log("Contabilizar: falta asociacion 'principal facturas' para Factura_Venta {$this->id_registro_modulo}");
			return;
		}

		$centro_costo = $this->getIdCentroCostoByCliente($this->nit);

		$this->centro_costo = $centro_costo != '' ? $centro_costo : 6;

		//GUARDAR EL MOVIMIENTO CONTABLE
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = number_format($totales_facturas['Total_General'], 2, ".", "");
		$oItem->Debe_Niif = number_format($totales_facturas['Total_General'], 2, ".", "");
		$oItem->Haber = "0";
		$oItem->Haber_Niif = "0";
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');

		$oItem->save();
		unset($oItem);

		if (floatval($totales_facturas['Factura_19']) > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 19', 'facturas');
			if (!$asociacion || !isset($asociacion['Id_Plan_Cuenta'])) {
				error_log("Contabilizar: falta asociacion 'iva facturas 19' para Factura_Venta {$this->id_registro_modulo}");
			} else {

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($totales_facturas['Factura_19'], 2, ".", "");
				$oItem->Haber_Niif = number_format($totales_facturas['Factura_19'], 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);
			}
		}

		if (floatval($totales_facturas['Factura_5']) > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 5', 'facturas');
			if (!$asociacion || !isset($asociacion['Id_Plan_Cuenta'])) {
				error_log("Contabilizar: falta asociacion 'iva facturas 5' para Factura_Venta {$this->id_registro_modulo}");
			} else {

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($totales_facturas['Factura_5'], 2, ".", "");
				$oItem->Haber_Niif = number_format($totales_facturas['Factura_5'], 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);
			}
		}

		//GUARDAR MOV. DE COSTOS SI EXISTEN COSTOS
		if (count($costos_productos_impuesto) > 0) {
			foreach ($costos_productos_impuesto as $key => $value) {
				$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado ' . $key);
				$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado ' . $key);
				if (
					!$asociacion || !isset($asociacion['Id_Plan_Cuenta']) ||
					!$asociacion_contraparte || !isset($asociacion_contraparte['Id_Plan_Cuenta'])
				) {
					error_log("Contabilizar: falta asociacion de costo gravado {$key} para Factura_Venta {$this->id_registro_modulo}");
					continue;
				}

				//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = number_format($value, 2, ".", "");
				$oItem->Debe_Niif = number_format($value, 2, ".", "");
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);

				//GUARDAR EL MOVIMIENTO CONTABLE CONTRAPARTE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($value, 2, ".", "");
				$oItem->Haber_Niif = number_format($value, 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	/*FACTURAS VENTAS*/

	private function GuardarMovimientoNCGFacturaVenta($productos, $nroFactura)
	{

		$id_modulo_nota = getIdMouloNota('Nota Credito Global');

		$totales_facturas = GetTotalesFacturasVentaNCG($productos);

		$costos_productos_impuesto = CalcularCostosProductosPorImpuestoNCGFacturaVenta($productos);


		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');


		$centro_costo = $this->getIdCentroCostoByCliente($this->nit);

		$this->centro_costo = $centro_costo != '' ? $centro_costo : 6;

		//GUARDAR EL MOVIMIENTO CONTABLE
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $id_modulo_nota;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

		/*		$oItem->Debe = number_format($totales_facturas['Total_General'],2,".","");
			$oItem->Debe_Niif = number_format($totales_facturas['Total_General'],2,".","");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Nit = $this->nit;
			*/

		$oItem->Debe =  "0";
		$oItem->Debe_Niif = "0";
		$oItem->Haber = number_format($totales_facturas['Total_General'], 2, ".", "");
		$oItem->Haber_Niif = number_format($totales_facturas['Total_General'], 2, ".", "");
		$oItem->Nit = $this->nit;


		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');

		$oItem->save();
		unset($oItem);

		if (floatval($totales_facturas['Factura_19']) > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 19', 'facturas');

			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;


			/* $oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($totales_facturas['Factura_19'],2,".","");
				$oItem->Haber_Niif = number_format($totales_facturas['Factura_19'],2,".",""); */

			$oItem->Debe = number_format($totales_facturas['Factura_19'], 2, ".", "");
			$oItem->Debe_Niif = number_format($totales_facturas['Factura_19'], 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";

			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
			$oItem->save();
			unset($oItem);
		}

		if (floatval($totales_facturas['Factura_5']) > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 5', 'facturas');


			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

			/*
		 		$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($totales_facturas['Factura_5'],2,".","");
				$oItem->Haber_Niif = number_format($totales_facturas['Factura_5'],2,".","");
					*/

			$oItem->Debe = number_format($totales_facturas['Factura_5'], 2, ".", "");
			$oItem->Debe_Niif = number_format($totales_facturas['Factura_5'], 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";


			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
			$oItem->save();
			unset($oItem);
		}

		//GUARDAR MOV. DE COSTOS SI EXISTEN COSTOS

		if (count($costos_productos_impuesto) > 0) {
			foreach ($costos_productos_impuesto as $key => $value) {

				$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado ' . intval($key));
				$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado ' . intval($key));





				//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $id_modulo_nota;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

				/* 		$oItem->Debe = number_format($value, 2, ".", "");
					$oItem->Debe_Niif = number_format($value, 2, ".", "");
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
 			*/

				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($value, 2, ".", "");
				$oItem->Haber_Niif = number_format($value, 2, ".", "");


				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);

				//GUARDAR EL MOVIMIENTO CONTABLE CONTRAPARTE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $id_modulo_nota;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

				/* 	$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Haber = number_format($value, 2, ".", "");
					$oItem->Haber_Niif = number_format($value, 2, ".", "");
 				*/

				$oItem->Debe = number_format($value, 2, ".", "");
				$oItem->Debe_Niif = number_format($value, 2, ".", "");
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";

				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);
			}
		}
		TotalIvaProductosNota(
			$productos,
			'factura venta',
			$nroFactura,
			$this->id_modulo,
			$this->id_registro_modulo,
			$this->nit,
			$this->tipo_nit,
			$this->centro_costo,
			$this->save_fecha,
			$id_modulo_nota
		);
	}

	private function GetTotalesFacturasVenta($productos)
	{
		$total_general_facturas = 0;
		$factura_5 = 0;
		$factura_19 = 0;
		$result = array();

		foreach ($productos as $p) {

			$total_general_facturas += (floatval($p['Cantidad']) * floatval($p['Precio_Venta'])) * (1 - floatval($p['Descuento']) / 100);

			if (floatval($p['Impuesto']) > 0) {

				if ($p['Impuesto'] == "19") {
					$factura_19 += (floatval($p['Cantidad']) * floatval($p['Precio_Venta'])) * (floatval($p['Impuesto']) / 100) * (1 - floatval($p['Descuento']) / 100);
				} elseif ($p['Impuesto'] == '5') {

					$factura_5 += (floatval($p['Cantidad']) * floatval($p['Precio_Venta'])) * (floatval($p['Impuesto']) / 100) * (1 - floatval($p['Descuento']) / 100);
				}
			}
		}

		$result['Total_General'] = ($total_general_facturas + $factura_19 + $factura_5);
		$result['Factura_19'] = $factura_19;
		$result['Factura_5'] = $factura_5;

		return $result;
	}

	private function GetCodigoFactura($idFactura)
	{

		$query = '
				SELECT
					Codigo
				FROM Factura_Venta
				WHERE
					Id_Factura_Venta = ' . $idFactura;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result != false ? $result['Codigo'] : 'Codigo no encontrado';
	}

	private function GetCodigoNotaCredito($id)
	{

		$query = '
				SELECT
					Codigo
				FROM Nota_Credito
				WHERE
					Id_Nota_Credito = ' . $id;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result != false ? $result['Codigo'] : 'Codigo no encontrado';
	}
	/*FIN FACTURA VENTA*/

	/*FACTURAS MULTITIPO*/

	private function GuardarMovimientoFacturaMultitipo($nroFactura, $idFactura, $tipo_factura_nopos, $cuota_moderadora, $datos_dis)
	{

		// VALIDANDO CUOTA MODERADORA

		if ($cuota_moderadora == 0) {
			if ($datos_dis['Id_Regimen'] == 1 && $tipo_factura_nopos == 'Factura') { // Se hace esto si el regimen es contributivo y la cuota es 0.
				$cuota_moderadora = $this->getCuotaReal($datos_dis['Id_Dispensacion']);
			}
		}

		$productos_factura = $this->GetProductosFactura($idFactura);

		$costos_productos_impuesto = $tipo_factura_nopos == 'Factura' ? $this->CalcularCostosProductosPorImpuesto($productos_factura) : [];


		$totales_facturas = $this->GetTotalesFactura($productos_factura, $cuota_moderadora);

		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');
		// $centro_costo = $this->getIdCentroCostoByCliente($this->nit);
		$centro_costo = $this->getIdCentroCostoByTipo('Punto_Dispensacion', $datos_dis['Id_Punto_Dispensacion']);
		$this->centro_costo = $centro_costo != '' ? $centro_costo : '0';

		//GUARDAR EL MOVIMIENTO CONTABLE
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$total = $totales_facturas['Total_General'];

		if ($total > 0) {
			$oItem->Debe = number_format($total, 2, ".", ""); // Se descuenta la cuota a la factura principal.
			$oItem->Debe_Niif = number_format($total, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
		} elseif ($total <= 0) {

			$oItem->Debe = number_format($total + $cuota_moderadora, 2, ".", ""); // Se descuenta la cuota a la factura principal.
			$oItem->Debe_Niif = number_format($total + $cuota_moderadora, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
		}

		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');

		$oItem->save();
		unset($oItem);

		if (floatval($totales_facturas['Ivas']['19']) > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 19', 'facturas');

			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($totales_facturas['Ivas']['19'], 2, ".", "");
			$oItem->Haber_Niif = number_format($totales_facturas['Ivas']['19'], 2, ".", "");
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');
			$oItem->save();
			unset($oItem);
		}

		if (isset($totales_facturas['Ivas']) && is_array($totales_facturas['Ivas']) && isset($totales_facturas['Ivas']['5']) && floatval($totales_facturas['Ivas']['5']) > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 5', 'facturas');
			if (!$asociacion || !isset($asociacion['Id_Plan_Cuenta'])) {
				$this->logError("Contabilizar: falta asociacion 'iva facturas 5' para Factura", [
					'id_registro_modulo' => $this->id_registro_modulo
				]);
			} else {
				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($totales_facturas['Ivas']['5'], 2, ".", "");
				$oItem->Haber_Niif = number_format($totales_facturas['Ivas']['5'], 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');
				$oItem->save();
				unset($oItem);
			}
		}

		foreach ($costos_productos_impuesto as $key => $value) {

			$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado ' . $key);
			$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado ' . $key);

			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = number_format($value, 2, ".", "");
			$oItem->Debe_Niif = number_format($value, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');
			$oItem->save();
			unset($oItem);

			// //GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($value, 2, ".", "");
			$oItem->Haber_Niif = number_format($value, 2, ".", "");
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');
			$oItem->save();
			unset($oItem);
		}

		if ($cuota_moderadora > 0) { // Contabilizando la cuota moderadora
			$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora', 'facturas');


			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = number_format($cuota_moderadora, 2, ".", "");
			$oItem->Debe_Niif = number_format($cuota_moderadora, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');

			$oItem->save();
			unset($oItem);

			if ($total <= 0) {
				$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora credito', 'facturas');

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = number_format($cuota_moderadora, 2, ".", "");
				$oItem->Haber_Niif = number_format($cuota_moderadora, 2, ".", "");
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');

				$oItem->save();
				unset($oItem);
			}
		}

		$this->TotalIvaProductos($productos_factura, 'factura', $nroFactura, $cuota_moderadora);
	}
	/*FIN FACTURA MULTITIPO*/

	/*INICIO  NOTA CREDITO GLOBAL FACTURA*/
	private function GuardarMovimientoNotaCreditoGlobalFactura($idFactura, $tipo_factura_nopos, $cuota_moderadora, $datos_dis)
	{

		// VALIDANDO CUOTA MODERADORA
		$id_modulo_nota = getIdMouloNota('Nota Credito Global');

		if ($cuota_moderadora == 0) {
			if ($datos_dis['Id_Regimen'] == 1 && $tipo_factura_nopos == 'Factura') { // Se hace esto si el regimen es contributivo y la cuota es 0.
				$cuota_moderadora = $this->getCuotaReal($datos_dis['Id_Dispensacion']);
			}
		}
		$nota = getNotaCreditoGlobal($this->id_registro_modulo);

		$productos_nota = getProductosNotaCreditoGlobal($this->id_registro_modulo, $nota['Tipo_Factura']);

		$costos_productos_impuesto = $tipo_factura_nopos == 'Factura' ? CalcularCostosProductosPorImpuestoNota($productos_nota) : [];

		$totales_nota = GetTotalesNotaCreditoGloblal($productos_nota, $cuota_moderadora);

		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');

		$centro_costo = $this->getIdCentroCostoByTipo('Punto_Dispensacion', $datos_dis['Id_Punto_Dispensacion']);
		$this->centro_costo = $centro_costo != '' ? $centro_costo : '0';

		//GUARDAR EL MOVIMIENTO CONTABLE
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $id_modulo_nota;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$total = $totales_nota['Total_General'];

		if ($total > 0) {
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($total, 2, ".", ""); // Se descuenta la cuota a la factura principal.
			$oItem->Haber_Niif = number_format($total, 2, ".", "");
		} elseif ($total <= 0) {
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($total + $cuota_moderadora, 2, ".", ""); // Se descuenta la cuota a la factura principal.
			$oItem->Haber_Niif = number_format($total + $cuota_moderadora, 2, ".", "");
		}

		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nota['Codigo'];
		$oItem->Numero_Comprobante = $nota['Codigo'];

		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');

		$oItem->save();
		unset($oItem);

		if (array_key_exists('19', $totales_nota['Ivas']) && floatval($totales_nota['Ivas']['19']) > 0) {

			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 19', 'facturas');

			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

			$oItem->Debe = number_format($totales_nota['Ivas']['19'], 2, ".", "");
			$oItem->Debe_Niif = number_format($totales_nota['Ivas']['19'], 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";

			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nota['Codigo'];
			$oItem->Numero_Comprobante = $nota['Codigo'];
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');
			$oItem->save();
			unset($oItem);
		}

		if (array_key_exists('5', $totales_nota['Ivas']) && floatval($totales_nota['Ivas']['5']) > 0) {

			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 5', 'facturas');

			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

			$oItem->Debe =  number_format($totales_nota['Ivas']['5'], 2, ".", "");
			$oItem->Debe_Niif = number_format($totales_nota['Ivas']['5'], 2, ".", "");;
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";

			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nota['Codigo'];
			$oItem->Numero_Comprobante = $nota['Codigo'];
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');
			$oItem->save();
			unset($oItem);
		}


		foreach ($costos_productos_impuesto as $key => $value) {

			$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado ' . intval($key));

			$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado ' . intval($key));

			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($value, 2, ".", "");
			$oItem->Haber_Niif = number_format($value, 2, ".", "");

			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nota['Codigo'];
			$oItem->Numero_Comprobante = $nota['Codigo'];

			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');

			$oItem->save();

			// //GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;


			$oItem->Debe = number_format($value, 2, ".", "");
			$oItem->Debe_Niif = number_format($value, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";


			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nota['Codigo'];
			$oItem->Numero_Comprobante = $nota['Codigo'];
			if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');
			$oItem->save();

			unset($oItem);
		}

		if ($cuota_moderadora > 0) { // Contabilizando la cuota moderadora

			$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora', 'facturas');


			//GUARDAR EL MOVIMIENTO CONTABLE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $id_modulo_nota;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

			$oItem->Debe =  "0";
			$oItem->Debe_Niif =  "0";
			$oItem->Haber = number_format($cuota_moderadora, 2, ".", "");
			$oItem->Haber_Niif = number_format($cuota_moderadora, 2, ".", "");


			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nota['Codigo'];
			$oItem->Numero_Comprobante = $nota['Codigo'];
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');

			$oItem->save();
			unset($oItem);

			if ($total <= 0) {
				$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora credito', 'facturas');

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $id_modulo_nota;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Debe = number_format($cuota_moderadora, 2, ".", "");
				$oItem->Debe_Niif = number_format($cuota_moderadora, 2, ".", "");

				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nota['Codigo'];
				$oItem->Numero_Comprobante = $nota['Codigo'];
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito_Global');

				$oItem->save();
				unset($oItem);
			}
		}

		TotalIvaProductosNota(
			$productos_nota,
			'Nota_Credito_Global',
			$nota['Codigo'],
			$this->id_modulo,
			$this->id_registro_modulo,
			$this->nit,
			$this->tipo_nit,
			$this->centro_costo,
			$this->save_fecha,
			$id_modulo_nota
		);
	}
	/*FIN  NOTA CREDITO GLOBAL FACTURA*/

	/*FACTURAS CAPITA*/

	private function GuardarMovimientoFacturaCapita($nroFactura, $idFactura, $totalFactura, $cuota = 0)
	{

		$productos_factura = $this->GetProductosFacturaCapita($idFactura);

		$costos_productos = $this->CalcularCostosProductosCapita($productos_factura);

		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');

		$centro_costo = $this->getIdCentroCostoByCliente($this->nit);
		$this->centro_costo = $centro_costo != '' ? $centro_costo : '0';

		//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA PPAL.
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = number_format($totalFactura - $cuota, 2, ".", "");
		$oItem->Debe_Niif = number_format($totalFactura - $cuota, 2, ".", "");
		$oItem->Haber = "0";
		$oItem->Haber_Niif = "0";
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
		$oItem->save();
		unset($oItem);

		$asociacion = $this->BuscarInformacionParaMovimiento('gravado 0');
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = "0";
		$oItem->Debe_Niif = "0";
		$oItem->Haber = number_format($totalFactura, 2, ".", "");
		$oItem->Haber_Niif = number_format($totalFactura, 2, ".", "");
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
		$oItem->save();
		unset($oItem);

		//GUARDAR MOV. DE COSTOS SI EXISTEN COSTOS
		if (count($costos_productos) > 0) {

			foreach ($costos_productos as $key => $value) {
				$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado ' . $key);
				$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado ' . $key);

				//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = number_format($value, 2, ".", "");
				$oItem->Debe_Niif = number_format($value, 2, ".", "");
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
				$oItem->save();
				unset($oItem);

				//GUARDAR EL MOVIMIENTO CONTABLE CONTRAPARTE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($value, 2, ".", "");
				$oItem->Haber_Niif = number_format($value, 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
				$oItem->save();
				unset($oItem);
			}
		}

		if ($cuota > 0) {

			$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora', 'facturas');

			//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUOTA MODERADORA
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = number_format($cuota, 2, ".", "");
			$oItem->Debe_Niif = number_format($cuota, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = 'Cliente';
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
			$oItem->save();
			unset($oItem);
		}
	}

	/*FIN FACTURAS CAPITA*/



	/*FACTURAS CAPITA*/

	private function GuardarMovimientoNCGFacturaCapita($nota, $idNota, $totalFactura, $cuota = 0)
	{

		$id_modulo_nota = getIdMouloNota('Nota Credito Global');
		$productos_factura = getProductosFacturaCapitaNCG($idNota);
		/**
		 * BUSCAR INFORMACION PARA 
		 */


		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');
		$asociacionPricipal = $asociacion;
		$centro_costo = $this->getIdCentroCostoByCliente($this->nit);
		$this->centro_costo = $centro_costo != '' ? $centro_costo : '0';

		//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA PPAL.
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $id_modulo_nota;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = "0";
		$oItem->Debe_Niif = "0";
		$oItem->Haber = number_format($totalFactura - $cuota, 2, ".", "");
		$oItem->Haber_Niif = number_format($totalFactura - $cuota, 2, ".", "");
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nota['Codigo'];
		$oItem->Numero_Comprobante = $nota['Codigo'];
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
		$oItem->save();
		unset($oItem);

		$asociacion = $this->BuscarInformacionParaMovimiento('gravado 0');
		$asociacionGravado0 = $asociacion;
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $id_modulo_nota;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = number_format($totalFactura, 2, ".", "");
		$oItem->Debe_Niif = number_format($totalFactura, 2, ".", "");
		$oItem->Haber = "0";
		$oItem->Haber_Niif = "0";
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nota['Codigo'];
		$oItem->Numero_Comprobante = $nota['Codigo'];
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
		$oItem->save();
		unset($oItem);





		$costos_productos = CalcularCostosProductosNCGCapita($nota['Codigo_Factura'], $productos_factura, $asociacionPricipal, $asociacionGravado0);


		//GUARDAR MOV. DE COSTOS SI EXISTEN COSTOS
		if (count($costos_productos) > 0) {

			foreach ($costos_productos as $key => $value) {

				//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");

				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $id_modulo_nota;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;

				$oItem->Debe = $value['Haber'];
				$oItem->Debe_Niif = $value['Haber_Nif'];

				$oItem->Haber = $value['Debe'];
				$oItem->Haber_Niif = $value['Debe_Nif'];

				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nota['Codigo'];
				$oItem->Numero_Comprobante = $nota['Codigo'];
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
				$oItem->save();
				unset($oItem);
			}
		}
		$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora', 'facturas');


		/* 	
				if ($cuota > 0) {
					
					$asociacion = $this->BuscarInformacionParaMovimiento('cuota moderadora', 'facturas');
	
					//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUOTA MODERADORA
					$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $id_modulo_nota;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = number_format($cuota, 2, ".", "");
					$oItem->Debe_Niif = number_format($cuota, 2, ".", "");
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = 'Cliente';
					$oItem->Documento = $nota['Codigo'];
					$oItem->Numero_Comprobante = $nota['Codigo'];
					if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
						$oItem->Id_Centro_Costo = $this->centro_costo;
					
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Capita');
					$oItem->save();
					unset($oItem);
				} */
	}

	/*FIN FACTURAS CAPITA*/




	/*FACTURAS ADMINISTRATIVA---------------------------------------------*/

	private function GuardarMovimientoFacturaAdministrativa($nroFactura, $swithcActivos, $idFactura, $fechaFactura)
	{
		$productos_factura = $this->GetProductosFacturaAdministrativa($idFactura);

		$iva = 0;
		$sum_total_con_descuento = 0;

		$total_depreciado_activos_PCGA = 0;
		$saldo_activos_PCGA = 0;
		$costo_activos_PCGA = 0;

		$total_depreciado_activos_NIIF = 0;
		$saldo_activos_NIIF = 0;
		$costo_activos_NIIF = 0;


		foreach ($productos_factura as $key => $prod) {

			//crear movimiento contable
			$total_con_descuento  =  $prod['Total_Precio_Con_Descuento'];

			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($total_con_descuento, 2, ".", "");
			$oItem->Haber_Niif = number_format($total_con_descuento, 2, ".", "");
			$oItem->Id_Plan_Cuenta = $prod['Id_Plan_Cuenta'];

			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);


			$total_depreciado_activos_PCGA += $prod['Depreciacion_Activo_Fijo_PCGA'];
			$saldo_activos_PCGA += $prod['Saldo_Activo_Fijo_PCGA'];
			$costo_activos_PCGA += $prod['Costo_Activo_Fijo_PCGA'];

			$total_depreciado_activos_NIIF += $prod['Depreciacion_Activo_Fijo_NIIF'];
			$saldo_activos_NIIF += $prod['Saldo_Activo_Fijo_NIIF'];
			$costo_activos_NIIF += $prod['Costo_Activo_Fijo_NIIF'];


			$iva += $prod['T_Impuesto'];
			$sum_total_con_descuento += $total_con_descuento;
		}

		$retencion_impuesto = 0;
		if ($iva > 0) {

			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");

			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($iva, 2, ".", "");
			$oItem->Haber_Niif = number_format($iva, 2, ".", "");
			$oItem->Id_Plan_Cuenta = '356';

			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);
		}

		//NACIONALES -----------------------------
		$nacionales  =  $sum_total_con_descuento + $iva; //+ $retencion_impuesto - $retencion_ventas ;

		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Debe = number_format($nacionales, 2, ".", "");
		$oItem->Debe_Niif = number_format($nacionales, 2, ".", "");
		$oItem->Haber = "0";
		$oItem->Haber_Niif = "0";
		$oItem->Id_Plan_Cuenta = '57';

		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		$oItem->Id_Centro_Costo = $this->centro_costo;
		$oItem->Fecha_Movimiento = $fechaFactura;
		$oItem->save();
		unset($oItem);

		//contabilizar venta activos
		if ($swithcActivos == 'Si') {
			//valor depreciado
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Debe = number_format($total_depreciado_activos_PCGA, 2, ".", "");
			$oItem->Debe_Niif = number_format($total_depreciado_activos_NIIF, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Id_Plan_Cuenta = '179';

			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);

			//saldo activo = costo - depreciacion
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Debe = number_format($saldo_activos_PCGA, 2, ".", "");
			$oItem->Debe_Niif = number_format($saldo_activos_NIIF, 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Id_Plan_Cuenta = '639';

			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);

			// costo de activo
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Haber = number_format($costo_activos_PCGA, 2, ".", "");
			$oItem->Haber_Niif = number_format($costo_activos_NIIF, 2, ".", "");
			$oItem->Id_Plan_Cuenta = '165';

			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);
		}
	}

	/*FIN FACTURAS ADMINISTRATIVA-----------------------------------------*/

	/*FACTURAS NOTA CREDITO GLOBAL ADMINISTRATIVA--------------------------*/

	private function GuardarMovimientoNCGFacturaAdministrativa($Nota, $fechaFactura)
	{

		$productos_factura = getProductosFacturaAdminNCG($Nota['Id_Nota_Credito_Global']);

		$iva = 0;
		$sum_total_con_descuento = 0;
		foreach ($productos_factura as $key => $prod) {
			//crear movimiento contable				
			$total_con_descuento  =  $prod['Total_Precio_Con_Descuento'];

			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Debe =  number_format($total_con_descuento, 2, ".", "");
			$oItem->Debe_Niif = number_format($total_con_descuento, 2, ".", "");
			$oItem->Haber =  "0";
			$oItem->Haber_Niif =  "0";

			$oItem->Id_Plan_Cuenta = $prod['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $Nota['Codigo'];
			$oItem->Numero_Comprobante = $Nota['Codigo'];
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);

			$iva += $prod['T_Impuesto'];
			$sum_total_con_descuento += $total_con_descuento;
		}

		$retencion_impuesto = 0;
		if ($iva > 0) {
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");

			$oItem->Debe =  number_format($iva, 2, ".", "");
			$oItem->Debe_Niif = number_format($iva, 2, ".", "");
			$oItem->Haber =  "0";
			$oItem->Haber_Niif =  "0";

			$oItem->Id_Plan_Cuenta = '356';
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $Nota['Codigo'];
			$oItem->Numero_Comprobante = $Nota['Codigo'];
			$oItem->Id_Centro_Costo = $this->centro_costo;
			$oItem->Fecha_Movimiento = $fechaFactura;
			$oItem->save();
			unset($oItem);
		}

		//NACIONALES -----------------------------
		$nacionales  =  $sum_total_con_descuento + $iva; //+ $retencion_impuesto - $retencion_ventas ;

		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Debe = "0";
		$oItem->Debe_Niif = "0";
		$oItem->Haber = number_format($nacionales, 2, ".", "");
		$oItem->Haber_Niif = number_format($nacionales, 2, ".", "");

		$oItem->Id_Plan_Cuenta = '57';
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $Nota['Codigo'];
		$oItem->Numero_Comprobante = $Nota['Codigo'];
		$oItem->Id_Centro_Costo = $this->centro_costo;
		$oItem->Fecha_Movimiento = $fechaFactura;
		$oItem->save();
		unset($oItem);
	}

	/*FIN NOTAS CREDITO GLOBAL FACTURAS ADMINISTRATIVA-------------------------*/




	/*NOTAS CREDITO*/

	private function GuardarMovimientoNotaCredito($productos_factura, $nroFactura)
	{

		$totales_facturas = $this->GetTotalesNotaCredito($productos_factura);
		$productos_con_costos = $this->GetCostosProductosNotaCredito($productos_factura);
		$costos_productos_impuesto = $this->CalcularCostosProductosPorImpuesto($productos_con_costos);
		$asociacion = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');
		$centro_costo = $this->getIdCentroCostoByCliente($this->nit);
		$this->centro_costo = $centro_costo != '' ? $centro_costo : '0';

		/* PRIMER MOVIMIENTO ESTA BIEN*/
		//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA PPAL.
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = "0";
		$oItem->Debe_Niif = "0";
		$oItem->Haber = number_format($totales_facturas['Total_General'], 2, ".", "");
		$oItem->Haber_Niif = number_format($totales_facturas['Total_General'], 2, ".", "");
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = $nroFactura;
		$oItem->Numero_Comprobante = $nroFactura;
		if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
			$oItem->Id_Centro_Costo = $this->centro_costo;

		if ($this->save_fecha) {
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
		} else {
			$oItem->Fecha_Movimiento = $this->fecha_movimiento;
		}
		$oItem->save();
		unset($oItem);

		$this->TotalIvaProductos($productos_factura, 'nota credito', $nroFactura);
		/* SEGUNDO MOVIMIENTO ESTA BIEN */
		if ($totales_facturas['Total_General_Iva'] > 0) {
			$asociacion = $this->BuscarInformacionParaMovimiento('iva facturas 19');
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Debe = number_format($totales_facturas['Total_General_Iva'], 2, ".", "");
			$oItem->Debe_Niif = number_format($totales_facturas['Total_General_Iva'], 2, ".", "");
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $nroFactura;
			$oItem->Numero_Comprobante = $nroFactura;
			if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo;

			if ($this->save_fecha) {
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
			} else {
				$oItem->Fecha_Movimiento = $this->fecha_movimiento;
			}
			$oItem->save();
			unset($oItem);
		}

		//GUARDAR MOV. DE COSTOS SI EXISTEN COSTOS
		if (count($costos_productos_impuesto) > 0) {
			if (isset($costos_productos_impuesto['19']) && $costos_productos_impuesto['19'] > 0) {
				$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado 19');
				$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado 19');
				/* TERCER MOVIMIENTO */
				//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = number_format($costos_productos_impuesto['19'], 2, ".", "");
				$oItem->Haber_Niif = number_format($costos_productos_impuesto['19'], 2, ".", "");
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha) {
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
				} else {
					$oItem->Fecha_Movimiento = $this->fecha_movimiento;
				}
				$oItem->save();
				unset($oItem);
				/* CUARTO MOVIMIENTO */
				//GUARDAR EL MOVIMIENTO CONTABLE CONTRAPARTE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Debe = number_format($costos_productos_impuesto['19'], 2, ".", "");
				$oItem->Debe_Niif = number_format($costos_productos_impuesto['19'], 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha) {
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
				} else {
					$oItem->Fecha_Movimiento = $this->fecha_movimiento;
				}
				$oItem->save();
				unset($oItem);
			}
			if (isset($costos_productos_impuesto['0']) && $costos_productos_impuesto['0'] > 0) {
				$asociacion = $this->BuscarInformacionParaMovimiento('costo gravado 0');
				$asociacion_contraparte = $this->BuscarInformacionParaMovimiento('costo contraparte gravado 0');
				/* QUINTO MOVIMIENTO */
				//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = number_format($costos_productos_impuesto['0'], 2, ".", "");
				$oItem->Haber_Niif = number_format($costos_productos_impuesto['0'], 2, ".", "");
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha) {
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
				} else {
					$oItem->Fecha_Movimiento = $this->fecha_movimiento;
				}
				$oItem->save();
				unset($oItem);
				/* SEXTO MOVIMIENTO */
				//GUARDAR EL MOVIMIENTO CONTABLE CONTRAPARTE DEL TOTAL DE LOS COSTOS
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion_contraparte['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Debe = number_format($costos_productos_impuesto['0'], 2, ".", "");
				$oItem->Debe_Niif = number_format($costos_productos_impuesto['0'], 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion_contraparte['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha) {
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
				} else {
					$oItem->Fecha_Movimiento = $this->fecha_movimiento;
				}
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function GetTotalesNotaCredito($productos)
	{

		$total_general_facturas = 0;
		$total_iva = 0;
		$result = array();

		foreach ($productos as $p) {

			$total_general_facturas += floatval($p['Subtotal']);

			$imp = $p['Impuesto'] != '' ? $p['Impuesto'] : 0;

			if ($imp > 0) {
				$total_iva += ($p['Subtotal'] * ($imp / 100));
				$total_general_facturas += ($p['Subtotal'] * ($imp / 100));
			}
		}

		$result['Total_General'] = $total_general_facturas;
		$result['Total_General_Iva'] = $total_iva;

		return $result;
	}

	private function ArmarInProductos($productos)
	{

		$i = 0;
		$in = '';
		$products = [];
		foreach ($productos as $value) {
			$products[] = $value['Id_Producto'];
		}

		return implode(",", $products);
	}

	/*FIN NOTAS CREDITO*/

	/*COMPROBANTES*/

	private function GuardarMovimientoComprobanteIngreso($datos, $codigo_comprobante)
	{

		$codigo_facturas = [];

		$asociacion_ppal = $this->BuscarInformacionParaMovimiento('principal facturas', 'facturas');

		if (count($datos['Facturas']) > 0) {
			foreach ($datos['Facturas'] as $i => $fact) {

				$codigo_facturas[] = $fact['Codigo'];

				$is_nota_credito = strpos($fact['Codigo'], 'NC');

				//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA PPAL.
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion_ppal['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				if ($is_nota_credito !== false) {
					$oItem->Haber = "0";
					$oItem->Debe = number_format($fact['ValorIngresado'], 2, ".", "");
					$oItem->Haber_Niif = "0";
					$oItem->Debe_Niif = number_format($fact['ValorIngresado'], 2, ".", "");
				} else {
					$oItem->Debe = "0";
					$oItem->Haber = number_format($fact['ValorIngresado'], 2, ".", "");
					$oItem->Debe_Niif = "0";
					$oItem->Haber_Niif = number_format($fact['ValorIngresado'], 2, ".", "");
				}
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $fact['Codigo'];
				$oItem->Numero_Comprobante = $codigo_comprobante;
				$oItem->Fecha_Movimiento = $datos['Fecha_Comprobante'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
				$oItem->save();
				unset($oItem);

				//GUARDAR MOV. DE RETENCIONES
				if (count($fact['RetencionesFacturas']) > 0) {
					foreach ($fact['RetencionesFacturas'] as $key => $value) {
						// $asociacion = $this->BuscarInformacionParaMovimiento($key, 'facturas');

						//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $value['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						if ($is_nota_credito !== false) {
							$oItem->Debe = "0";
							$oItem->Haber = number_format($value['Valor'], 2, ".", "");
							$oItem->Debe_Niif = "0";
							$oItem->Haber_Niif = number_format($value['Valor'], 2, ".", "");
						} else {
							$oItem->Haber = "0";
							$oItem->Debe = number_format($value['Valor'], 2, ".", "");
							$oItem->Haber_Niif = "0";
							$oItem->Debe_Niif = number_format($value['Valor'], 2, ".", "");
						}
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $fact['Codigo'];
						$oItem->Numero_Comprobante = $codigo_comprobante;
						$oItem->Fecha_Movimiento = $datos['Fecha_Comprobante'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
						$oItem->save();
						unset($oItem);
					}
				}

				## REGISTRAR DESCUENTOS SI ES DIFERENTE A 0 (CERO)

				if (count($fact['DescuentosFactura']) > 0) {

					foreach ($fact['DescuentosFactura'] as $key => $value) {
						//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $value['Id_Cuenta_Descuento'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						if ($is_nota_credito !== false) {
							$oItem->Debe = "0";
							$oItem->Haber = number_format($value['ValorDescuento'], 2, ".", "");
							$oItem->Debe_Niif = "0";
							$oItem->Haber_Niif = number_format($value['ValorDescuento'], 2, ".", "");
						} else {
							$oItem->Haber = "0";
							$oItem->Debe = number_format($value['ValorDescuento'], 2, ".", "");
							$oItem->Haber_Niif = "0";
							$oItem->Debe_Niif = number_format($value['ValorDescuento'], 2, ".", "");
						}
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $fact['Codigo'];
						$oItem->Numero_Comprobante = $codigo_comprobante;
						$oItem->Fecha_Movimiento = $datos['Fecha_Comprobante'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
						$oItem->save();
						unset($oItem);
					}
				}

				## REGISTRAR AJUSTES SI ES DIFERENTE A 0 (CERO)

				if (isset($fact['MayorPagar'])) {
					if ($fact['ValorMayorPagar'] > 0) {
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $fact['Id_Cuenta_MayorPagar'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						if ($is_nota_credito !== false) {
							$oItem->Haber = "0";
							$oItem->Debe = number_format($fact['ValorMayorPagar'], 2, ".", "");
							$oItem->Haber_Niif = "0";
							$oItem->Debe_Niif = number_format($fact['ValorMayorPagar'], 2, ".", "");
						} else {
							$oItem->Debe = "0";
							$oItem->Haber = number_format($fact['ValorMayorPagar'], 2, ".", "");
							$oItem->Debe_Niif = "0";
							$oItem->Haber_Niif = number_format($fact['ValorMayorPagar'], 2, ".", "");
						}
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $fact['Codigo'];
						$oItem->Numero_Comprobante = $codigo_comprobante;
						$oItem->Fecha_Movimiento = $datos['Fecha_Comprobante'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
						$oItem->save();
						unset($oItem);
					}
				}
			}
		}

		//GUARDAR EL MOVIMIENTO CONTABLE DEL COMPROBANTE
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $datos['Id_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = number_format($datos['Valor_Banco'], 2, ".", "");
		$oItem->Debe_Niif = number_format($datos['Valor_Banco'], 2, ".", "");
		$oItem->Haber = "0";
		$oItem->Haber_Niif = "0";
		$oItem->Nit = $this->nit;
		$oItem->Tipo_Nit = $this->tipo_nit;
		$oItem->Documento = implode(" | ", $codigo_facturas);
		$oItem->Numero_Comprobante = $codigo_comprobante;
		$oItem->Fecha_Movimiento = $datos['Fecha_Comprobante'];
		if ($this->save_fecha)
			$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
		$oItem->save();
		unset($oItem);
	}

	private function GuardarMovimientoComprobanteEgreso($datos, $codigo_comprobante)
	{

		$totales_facturas = array();

		$asociacion_ppal = $this->BuscarInformacionParaMovimiento('principal facturas proveedores', 'facturas');


		if (count($datos['Facturas']) > 0) {

			$totales_facturas = $this->GetTotalesComprobante($datos['Facturas'], 'Egreso', 'Facturas');
		} else {

			$totales_facturas = $this->GetTotalesComprobante($datos['Valores_Comprobante'], 'Egreso', 'Comprobante');
		}

		if (count($datos['Facturas']) > 0) {

			foreach ($totales_facturas['Facturas'] as $i => $fact) {

				//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA PPAL.
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $fact['tipo_factura'] == 'Notas Contables' ? $fact['id_plan_cuenta_factura'] : $asociacion_ppal['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = number_format($fact['neto'], 2, ".", "");
				$oItem->Haber = "0";
				$oItem->Debe_Niif = number_format($fact['neto'], 2, ".", "");
				$oItem->Haber_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $fact['codigo'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
				$oItem->save();
				unset($oItem);

				//GUARDAR MOV. DE RETENCIONES
				if (count($fact['retenciones']) > 0) {

					if (count($datos['Facturas']) > 0) {

						foreach ($fact['retenciones'] as $key => $value) {
							$asociacion = $this->BuscarInformacionParaMovimiento($key, 'facturas');

							//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
							$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
							$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
							$oItem->Id_Modulo = $this->id_modulo;
							$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
							$oItem->Debe = "0";
							$oItem->Debe_Niif = "0";
							$oItem->Haber = number_format($value['Total'], 2, ".", "");
							$oItem->Haber_Niif = number_format($value['Total'], 2, ".", "");
							$oItem->Nit = $this->nit;
							$oItem->Tipo_Nit = $this->tipo_nit;
							$oItem->Documento = $fact['codigo'];
							if ($this->save_fecha)
								$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
							$oItem->save();
							unset($oItem);
						}
					}
				}

				## REGISTRAR DESCUENTOS SI ES DIFERENTE A 0 (CERO)

				if (count($fact['descuentos']) > 0) {

					foreach ($fact['descuentos'] as $key => $value) {
						//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $value['Id_Cuenta_Descuento'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = "0";
						$oItem->Debe_Niif = "0";
						$oItem->Haber = number_format($value['ValorDescuento'], 2, ".", "");
						$oItem->Haber_Niif = number_format($value['ValorDescuento'], 2, ".", "");
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $fact['codigo'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
						$oItem->save();
						unset($oItem);
					}
				}

				if ($fact['ajuste'] > 0) {

					//GUARDAR EL MOVIMIENTO CONTABLE DEL TOTAL DE LOS COSTOS
					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $fact['id_plan_ajuste'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Haber = number_format($fact['ajuste'], 2, ".", "");
					$oItem->Haber_Niif = number_format($fact['ajuste'], 2, ".", "");
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = $fact['codigo'];
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
					$oItem->save();
					unset($oItem);
				}
			}
			//GUARDAR EL MOVIMIENTO CONTABLE DEL COMPROBANTE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $datos['Id_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Haber = number_format($totales_facturas['Total_General'], 2, ".", "");
			$oItem->Haber_Niif = number_format($totales_facturas['Total_General'], 2, ".", "");
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = implode(" | ", $totales_facturas['Codigos_Facturas']);
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
			$oItem->save();
			unset($oItem);
		} else {

			if (count($totales_facturas['Cuentas']) > 0) {
				foreach ($totales_facturas['Cuentas'] as $i => $cuenta) {
					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $cuenta['id_plan_cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Debe = number_format($cuenta['neto'], 2, ".", "");
					$oItem->Debe_Niif = number_format($cuenta['neto'], 2, ".", "");
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = "";
					$oItem->Detalles = $cuenta['detalles'];
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
					$oItem->save();
					unset($oItem);
				}
			}

			//GUARDAR EL MOVIMIENTO CONTABLE DEL COMPROBANTE
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $datos['Id_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Haber = number_format($totales_facturas['Total_General'], 2, ".", "");
			$oItem->Haber_Niif = number_format($totales_facturas['Total_General'], 2, ".", "");
			$oItem->Debe = "0";
			$oItem->Debe_Niif = "0";
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = "";
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
			$oItem->save();
			unset($oItem);

			/* $asociacion = $this->BuscarInformacionParaMovimiento('otros');
				
				$valor = floatval($totales_facturas['Total_General']) - floatval($totales_facturas['Retenciones']);

				//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA OTROS.
				$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Haber = number_format($valor, 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $value['Id_Banco'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
				$oItem->save();
				unset($oItem);

				//GUARDAR EL MOVIMIENTO CONTABLE DE LA CUENTA BANCO.
				$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $datos['Id_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Haber = number_format($valor, 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $value['Id_Banco'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
				$oItem->save();
				unset($oItem);	 */
		}

		/* //GUARDAR EL MOVIMIENTO CONTABLE DEL COMPROBANTE
			$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $datos['Id_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = number_format($totales_facturas['Total_General'], 2, ".", "");
			$oItem->Haber = "0";
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $codigo_comprobante;
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Comprobante');
			$oItem->save();
			unset($oItem); */
	}

	private function GetCodigoComprobante($idComprobante)
	{

		$query = '
				SELECT
					Codigo
				FROM Comprobante
				WHERE
					Id_Comprobante = ' . $idComprobante;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result != false ? $result['Codigo'] : 'Codigo no encontrado';
	}

	private function GetTotalesComprobante($data, $tipo_comprobante, $accion)
	{

		$total_general_facturas = 0;
		$total_general_facturas_neto = 0;
		$total_general_facturas_neto_niif = 0;
		$retenciones = array();
		$descuentos_fact = array();
		$rets = 0;
		$descuentos = 0;
		$ajustes = 0;
		$facturas = '';
		$result = array();
		$codigos_factura = [];

		if ($tipo_comprobante == 'Ingreso') {

			if ($accion == 'Facturas') {

				foreach ($data as $f) {

					$codigos_factura[] = $f['Codigo']; // Incluyendo códigos.

					$valor_ingresado = floatval($f['ValorIngresado']);
					$total_general_facturas += $valor_ingresado;
					$descuentos += floatval($f['ValorDescuento']);
					$total_general_facturas_neto += $valor_ingresado;
					$ajustes += floatval($f['ValorMayorPagar']);
					$total_general_facturas_neto -= $ajustes;
					$total_general_facturas -= $ajustes;

					if ($ajustes > 0) { // Si se aplicó algun tipo de ajuste
						if ((strpos($f['MayorPagar']['Codigo'], 'AJUSTE') !== false && strpos($f['MayorPagar']['Codigo'], '5305') !== false) || strpos($f['MayorPagar']['Codigo'], 'ESTAMPILLA') !== false) {
							if (strpos($f['Codigo'], 'NC') !== false) { // Cuando la factura sea una nota credito.
								$total_general_facturas += $ajustes; // Resto al valor de la factura para que el asiento esté cuadrado
							} else {
								$total_general_facturas -= $ajustes; // Resto al valor del banco para que el asiento esté cuadrado.
							}
						} else {
							if (strpos($f['Codigo'], 'NC') !== false) { // Cuando la factura sea una nota credito.
								$total_general_facturas -= $ajustes;  // Resto al valor del banco para que el asiento esté cuadrado.
							} else {
								$total_general_facturas += $ajustes; // Resto al valor de la factura para que el asiento esté cuadrado
							}
						}
					}

					if (count($f['RetencionesFacturas']) > 0) {

						foreach ($f['RetencionesFacturas'] as $key => $value) {

							$valor = floatval($value['Valor']);
							$total_general_facturas_neto += floatval($value['Valor']);

							if ($value['Tipo'] == 'Renta') {

								if (!$retenciones[strval($value['Id_Plan_Cuenta'])]) {
									$retenciones[strval($value['Id_Plan_Cuenta'])]['Total'] = $valor;
								} else {
									$retenciones[strval($value['Id_Plan_Cuenta'])]['Total'] += $valor;
								}
							} elseif ($value['Tipo'] == 'Iva') {

								if (!$retenciones[strval($value['Id_Plan_Cuenta'])]) {
									$retenciones[strval($value['Id_Plan_Cuenta'])]['Total'] = $valor;
								} else {
									$retenciones[strval($value['Id_Plan_Cuenta'])]['Total'] += $valor;
								}
							} elseif ($value['Tipo'] == 'Ica') {

								if (!$retenciones[strval($value['Id_Plan_Cuenta'])]) {
									$retenciones[strval($value['Id_Plan_Cuenta'])]['Total'] = $valor;
								} else {
									$retenciones[strval($value['Id_Plan_Cuenta'])]['Total'] += $valor;
								}
							}
						}
					}

					if (count($f['DescuentosFactura']) > 0) {
						foreach ($f['DescuentosFactura'] as $key => $descuento) {
							$total_general_facturas_neto += $descuento['ValorDescuento'];
							$descuentos_fact[] = $descuento;
						}
					}

					$factura = [
						"neto" => $total_general_facturas_neto,
						"codigo" => $f['Codigo'],
						"retenciones" => $retenciones,
						"descuentos" => $descuentos_fact,
						"ajuste" => $ajustes,
						"id_plan_descuento" => isset($f['Id_Cuenta_Descuento']) ? $f['Id_Cuenta_Descuento'] : '0',
						"id_plan_ajuste" => isset($f['Id_Cuenta_MayorPagar']) ? $f['Id_Cuenta_MayorPagar'] : '0',
						"codigo_ajuste" => isset($f['MayorPagar']['Codigo']) ? $f['MayorPagar']['Codigo'] : ''
					];

					$result['Facturas'][] = $factura;
					$total_general_facturas_neto = 0;
					$ajustes = 0;
					$descuentos_fact = [];
					$retenciones = [];
				}
			} elseif ($accion == 'Comprobante') {

				if (count($data) > 0) {

					foreach ($data as $key => $value) {

						$total_general_facturas +=  floatval($value['Subtotal']);
					}

					foreach ($data['Retenciones'] as $key => $value) {

						if ($value['Tipo'] == 'Renta') {

							if (!$retenciones['rete fuente ventas ' . number_format($value['Porcentaje'], 1, ".", "")]) {
								$retenciones['rete fuente ventas ' . number_format($value['Porcentaje'], 1, ".", "")] = floatval($value['Valor']);
							} else {
								$retenciones['rete fuente ventas ' . number_format($value['Porcentaje'], 1, ".", "")] += floatval($value['Valor']);
							}
						} elseif ($value['Tipo'] == 'Iva') {

							if (!$retenciones['rete iva ' . number_format($value['Porcentaje'], 1, ".", "")]) {
								$retenciones['rete iva ' . number_format($value['Porcentaje'], 1, ".", "")] = floatval($value['Valor']);
							} else {
								$retenciones['rete iva ' . number_format($value['Porcentaje'], 1, ".", "")] += floatval($value['Valor']);
							}
						} elseif ($value['Tipo'] == 'Ica') {

							if (!$retenciones['rete ica ']) {
								$retenciones['rete ica '] = floatval($value['Valor']);
							} else {
								$retenciones['rete ica '] += floatval($value['Valor']);
							}
						}

						$total_general_facturas -= floatval($value['Valor']);
					}
				}
			}
		} else {

			if ($accion == 'Facturas') {


				foreach ($data as $f) {

					$codigos_factura[] = $f['Codigo']; // Incluyendo códigos.

					$valor_ingresado = floatval($f['ValorIngresado']);
					$total_general_facturas += $valor_ingresado;
					$descuentos += floatval($f['ValorDescuento']);
					$total_general_facturas_neto += $valor_ingresado;
					$ajustes += floatval($f['ValorMayorPagar']);
					$total_general_facturas_neto -= $ajustes;
					$total_general_facturas -= $ajustes;

					//$facturas.= $f['Codigo'].', ';

					if (count($f['RetencionesFacturas']) > 0) {

						foreach ($f['RetencionesFacturas'] as $key => $value) {

							$valor = floatval($value['Valor']);
							$total_general_facturas_neto += floatval($value['Valor']);

							if ($value['Tipo'] == 'Renta') {

								if (!$retenciones['rete fuente ' . number_format($value['Porcentaje'], 1, ".", "")]) {
									$retenciones['rete fuente ' . number_format($value['Porcentaje'], 1, ".", "")]['Total'] = $valor;
								} else {
									$retenciones['rete fuente ' . number_format($value['Porcentaje'], 1, ".", "")]['Total'] += $valor;
								}

								$retenciones['rete fuente ' . number_format($value['Porcentaje'], 1, ".", "")]['facturas'] .= $f['Codigo'] . ', ';
							} elseif ($value['Tipo'] == 'Iva') {

								if (!$retenciones['rete iva ' . number_format($value['Porcentaje'], 1, ".", "")]) {
									$retenciones['rete iva ' . number_format($value['Porcentaje'], 1, ".", "")]['Total'] = $valor;
								} else {
									$retenciones['rete iva ' . number_format($value['Porcentaje'], 1, ".", "")]['Total'] += $valor;
								}
							} elseif ($value['Tipo'] == 'Ica') {

								if (!$retenciones['rete ica ' . number_format($value['Porcentaje'], 1, ".", "")]) {
									$retenciones['rete ica ' . number_format($value['Porcentaje'], 1, ".", "")]['Total'] = $valor;
								} else {
									$retenciones['rete ica ' . number_format($value['Porcentaje'], 1, ".", "")]['Total'] += $valor;
								}
							}
						}
					}

					if (count($f['DescuentosFactura']) > 0) {
						foreach ($f['DescuentosFactura'] as $key => $descuento) {
							$total_general_facturas_neto += $descuento['ValorDescuento'];
							$descuentos_fact[] = $descuento;
						}
					}

					$factura = [
						"neto" => $total_general_facturas_neto,
						"codigo" => $f['Codigo'],
						"id_plan_cuenta_factura" => $f['Id_Plan_Cuenta'],
						"tipo_factura" => $f['Tipo_Factura'],
						"retenciones" => $retenciones,
						"descuentos" => $descuentos_fact,
						"ajuste" => $ajustes,
						"id_plan_descuento" => isset($f['Id_Cuenta_Descuento']) ? $f['Id_Cuenta_Descuento'] : '0',
						"id_plan_ajuste" => isset($f['Id_Cuenta_MayorPagar']) ? $f['Id_Cuenta_MayorPagar'] : '0',
						"codigo_ajuste" => isset($f['MayorPagar']['Codigo']) ? $f['MayorPagar']['Codigo'] : ''
					];

					$result['Facturas'][] = $factura;
					$total_general_facturas_neto = 0;
					$ajustes = 0;
					$descuentos_fact = [];
					$retenciones = [];
				}
			} elseif ($accion == 'Comprobante') {

				if (count($data) > 0) {

					foreach ($data as $key => $value) {

						$total_general_facturas_neto +=  floatval($value['Subtotal']) + floatval($value['Total_Impuesto']);
						$total_general_facturas_neto_niif +=  floatval($value['Subtotal_Niif']) + floatval($value['Total_Impuesto']);
						$total_general_facturas += $total_general_facturas_neto;

						$cuentas = [
							"neto" => $total_general_facturas_neto,
							"neto_niif" => $total_general_facturas_neto_niif,
							"id_plan_cuenta" => $value['Id_Plan_Cuentas'],
							"detalles" => $value['Observaciones']
						];

						$result['Cuentas'][] = $cuentas;
						$total_general_facturas_neto = 0;
						$total_general_facturas_neto_niif = 0;
					}

					/* foreach ($data['Retenciones'] as $key => $value) {
							
							$rets += floatval($value['Valor']);
						} */
				}
			}
		}

		$result['Total_General'] = $total_general_facturas;
		$result['Codigos_Facturas'] = $codigos_factura;
		$result['Retenciones'] = $tipo_comprobante == 'Ingreso' ? $retenciones : $rets;
		/*$result['Total_Neto'] = $total_general_facturas_neto;
			$result['Retenciones'] = $tipo_comprobante == 'Ingreso' ? $retenciones : $rets;
			//$result['Retenciones']['Facturas'] = $facturas;
			$result['Descuentos'] = $descuentos; */

		return $result;
	}

	/*FIN COMPROBANTES*/

	/*AJUSTE INDIVIDUAL*/

	private function GuardarMovimientoAjusteIndividual($productos, $codigo_ajuste, $tipo, $clase_ajuste)
	{
		//var_dump($productos);
		$this->TotalIvaProductos($productos, 'ajuste ' . $tipo, $codigo_ajuste, 0, $clase_ajuste);
	}

	private function GetCodigoAjuste($idAjuste)
	{

		$query = '
				SELECT
					Codigo
				FROM Ajuste_Individual
				WHERE
					Id_Ajuste_Individual = ' . $idAjuste;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result != false ? $result['Codigo'] : 'Codigo no encontrado';
	}

	private function GetTotalesAjuste($productos)
	{

		$total_general_facturas = 0;
		$retenciones = array();
		$rets = 0;
		$descuentos = 0;
		$facturas = '';
		$result = array();

		return $result;
	}

	/*FIN AJUSTE INDIVIDUAL*/

	/*INVENTARIO FISICO*/
	private function GuardarMovimientoInventarioFisico($productos, $codigo_inventario)
	{
		$totales_inventario = $this->GetTotalesInventario($productos);

		foreach ($totales_inventario as $tipo => $valores) {

			if ($tipo == 'Sobrante') {

				foreach ($valores as $gravado => $total_gravado) {

					$busqueda = 'gravado ' . $gravado;
					$busqueda2 = 'gravado costo ' . $gravado;

					$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);
					$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);

					//GUARDAR EL MOVIMIENTO CONTABLE
					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = number_format($total_gravado, 2, ".", "");
					$oItem->Debe_Niif = number_format($total_gravado, 2, ".", "");
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = $codigo_inventario;
					$oItem->Numero_Comprobante = $codigo_inventario;
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico');
					$oItem->save();
					unset($oItem);

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Haber = number_format($total_gravado, 2, ".", "");
					$oItem->Haber_Niif = number_format($total_gravado, 2, ".", "");
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = $codigo_inventario;
					$oItem->Numero_Comprobante = $codigo_inventario;
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($tipo == 'Faltante') {

				foreach ($valores as $gravado => $total_gravado) {

					$busqueda = 'gravado ' . $gravado;
					$busqueda2 = 'gravado costo ' . $gravado;

					$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);
					$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);

					//GUARDAR EL MOVIMIENTO CONTABLE
					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Haber = number_format($total_gravado, 2, ".", "");
					$oItem->Haber_Niif = number_format($total_gravado, 2, ".", "");
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = $codigo_inventario;
					$oItem->Numero_Comprobante = $codigo_inventario;
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico');
					$oItem->save();
					unset($oItem);

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = number_format($total_gravado, 2, ".", "");
					$oItem->Debe_Niif = number_format($total_gravado, 2, ".", "");
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = $this->nit;
					$oItem->Tipo_Nit = $this->tipo_nit;
					$oItem->Documento = $codigo_inventario;
					$oItem->Numero_Comprobante = $codigo_inventario;
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico');
					$oItem->save();
					unset($oItem);
				}
			}
		}
	}

	private function GetTotalesInventario($productos)
	{
		$totales = array();
		$sobrante = array();
		$faltante = array();
		$id_producto = '';
		$costo_actual = 0;

		foreach ($productos as $value) {

			$resul_resta = intval($value['Cantidad_Final']) - intval($value['Cantidad_Inventario']);

			if ($id_producto != $value['Id_Producto']) {

				$costo_actual = $this->GetCostoProducto($value['Id_Producto'], 'Inventario');
			}

			//var_dump($costo_actual);

			if ($resul_resta > 0) {

				if ($value['Gravado'] == 'No') {

					$sobrante['0'] += $costo_actual * abs($resul_resta);
				} else {

					$sobrante['19'] += $costo_actual * abs($resul_resta);
				}
			} elseif ($resul_resta < 0) {

				if ($value['Gravado'] == 'No') {

					$faltante['0'] += $costo_actual * abs($resul_resta);
				} else {

					$faltante['19'] += $costo_actual * abs($resul_resta);
				}
			}

			$id_producto = $value['Id_Producto'];
		}

		$totales['Sobrante'] = $sobrante;
		$totales['Faltante'] = $faltante;

		return $totales;
	}
	/*FIN INVENTARIO FISICO*/

	/*INVENTARIO FISICO PUNTO*/
	private function GuardarMovimientoInventarioFisicoPunto($productos, $codigo_inventario, $conInventario)
	{
		$totales_inventario = $this->GetTotalesInventarioPunto($productos, $conInventario);

		if (count($totales_inventario) > 0) {

			foreach ($totales_inventario as $tipo => $valores) {

				if ($tipo == 'Sobrante') {

					if (count($totales_inventario[$tipo]) > 0) {
						foreach ($valores as $gravado => $total_gravado) {

							$busqueda = 'costo gravado ' . $gravado;
							$busqueda2 = 'costo contraparte gravado ' . $gravado;

							$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);
							$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);

							//GUARDAR EL MOVIMIENTO CONTABLE
							$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
							$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
							$oItem->Id_Modulo = $this->id_modulo;
							$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
							$oItem->Debe = number_format($total_gravado, 2, ".", "");
							$oItem->Debe_Niif = number_format($total_gravado, 2, ".", "");
							$oItem->Haber = "0";
							$oItem->Haber_Niif = "0";
							$oItem->Nit = $this->nit;
							$oItem->Tipo_Nit = $this->tipo_nit;
							$oItem->Documento = $codigo_inventario;
							$oItem->Numero_Comprobante = $codigo_inventario;
							if ($this->save_fecha)
								$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
							$oItem->save();
							unset($oItem);

							$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
							$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
							$oItem->Id_Modulo = $this->id_modulo;
							$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
							$oItem->Debe = "0";
							$oItem->Debe_Niif = "0";
							$oItem->Haber = number_format($total_gravado, 2, ".", "");
							$oItem->Haber_Niif = number_format($total_gravado, 2, ".", "");
							$oItem->Nit = $this->nit;
							$oItem->Tipo_Nit = $this->tipo_nit;
							$oItem->Documento = $codigo_inventario;
							$oItem->Numero_Comprobante = $codigo_inventario;
							if ($this->save_fecha)
								$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
							$oItem->save();
							unset($oItem);
						}
					} else {
						$busqueda = 'costo gravado 0';
						$busqueda2 = 'costo contraparte gravado 0';

						$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);
						$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);

						//GUARDAR EL MOVIMIENTO CONTABLE
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = "0";
						$oItem->Debe_Niif = "0";
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $codigo_inventario;
						$oItem->Numero_Comprobante = $codigo_inventario;
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
						$oItem->save();
						unset($oItem);

						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = "0";
						$oItem->Debe_Niif = "0";
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $codigo_inventario;
						$oItem->Numero_Comprobante = $codigo_inventario;
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
						$oItem->save();
						unset($oItem);
					}
				} elseif ($tipo == 'Faltante') {

					if (count($totales_inventario[$tipo]) > 0) {
						foreach ($valores as $gravado => $total_gravado) {

							$busqueda = 'costo gravado ' . $gravado;
							$busqueda2 = 'costo contraparte gravado ' . $gravado;

							$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);
							$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);

							//GUARDAR EL MOVIMIENTO CONTABLE
							$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
							$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
							$oItem->Id_Modulo = $this->id_modulo;
							$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
							$oItem->Debe = "0";
							$oItem->Debe_Niif = "0";
							$oItem->Haber = number_format($total_gravado, 2, ".", "");
							$oItem->Haber_Niif = number_format($total_gravado, 2, ".", "");
							$oItem->Nit = $this->nit;
							$oItem->Tipo_Nit = $this->tipo_nit;
							$oItem->Documento = $codigo_inventario;
							$oItem->Numero_Comprobante = $codigo_inventario;
							if ($this->save_fecha)
								$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
							$oItem->save();
							unset($oItem);

							$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
							$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
							$oItem->Id_Modulo = $this->id_modulo;
							$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
							$oItem->Debe = number_format($total_gravado, 2, ".", "");
							$oItem->Debe_Niif = number_format($total_gravado, 2, ".", "");
							$oItem->Haber = "0";
							$oItem->Haber_Niif = "0";
							$oItem->Nit = $this->nit;
							$oItem->Tipo_Nit = $this->tipo_nit;
							$oItem->Documento = $codigo_inventario;
							$oItem->Numero_Comprobante = $codigo_inventario;
							if ($this->save_fecha)
								$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
							$oItem->save();
							unset($oItem);
						}
					} else {
						$busqueda = 'costo gravado 0';
						$busqueda2 = 'costo contraparte gravado 0';

						$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);
						$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);

						//GUARDAR EL MOVIMIENTO CONTABLE
						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = "0";
						$oItem->Debe_Niif = "0";
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $codigo_inventario;
						$oItem->Numero_Comprobante = $codigo_inventario;
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
						$oItem->save();
						unset($oItem);

						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = "0";
						$oItem->Debe_Niif = "0";
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $this->nit;
						$oItem->Tipo_Nit = $this->tipo_nit;
						$oItem->Documento = $codigo_inventario;
						$oItem->Numero_Comprobante = $codigo_inventario;
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Inventario_Fisico_Punto');
						$oItem->save();
						unset($oItem);
					}
				}
			}
		}
	}

	private function GetTotalesInventarioPunto($productos, $conInventario)
	{
		$totales = array();
		$sobrante = array();
		$faltante = array();
		$id_producto = '';
		$costo_actual = 0;

		foreach ($productos as $value) {

			/* $resul_resta = 
					$conInventario == 'Si' ? 
					intval($value['Cantidad_Final']) - intval($value['Cantidad_Inventario']) : 
					intval($value['Cantidad_Final']) - intval($value['Segundo_Conteo']); */

			$cantidad_inventario = $value['Cantidad_Inventario'] != '' ? $value['Cantidad_Inventario'] : 0;
			$cantidad_final = $value['Cantidad_Final'] != '' ? $value['Cantidad_Final'] : 0;
			$segundo_conteo = $value['Segundo_Conteo'] != '' ? $value['Segundo_Conteo'] : 0;

			$resul_resta =
				intval($cantidad_inventario) > 0 ?
				intval($cantidad_final) - intval($cantidad_inventario) :
				intval($cantidad_final) - intval($segundo_conteo);

			if ($id_producto != $value['Id_Producto']) {

				$costo_actual = $this->GetCostoProducto($value['Id_Producto'], 'Inventario');
			}

			if ($resul_resta > 0) {

				if ($value['Gravado'] == 'No') {

					$sobrante['0'] += $costo_actual * abs($resul_resta);
				} else {

					$sobrante['19'] += $costo_actual * abs($resul_resta);
				}
			} elseif ($resul_resta < 0) {

				if ($value['Gravado'] == 'No') {

					$faltante['0'] += $costo_actual * abs($resul_resta);
				} else {

					$faltante['19'] += $costo_actual * abs($resul_resta);
				}
			}

			$id_producto = $value['Id_Producto'];
		}

		$totales['Sobrante'] = $sobrante;
		$totales['Faltante'] = $faltante;

		return $totales;
	}
	/*FIN INVENTARIO FISICO PUNTO*/

	/*ACTA RECEPCION INTERNACIONAL*/

	private function CrearMovimientosActaInternacional($datos)
	{
		$tasa_dolar = $this->GetTasaOrdenCompra($this->id_registro_modulo);
		$this->GuardarMovimientosActaInternacional($datos, $tasa_dolar);
	}

	private function GuardarMovimientosActaInternacional($datos, $tasa)
	{
		$productos_totales = $this->CalcularCostosProductosActaInternacional($datos['Productos'], $tasa);
		$nit_proveedor = $this->GetNitProveedorExtranjero($datos['Modelo']['Id_Acta_Recepcion_Internacional']);

		foreach ($productos_totales as  $key => $totales_factura) {

			foreach ($totales_factura as $k => $value) {

				if ($k == 'excluidos') {
					if ($value > 0) {
						$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('productos excluidos');

						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = number_format($value, 2, ".", "");
						$oItem->Debe_Niif = number_format($value, 2, ".", "");
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $nit_proveedor;
						$oItem->Tipo_Nit = 'Proveedor';
						$oItem->Documento = $key;
						$oItem->Detalle = 'Total productos excluidos';
						$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion_Internacional');
						$oItem->save();
						unset($oItem);
					}
				} elseif ($k == 'gravados') {
					if ($value > 0) {
						$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('productos gravados');

						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = number_format($value, 2, ".", "");
						$oItem->Debe_Niif = number_format($value, 2, ".", "");
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $nit_proveedor;
						$oItem->Tipo_Nit = 'Proveedor';
						$oItem->Documento = $key;
						$oItem->Detalle = 'Total productos gravados';
						$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion_Internacional');
						$oItem->save();
						unset($oItem);
					}
				} elseif ($k == 'total') {
					if ($value > 0) {
						$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

						$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Haber = number_format($value, 2, ".", "");
						$oItem->Haber_Niif = number_format($value, 2, ".", "");
						$oItem->Debe = "0";
						$oItem->Debe_Niif = "0";
						$oItem->Nit = $nit_proveedor;
						$oItem->Tipo_Nit = 'Proveedor';
						$oItem->Documento = $key;
						$oItem->Detalle = 'Total productos';
						$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion_Internacional');
						$oItem->save();
						unset($oItem);
					}
				}
			}
		}
	}

	private function CalcularCostosProductosActaInternacional($productos, $tasa_dolar)
	{
		$productos_factura = array();

		$i = 0;
		foreach ($productos as $p) {

			unset($p['Producto_Lotes'][count($p['Producto_Lotes']) - 1]);

			foreach ($p['Producto_Lotes'] as $lote) {

				if (!isset($productos_factura[$lote['Factura']])) {
					$productos_factura[$lote['Factura']] = array('excluidos' => 0, 'gravados' => 0, 'total' => 0);
				}
				$productos_factura[$lote['Factura']]['total'] += floatval($lote['Subtotal']) * floatval($tasa_dolar);

				if ($p['Gravado'] == 'No') {
					$productos_factura[$lote['Factura']]['excluidos'] += floatval($lote['Subtotal']) * floatval($tasa_dolar);
				} else {
					$productos_factura[$lote['Factura']]['gravados'] += floatval($lote['Subtotal']) * floatval($tasa_dolar);
				}
			}
		}
		return $productos_factura;
	}

	private function GetTasaOrdenCompra($id_acta)
	{
		global $queryObj;
		$query = '
				SELECT
					OCI.Tasa_Dolar
				FROM Orden_Compra_Internacional OCI
				INNER JOIN Acta_Recepcion_Internacional ARI ON OCI.Id_Orden_Compra_Internacional = ARI.Id_Orden_Compra_Internacional
				WHERE
					ARI.Id_Acta_Recepcion_Internacional = ' . $id_acta;

		$this->queryObj->SetQuery($query);
		$tasa = $this->queryObj->ExecuteQuery('simple');
		return $tasa['Tasa_Dolar'];
	}

	/*FIN ACTA RECEPCION INTERNACIONAL*/

	/*PARCIAL ACTA RECEPCION INTERNACIONAL*/

	private function CrearMovimientosParcialActaInternacional($datos, $id_nacionalizacion)
	{
		$this->save_fecha = true;

		$tasa_orden = $this->GetTasaOrdenCompra($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
		$nit_proveedor_orden = $this->GetNitProveedorExtranjero($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
		$datos_acta = $this->GetDatosActaInternacional($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
		$costos_productos = $this->CalcularCostosProductosParciales($datos['Productos'], $tasa_orden, $datos['Modelo']['Tasa_Cambio']);
		$acta_data = $this->GetNitGastosActa($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
		$productos_recalculados = $this->RecalculoProductosConTasaDeOrden($tasa_orden, $datos['Productos'], $datos['Porcentaje_Flete_Internacional'], $datos['Porcentaje_Seguro_Internacional'], $datos['Otros_Gastos'], $datos['Tasa_Dolar_Parcial'], $id_nacionalizacion);
		$diferencia_cambio = $this->CalcularDiferenciaAlCambio($datos['Productos'], $productos_recalculados, $tasa_orden, $datos['Modelo']['Tasa_Cambio'], $datos['Modelo'], $costos_productos);
		$totales_parcial = $this->ObtenerTotalesRealesParcial($costos_productos, $datos['Modelo'], $datos['Otros_Gastos'], $diferencia_cambio);

		//var_dump($costos_productos); 
		//echo "==================================<br>";
		//var_dump($diferencia_cambio); 
		//echo "==================================<br>";
		//var_dump($totales_parcial);  
		//exit;

		// echo json_encode($diferencia_cambio); exit;
		//GUARDAR GASTOS ADICIONADOS AL PARCIAL			
		$this->GuardarCostosObligatorios($datos['Modelo']);

		//GUARDAR TOTALES RECALCULADOS CON TASA DE LA ORDEN
		foreach ($diferencia_cambio as $key => $value) {

			if ($key == 'total_excento_recalculado') {
				if ($value != 0) {
					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 0');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = round(number_format($value, 2, ".", ""));
					$oItem->Debe_Niif = round(number_format($value, 2, ".", ""));
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = $nit_proveedor_orden;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor gravado 0 recalculado con tasa de orden";

					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'total_gravado_recalculado') {
				if ($value != 0) {

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 19');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = round(number_format($value, 2, ".", ""));
					$oItem->Debe_Niif = round(number_format($value, 2, ".", ""));
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = $nit_proveedor_orden;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor gravado 19 recalculado con tasa de orden";

					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'total_diferencia_excento') {
				if ($value != 0) {
					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 0');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = number_format($value * -1, 2, ".", ""); //abs()
					$oItem->Debe_Niif = number_format($value * -1, 2, ".", ""); // abs()
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = 804016084;
					$oItem->Tipo_Nit = 'Empresa';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Diferencia en Cambio - Productos Excentos";

					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'total_diferencia_gravado') {
				if ($value != 0) {

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 19');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = number_format($value * -1, 2, ".", ""); // abs() 
					$oItem->Debe_Niif = number_format($value * -1, 2, ".", ""); // abs()
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = 804016084;
					$oItem->Tipo_Nit = 'Empresa';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Diferencia en Cambio - Productos Gravados";

					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'total_excento_costo') {
				if ($value != 0) {
					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('productos excluidos');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($value, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($value, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = $nit_proveedor_orden;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor productos excluidos recalculado con tasa de orden";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'total_gravado_costo') {
				if ($value != 0) {
					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('productos gravados');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($value, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($value, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = $nit_proveedor_orden;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor productos gravados recalculado con tasa de orden";

					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			}
		}

		if ($datos['Modelo']['Descuento_Parcial'] != '0') {

			if ($diferencia_cambio["porcentaje_excluido"] != 0) {
				$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 0');

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = round(number_format($datos['Modelo']['Descuento_Parcial'] * $diferencia_cambio["porcentaje_excluido"], 2, ".", ""));
				$oItem->Haber_Niif = round(number_format($datos['Modelo']['Descuento_Parcial'] * $diferencia_cambio["porcentaje_excluido"], 2, ".", ""));
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $nit_proveedor_orden;
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $datos['Modelo']['Codigo'];
				$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
				$oItem->Detalles = "Descuento arancelario";

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
				$oItem->save();
				unset($oItem);
			}
			if ($diferencia_cambio["porcentaje_gravado"] != 0) {
				$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 19');

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = round(number_format($datos['Modelo']['Descuento_Parcial'] * $diferencia_cambio["porcentaje_gravado"], 2, ".", ""));
				$oItem->Haber_Niif = round(number_format($datos['Modelo']['Descuento_Parcial'] * $diferencia_cambio["porcentaje_gravado"], 2, ".", ""));
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $nit_proveedor_orden;
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $datos['Modelo']['Codigo'];
				$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
				$oItem->Detalles = "Descuento arancelario";

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
				$oItem->save();
				unset($oItem);
			}

			$plan_cuenta_descuento = $this->BuscarInformacionParaMovimiento('descuento arancelario');

			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $plan_cuenta_descuento['Id_Plan_Cuenta'];
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = round(number_format($datos['Modelo']['Descuento_Parcial'], 2, ".", ""));
			$oItem->Debe_Niif = round(number_format($datos['Modelo']['Descuento_Parcial'], 2, ".", ""));
			$oItem->Haber = "0";
			$oItem->Haber_Niif = "0";
			$oItem->Nit = $nit_proveedor_orden;
			$oItem->Tipo_Nit = 'Proveedor';
			$oItem->Documento = $datos['Modelo']['Codigo'];
			$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
			$oItem->Detalles = "Descuento arancelario";

			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
			$oItem->save();
			unset($oItem);
		}

		/*
			foreach ($totales_parcial as $key => $costo) {
				if ($key == 'excluidos') {
					if ($costo > 0) {					
						$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 0');
					
						$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = round(number_format($costo, 2, ".", ""));
						$oItem->Debe_Niif = round(number_format($costo, 2, ".", ""));
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $nit_proveedor_orden;
						$oItem->Tipo_Nit = 'Proveedor';
						$oItem->Documento = $datos['Modelo']['Codigo'];
						$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
						$oItem->Detalles = "Valor gravado 0, acumulado de productos";
						
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
						$oItem->save();
						unset($oItem);
					}					
				}elseif ($key == 'gravados') {
					if ($costo > 0) {
						$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 19');
					
						$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
						$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
						$oItem->Id_Modulo = $this->id_modulo;
						$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
						$oItem->Debe = round(number_format($costo, 2, ".", ""));
						$oItem->Debe_Niif = round(number_format($costo, 2, ".", ""));
						$oItem->Haber = "0";
						$oItem->Haber_Niif = "0";
						$oItem->Nit = $nit_proveedor_orden;
						$oItem->Tipo_Nit = 'Proveedor';
						$oItem->Documento = $datos['Modelo']['Codigo'];
						$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
						$oItem->Detalles = "Valor gravado 19, acumulado de productos";
						
						if ($this->save_fecha)
							$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
						$oItem->save();
						unset($oItem);
					}					
				}
			}
*/
		foreach ($costos_productos as $key => $costo) {
			if ($key == 'iva') {
				if ($costo > 0) {
					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('iva nacionalizacion 19');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Debe = round(number_format($costo, 2, ".", ""));
					$oItem->Debe_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";
					$oItem->Nit = $nit_proveedor_orden;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor iva nacionalizacion 19";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($costo, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = 800197268;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor iva nacionalizacion 19, contraparte";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'arancel') {
				if ($costo > 0) {
					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($costo, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = 800197268;
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor acumulado aranceles";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'flete_internacional') {
				if ($costo > 0) {

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($costo, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = $acta_data['Tercero_Flete_Internacional'];
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor acumulado flete internacional";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'seguro_internacional') {
				if ($costo > 0) {

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($costo, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = $acta_data['Tercero_Seguro_Internacional'];
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor acumulado seguro internacional";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'flete_nacional') {
				if ($costo > 0) {

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($costo, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = $acta_data['Tercero_Flete_Nacional'];
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor flete nacional";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			} elseif ($key == 'licencia_importacion') {
				if ($costo > 0) {

					$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

					$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $this->id_modulo;
					$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
					$oItem->Haber = round(number_format($costo, 2, ".", ""));
					$oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
					$oItem->Debe = "0";
					$oItem->Debe_Niif = "0";
					$oItem->Nit = $acta_data['Tercero_Licencia_Importacion'];
					$oItem->Tipo_Nit = 'Proveedor';
					$oItem->Documento = $datos['Modelo']['Codigo'];
					$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
					$oItem->Detalles = "Valor licencia importacion";
					if ($this->save_fecha)
						$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
					$oItem->save();
					unset($oItem);
				}
			}
		}

		$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

		foreach ($datos['Otros_Gastos'] as $gasto) {
			if ($gasto['Monto_Gasto'] > 0) {

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = round(number_format(floatval($gasto['Monto_Gasto']), 2, ".", ""));
				$oItem->Haber_Niif = round(number_format(floatval($gasto['Monto_Gasto']), 2, ".", ""));
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $gasto['Id_Proveedor'];
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $datos['Modelo']['Codigo'];
				$oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
				$oItem->Detalles = "Valor otros gastos: " . $gasto['Concepto_Gasto'];
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
				$oItem->save();
				unset($oItem);
			}
		}

		//GUARDAR DIFERENCIA AL CAMBIO

	}

	private function GetNitGastosActa($id_acta)
	{

		$query = '
				SELECT
					Tercero_Flete_Internacional,
					Tercero_Seguro_Internacional,
					Tercero_Flete_Nacional,
					Tercero_Licencia_Importacion
				FROM Acta_Recepcion_Internacional
				WHERE
					Id_Acta_Recepcion_Internacional = ' . $id_acta;

		$this->queryObj->SetQuery($query);
		$acta_data = $this->queryObj->ExecuteQuery('simple');
		return $acta_data;
	}

	private function GetNitProh()
	{

		$query = '
				SELECT
					NIT
				FROM Configuracion
				WHERE
					Id_Configuracion = 1';

		$this->queryObj->SetQuery($query);
		$nit = $this->queryObj->ExecuteQuery('simple');
		return $nit['NIT'];
	}

	private function CalcularCostosProductosParciales($productos, $tasa_orden, $tasa_parcial)
	{;

		$gastos_productos_excluidos = 0;
		$gastos_productos_gravados = 0;
		$gastos_iva = 0;
		$gastos_aranceles = 0;
		$gastos_flete_internacional = 0;
		$gastos_seguro_internacional = 0;
		$gastos_flete_nacional = 0;
		$gastos_licencia_importacion = 0;
		$gastos_productos = array('excluidos' => 0, 'gravados' => 0, 'iva' => 0, 'arancel' => 0, 'flete_internacional' => 0, 'seguro_internacional' => 0,  'flete_nacional' => 0, 'licencia_importacion' => 0,);

		foreach ($productos as $p) {


			$fob = $p['Recalculo']['Cantidad'] * $p['Recalculo']['Usd'];

			$fi = $fob * $p['Recalculo']['FI'];
			$si = $fob * $p['Recalculo']['SI'];

			$total_cif_tasa_nac = ($fob + $fi + $si) * $tasa_parcial;
			$total_cif_tasa_giro = ($fob + $fi + $si) * $tasa_orden;

			if ($p['Gravado'] == '0') {
				$gastos_productos_excluidos += floatval($p['Precio_Unitario_Final'] * $p['Cantidad']);
			} else {
				$gastos_productos_gravados += floatval($p['Precio_Unitario_Final'] * $p['Cantidad']);
				$gastos_iva += floatval($p['Total_Iva']);
			}
			$fob_pesos = $fob * $tasa_parcial;
			//echo "tasa parcial= ".$tasa_parcial."+++";
			//echo "tasa orden= ".$tasa_orden."+++";
			//echo "FOB= ".$fob_pesos."+++";
			//echo "Flete= ".$p['Total_Flete']."+++";
			//echo "Seguro= ".$p['Total_Seguro']."+++"; 
			//echo "Porcentaje_Arancel= ".$p['Porcentaje_Arancel']."+++";
			$gastos_aranceles += floatval(($fob_pesos + $p['Total_Flete'] + $p['Total_Seguro']) * $p['Porcentaje_Arancel'] / 100);
			$gastos_flete_internacional += floatval($fi * $tasa_orden);
			$gastos_seguro_internacional += floatval($si * $tasa_orden);
			$gastos_flete_nacional += floatval($total_cif_tasa_giro * $p['Adicional_Flete_Nacional']);
			$gastos_licencia_importacion += floatval($total_cif_tasa_giro * $p['Adicional_Licencia_Importacion']);
		}

		$gastos_productos['excluidos'] = $gastos_productos_excluidos;
		$gastos_productos['gravados'] = $gastos_productos_gravados;
		$gastos_productos['iva'] = $gastos_iva;
		$gastos_productos['arancel'] = $gastos_aranceles;
		$gastos_productos['flete_nacional'] = $gastos_flete_nacional;
		$gastos_productos['licencia_importacion'] = $gastos_licencia_importacion;
		$gastos_productos['flete_internacional'] = $gastos_flete_internacional;
		$gastos_productos['seguro_internacional'] = $gastos_seguro_internacional;

		return $gastos_productos;
	}

	private function GuardarCostosObligatorios($modelo)
	{

		$plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

		foreach ($modelo as  $key => $value) {

			if ($key == 'Tramite_Sia' || $key == 'Formulario' || $key == 'Cargue' ||  $key == 'Gasto_Bancario') {

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Haber = round(number_format($value, 2, ".", ""));
				$oItem->Haber_Niif = round(number_format($value, 2, ".", ""));
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Nit = $modelo['Tercero_' . $key];
				$oItem->Tipo_Nit = 'Proveedor';
				$oItem->Documento = $modelo['Codigo'];
				$oItem->Numero_Comprobante = $modelo['Codigo'];
				$oItem->Detalles = "Valor " . str_replace("_", " ", $key);
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function CalcularCostosOtrosGastos($otros_gastos)
	{
		$total_otros_gastos = 0;
		$detalle_pago = 'Gastos por conceptos: ';
		$detalles_otros_gastos = array('total' => 0, 'detalles' => '');

		foreach ($otros_gastos as $og) {

			$total_otros_gastos += floatval($og['Monto_Gasto']);
			$detalle_pago .= $og['Concepto_Gasto'] . ', ';
		}

		$detalles_otros_gastos['total'] = $total_otros_gastos;
		$detalles_otros_gastos['detalles'] = trim($detalle_pago, ", ");

		return $detalles_otros_gastos;
	}

	private function GetNitProveedorExtranjero($id_acta)
	{
		global $queryObj;

		$query = '
				SELECT
					OCI.Id_Proveedor
				FROM Orden_Compra_Internacional OCI
				INNER JOIN Acta_Recepcion_Internacional ARI ON OCI.Id_Orden_Compra_Internacional = ARI.Id_Orden_Compra_Internacional
				WHERE
					ARI.Id_Acta_Recepcion_Internacional = ' . $id_acta;

		$this->queryObj->SetQuery($query);
		$proveedor = $this->queryObj->ExecuteQuery('simple');
		return $proveedor['Id_Proveedor'];
	}

	private function GetDatosActaInternacional($id_acta)
	{
		global $queryObj;

		$query = 'SELECT SUM(PARI.Subtotal*OC.Tasa_Dolar) AS Total, P.Gravado
                        FROM Producto_Acta_Recepcion_Internacional PARI
                        INNER JOIN Producto P ON PARI.Id_Producto = P.Id_Producto
                        INNER JOIN Acta_Recepcion_Internacional AR ON AR.Id_Acta_Recepcion_Internacional=PARI.Id_Acta_Recepcion_Internacional
                        INNER JOIN Orden_Compra_Internacional OC ON OC.Id_Orden_Compra_Internacional = AR.Id_Orden_Compra_Internacional 
                        WHERE PARI.Id_Acta_Recepcion_Internacional = ' . $id_acta . '
                        GROUP BY P.Gravado;';

		$this->queryObj->SetQuery($query);
		$datos_acta = $this->queryObj->ExecuteQuery('multiple');
		return $datos_acta;
	}

	private function RecalculoProductosConTasaDeOrden($tasa_orden, $productos, $flete, $seguro, $otros_gastos, $tasa_parcial, $id_parcial)
	{
		$nuevos_productos = array();
		$adicional_otros_gastos = $this->GetAdicionalOtrosGastos($otros_gastos, $id_parcial);

		$i = 0;
		foreach ($productos as $p) {

			$p_nuevo = array();

			$p_nuevo['Tasa_Orden'] = $tasa_orden;
			$p_nuevo['Tasa_nac'] = $tasa_parcial;
			$p_nuevo['%flete'] = $flete;
			$p_nuevo['%seguro'] = $seguro;
			$p_nuevo['precio'] = $p['Precio'];

			$p_nuevo['Precio_Unitario'] = $this->ConversionPrecioDolarAPesos(floatval($p['Precio']), $tasa_parcial);
			$p_nuevo['Valor_Inicial_Mercancia'] = $p_nuevo['Precio_Unitario'] * $p['Cantidad'];
			$p_nuevo['FOT_Pesos'] = $this->CalcularFotPesos($p_nuevo['Precio_Unitario'], $flete, $seguro);
			$p_nuevo['Precio_Unitario_Final'] = $this->CalcularPrecioUnitarioFinal($p_nuevo['FOT_Pesos'], floatval(trim($p['Porcentaje_Arancel'], " %")));
			$p_nuevo['Cantidad'] = $p['Cantidad'];
			$p_nuevo['Id_Producto'] = $p['Id_Producto'];
			$p_nuevo['Nombre_Producto'] = $p['Nombre_Producto'];
			$p_nuevo['Lote'] = $p['Lote'];
			$p_nuevo['Porcentaje_Arancel'] = $p['Porcentaje_Arancel'];
			$p_nuevo['Gravado'] = $p['Gravado'];
			$p_nuevo['Subtotal'] = ($p_nuevo['Precio_Unitario_Final']  * $p['Cantidad']); // * (1+ $p['Gravado']/100);
			$p_nuevo['Subtotal_Final'] = $p_nuevo['Subtotal'] + ($p['Adicional_Flete_Nacional'] * $p['Cantidad']) + ($p['Adicional_Licencia_Importacion'] * $p['Cantidad']);
			$p_nuevo['Subtotal_Final'] = $p_nuevo['Subtotal_Final'] + ($p['Cantidad'] * $adicional_otros_gastos);

			array_push($nuevos_productos, $p_nuevo);

			$i++;
		}

		return $nuevos_productos;
	}

	private function ObtenerTotalesRealesParcial($totalesGastos, $gastosVariosParcial, $otrosGastos, $valoresDiferencia)
	{


		$total_excluido = 0;
		$total_gravado = 0;
		$totales_parcial = array('excluidos' => 0, 'gravados' => 0);
		// echo json_encode($otrosGastos); exit;
		foreach ($totalesGastos as $key => $value) {
			if (($key != "excluidos" && $key != "gravados" && $key != "iva") && $value != 0) {
				$total_excluido += $value * $valoresDiferencia['porcentaje_excluido'];
				$total_gravado += $value * $valoresDiferencia['porcentaje_gravado'];
			}
		}

		foreach ($otrosGastos as $gasto) {
			if ($gasto['Monto_Gasto'] != 0) {
				$total_excluido += $gasto['Monto_Gasto'] * $valoresDiferencia['porcentaje_excluido'];
				$total_gravado += $gasto['Monto_Gasto'] * $valoresDiferencia['porcentaje_gravado'];
			}
		}

		foreach ($gastosVariosParcial as  $key => $value) {
			if (($key == 'Tramite_Sia' || $key == 'Formulario' || $key == 'Cargue' ||  $key == 'Gasto_Bancario') && $value != 0) {

				$total_excluido += $value * $valoresDiferencia['porcentaje_excluido'];
				$total_gravado += $value * $valoresDiferencia['porcentaje_gravado'];
			}
		}

		$total_excluido += abs($valoresDiferencia['diferencia']) * $valoresDiferencia['porcentaje_excluido'];
		$total_gravado += abs($valoresDiferencia['diferencia']) * $valoresDiferencia['porcentaje_gravado'];

		$totales_parcial['excluidos'] = $total_excluido;
		$totales_parcial['gravados'] = $total_gravado;

		return $totales_parcial;
	}

	private function GetConteoProductosParcial($id_parcial)
	{
		$query = '
	            SELECT 
	                SUM(Cantidad) AS Total
	            FROM Producto_Nacionalizacion_Parcial
	            WHERE
	                Id_Nacionalizacion_Parcial = ' . $id_parcial;

		//var_dump($id_parcial);
		$this->queryObj->SetQuery($query);
		$conteo = $this->queryObj->ExecuteQuery('simple');

		if ($conteo['Total']) {
			return floatval($conteo['Total']);
		} else {
			return 0;
		}
	}

	private function GetAdicionalOtrosGastos($gastos, $id_parcial)
	{
		$adicional_final = 0;
		$total_cantidad_productos = $this->GetConteoProductosParcial($id_parcial);


		foreach ($gastos as $gasto) {

			$monto = floatval($gasto['Monto_Gasto']);
			$adicional_gasto = $monto / $total_cantidad_productos;
			$adicional_final += $adicional_gasto;
		}

		return $adicional_final;
	}

	private function ConversionPrecioDolarAPesos($precio, $tasa)
	{
		$conversion = $precio * $tasa;
		return $conversion;
	}

	private function CalcularFotPesos($precio_unitario, $flete, $seguro)
	{
		$valor_flete = $precio_unitario * $flete;
		$valor_seguro = $precio_unitario * $seguro;
		$fot = $precio_unitario + $valor_flete + $valor_seguro;
		return $fot;
	}

	private function CalcularPrecioUnitarioFinal($fot_pesos, $arancel)
	{
		$puf = $fot_pesos * (1 + $arancel / 100);
		return $puf;
	}

	private function CalcularDiferenciaAlCambio($productos, $productos_recalculados, $tasa_orden, $tasa_parcial, $modelo, $costos_productos)
	{
		//var_dump()
		$total = 0;
		$total_INICIAL = 0;
		$total_recalculados = 0;
		$total_cif_tasa_nac = 0;
		$total_cif_tasa_giro = 0;
		// echo $tasa_orden; exit;
		$total_gravado_recalculado = 0;
		$total_excento_recalculado = 0;
		$total_excento_costo = 0;
		$total_gravado_costo = 0;

		$total_otros = 0;

		$result = array('diferencia' => 0, 'cuenta' => '', 'total_excento_recalculado' => 0, 'total_gravado_recalculado' => 0, 'porcentaje_excluido' => 0, 'porcentaje_gravado' => 0, 'total_excento_costo' => 0, 'total_gravado_costo' => 0, 'total_diferencia_excento' => 0, 'total_diferencia_gravado' => 0);

		foreach ($modelo as  $key => $value) {
			if ($key == 'Tramite_Sia' || $key == 'Formulario' || $key == 'Cargue' ||  $key == 'Gasto_Bancario') {
				$total_otros += round(number_format($value, 4, ".", ""));
			}
		}
		foreach ($costos_productos as  $key => $value) {
			if ($key == 'flete_nacional' || $key == 'licencia_importacion') {
				$total_otros += round(number_format($value, 4, ".", ""));
			}
		}

		foreach ($productos as $p) {
			$fob = $p['Recalculo']['Cantidad'] * $p['Recalculo']['Usd'];

			$fi = $fob * $p['Recalculo']['FI'];
			$si = $fob * $p['Recalculo']['SI'];

			$total_cif_tasa_nac += ($fob + $fi + $si) * $tasa_parcial;
			$total_cif_tasa_giro += ($fob + $fi + $si) * $tasa_orden;
			//echo $pr['Gravado']."--------";
			if ($p['Gravado'] == '0') {
				$total_excento_costo += $fob * $tasa_orden;
			} else {
				$total_gravado_costo += $fob * $tasa_orden;
			}


			$total += floatval($p['Cantidad']) * floatval($p['Precio_Unitario_Final']);

			/*var_dump('total 1',$total);
				var_dump('tasa parcial',$tasa_parcial);
				var_dump('cantidad',$p['Cantidad']);
				var_dump('precio',floatval($p['Precio']));*/
		}

		$dif = $total_cif_tasa_nac - $total_cif_tasa_giro;


		foreach ($productos_recalculados as $pr) {
			$total_INICIAL += $pr['Valor_Inicial_Mercancia'];
			if ($pr['Gravado'] == '0') {
				$total_excento_recalculado += floatval($pr['Subtotal']);
			} else {
				$total_gravado_recalculado += floatval($pr['Subtotal']);
			}

			$total_recalculados += floatval($pr['Subtotal']);
		}


		$result['porcentaje_excluido'] = (($total_excento_recalculado * 100) / $total_recalculados) / 100;
		$result['porcentaje_gravado'] = (($total_gravado_recalculado * 100) / $total_recalculados) / 100;

		if (floatval($tasa_orden) < floatval($tasa_parcial)) {
			$result['cuenta'] = 'diferencia ingreso';
		} elseif (floatval($tasa_orden) > floatval($tasa_parcial)) {
			$result['cuenta'] = 'diferencia gasto';
		}

		$result['diferencia'] = $dif;
		$result['Tr'] = $total_INICIAL;
		$result['TT'] = $total;

		$result['total_excento_recalculado'] = $total_excento_recalculado + ($total_otros * $result['porcentaje_excluido']);
		$result['total_gravado_recalculado'] = $total_gravado_recalculado + ($total_otros * $result['porcentaje_gravado']);

		$result['total_diferencia_excento'] = $dif * $result['porcentaje_excluido'];
		$result['total_diferencia_gravado'] = $dif * $result['porcentaje_gravado'];

		$result['total_excento_costo'] = $total_excento_costo;
		$result['total_gravado_costo'] = $total_gravado_costo;
		return $result;
	}

	/*FIN PARCIAL ACTA RECEPCION INTERNACIONAL*/

	/*METODOS GENERALES*/

	private function GetProductosFactura($idFactura)
	{
		#CAMBIOS REALIZADOS POR CARLOS CARDONA - NUEVO MODELO INVENTARIO - COSTO PROMEDIO
		$query = '
		 		SELECT
		 			PF.Id_Factura,
					PF.Id_Producto_Factura,
					PF.Id_Producto,
					PF.Cantidad,
					PF.Precio,
					PF.Descuento,
					PF.Impuesto,
					PF.Subtotal,
					(PF.Subtotal - (PF.Cantidad*PF.Descuento)) AS Subtotal_Con_Descuento,
					COALESCE( ( SELECT IF( Costo !=0 , Costo , null)  FROM Producto_Dispensacion WHERE Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion),
					            (SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = PF.Id_Producto) ,0  ) AS Costo_Promedio
			
				FROM Producto_Factura PF
		 		WHERE
		 			PF.Id_Factura = ' . $idFactura;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('multiple');

		return $result;
	}

	private function GetCostosProductosNotaCredito($productos_factura)
	{

		$in_condition = $this->ArmarInProductos($productos_factura);

		$query = '
		 		SELECT
		 			PF.Id_Factura_Venta,
					PF.Id_Producto_Factura_Venta,
					PF.Id_Producto,
					PF.Cantidad,
					PF.Precio_Venta,
					PF.Impuesto,
					PF.Subtotal,
					(PF.Subtotal) AS Subtotal_Con_Descuento,
					
					COALESCE( ( SELECT Costo FROM Producto_Remision WHERE Id_Producto_Factura_Venta = PF.Id_Producto_Factura_Venta Group by Id_Producto_Factura_Venta),
					            (SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = PF.Id_Producto) ,0  ) AS Costo_Promedio
    				FROM Producto_Factura_Venta PF
    		 		WHERE
					PF.Id_Producto IN (' . $in_condition . ') GROUP BY PF.Id_Producto';


		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('multiple');

		return $result;
	}

	private function GetTotalesFactura($productos, $cuota_moderadora)
	{
		try {
			$total_general_facturas = 0;
			$ivas_factura = array();
			$result = array();

			if (!is_array($productos)) {
				$this->logError('GetTotalesFactura: productos no es un array', ['cuota_moderadora' => $cuota_moderadora]);
				return [
					'Total_General' => 0,
					'Ivas' => []
				];
			}

			foreach ($productos as $p) {
				if (!is_array($p)) {
					continue;
				}

				$subtotal = isset($p['Subtotal_Con_Descuento']) ? floatval($p['Subtotal_Con_Descuento']) : 0;
				$total_general_facturas += $subtotal;

				$impuesto = isset($p['Impuesto']) ? floatval($p['Impuesto']) : 0;

				if ($impuesto > 0) {
					$total_iva_producto = $subtotal * ($impuesto / 100);
					$total_general_facturas += $total_iva_producto;

					$impuesto_key = (string)$p['Impuesto'];
					if (!isset($ivas_factura[$impuesto_key])) {
						$ivas_factura[$impuesto_key] = $total_iva_producto;
					} else {
						$ivas_factura[$impuesto_key] += $total_iva_producto;
					}
				}
			}

			$cuota_moderadora = isset($cuota_moderadora) ? floatval($cuota_moderadora) : 0;
			$total_general_facturas -= $cuota_moderadora;

			$result['Total_General'] = $total_general_facturas;
			$result['Ivas'] = $ivas_factura;

			return $result;
		} catch (Exception $e) {
			$this->logError('GetTotalesFactura: excepción', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'cuota_moderadora' => $cuota_moderadora
			]);
			return [
				'Total_General' => 0,
				'Ivas' => []
			];
		}
	}

	private function TotalIvaProductos($productos, $tipo_calculo, $nroFactura = '', $cuota_moderadora = 0, $clase_ajuste = null)
	{

		$total_productos_iva_19 = 0;
		$total_productos_iva_5 = 0;
		$total_productos_iva_0 = 0;

		$facturas_iva_19 = '';
		$facturas_iva_5 = '';
		$facturas_iva_0 = '';
		$facturas = [];
		$total_gravado = [];

		$gravados_cargar = array();

		if ($tipo_calculo == 'acta') {



			foreach ($productos as $value) {

				foreach ($value['producto'] as $v) {

					if ($v['Factura']) {

						if ($v['Impuesto'] == "19") {

							$iva_producto = floatval($v['Cantidad']) * floatval($v['Precio']);
							$facturas[$v['Factura']]['iva_19'] += $iva_producto;
						}

						if ($v['Impuesto'] == "0") {

							$iva_producto = floatval($v['Cantidad']) * floatval($v['Precio']);
							$facturas[$v['Factura']]['iva_0'] += $iva_producto;
						}
					}
				}



				## ARMANDO TOTALES GRAVADOS

			}


			foreach ($facturas as $key => $ivas) {

				foreach ($ivas as $k => $value) {

					if (isset($total_gravado[$k])) {
						$total_gravado[$k] += $value;
					} else {
						$total_gravado[$k] = $value;
					}
				}
			}

			foreach ($total_gravado as $k => $value) {
				/*echo "<pre>";
                      var_dump($k);
                    var_dump($value);
                    echo "</pre>";*/
				$asociacion = $this->BuscarInformacionParaMovimiento($k == 'iva_0' ? 'gravado 0' : 'gravado 19');
				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = round($value);
				$oItem->Debe_Niif = round($value);
				$oItem->Haber = "0";
				$oItem->Haber_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $key;
				$oItem->Numero_Comprobante = $this->numero_comprobante;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Acta_Recepcion');
				$oItem->save();
				unset($oItem);
			}


			/* echo "<pre>";
                    var_dump($total_gravado);
                    echo "</pre>";*/


			$facturas = [];
			$total_gravado = [];
			// var_dump($iva_producto);exit;

		}




		if ($tipo_calculo == 'factura venta') {

			foreach ($productos as $p) {

				if ($p['Impuesto'] == "19") {

					$iva_producto = floatval($p['Cantidad']) * floatval($p['Precio_Venta']) * (1 - floatval($p['Descuento']) / 100);
					$total_productos_iva_19 += $iva_producto;

					$facturas_iva_19 .= $p['Factura'] . ", ";
					if (!in_array('19', $gravados_cargar)) {
						array_push($gravados_cargar, '19');
					}
				}

				if ($p['Impuesto'] == "5") {

					$iva_producto = floatval($p['Cantidad']) * floatval($p['Precio_Venta']) * (1 - floatval($p['Descuento']) / 100);
					$total_productos_iva_5 += $iva_producto;

					$facturas_iva_5 .= $p['Factura'] . ", ";
					if (!in_array('5', $gravados_cargar)) {
						array_push($gravados_cargar, '5');
					}
				}

				if ($p['Impuesto'] == "0") {

					$iva_producto = floatval($p['Cantidad']) * floatval($p['Precio_Venta']) * (1 - floatval($p['Descuento']) / 100);
					$total_productos_iva_0 += $iva_producto;

					$facturas_iva_0 .= $p['Factura'] . ", ";
					if (!in_array('0', $gravados_cargar)) {
						array_push($gravados_cargar, '0');
					}
				}
			}

			foreach ($gravados_cargar as $value) {
				$busqueda = '';
				$total_cargar = 0;
				$facturas_cargar = '';

				if ($value == '0') {
					$busqueda = 'gravado 0';
					$total_cargar = $total_productos_iva_0;
					$facturas_cargar = $facturas_iva_0;
				} elseif ($value == '5') {

					$busqueda = 'gravado 5';
					$total_cargar = $total_productos_iva_5;
					$facturas_cargar = $facturas_iva_5;
				} elseif ($value == '19') {

					$busqueda = 'gravado 19';
					$total_cargar = $total_productos_iva_19;
					$facturas_cargar = $facturas_iva_19;
				}

				$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($total_cargar, 2, ".", "");
				$oItem->Haber_Niif = number_format($total_cargar, 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura_Venta');
				$oItem->save();
				unset($oItem);
			}
		}

		if ($tipo_calculo == 'factura') {

			$gravados_cargar = $this->CalcularTotalesGravadosProductos($productos, 'factura');

			foreach ($gravados_cargar as $key => $value) {
				$busqueda = '';
				$total_cargar = $value;
				$facturas_cargar = $nroFactura;

				if ($key == '0') {
					$busqueda = 'gravado 0';
				} elseif ($key == '5') {

					$busqueda = 'gravado 5';
				} elseif ($key == '19') {

					$busqueda = 'gravado 19';
				}



				$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = "0";
				$oItem->Debe_Niif = "0";
				$oItem->Haber = number_format($total_cargar, 2, ".", "");
				$oItem->Haber_Niif = number_format($total_cargar, 2, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Factura');
				$oItem->save();
				unset($oItem);
			}
		}

		if ($tipo_calculo == 'nota credito') {

			$gravados_cargar = $this->CalcularTotalesGravadosProductos($productos, 'devolucion');

			foreach ($gravados_cargar as $key => $value) {

				$busqueda = 'gravado ' . $key;
				$total_cargar = $value;

				$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);

				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = number_format($total_cargar, 2, ".", "");
				$oItem->Haber = "0";
				$oItem->Debe_Niif = number_format($total_cargar, 2, ".", "");
				$oItem->Haber_Niif = "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
					$oItem->Id_Centro_Costo = $this->centro_costo;

				if ($this->save_fecha) {
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nota_Credito');
				} else {
					$oItem->Fecha_Movimiento = $this->fecha_movimiento;
				}
				$oItem->save();
				unset($oItem);
			}
		}

		if ($tipo_calculo == 'ajuste Entrada' || $tipo_calculo == 'ajuste Salida') {


			$gravados_cargar = $this->CalcularTotalesGravadosProductos($productos, 'ajuste');

			foreach ($gravados_cargar as $key => $value) {

				$busqueda = 'gravado ' . $key;
				$busqueda2 = 'gravado costo ' . $key;
				$total_cargar = $value;

				if ($tipo_calculo == 'ajuste Entrada' && ($clase_ajuste == 'Bonificacion' || $clase_ajuste == 'Sobrante')) {
					$busqueda2 = $clase_ajuste;
				}
				if ($clase_ajuste == 'Ajuste Valorizado') {
					$busqueda .= " $clase_ajuste";
					$busqueda2 .= " $clase_ajuste";
				}

				$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);

				$asociacion2 = $this->BuscarInformacionParaMovimiento($busqueda2);


				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $tipo_calculo == 'ajuste Entrada' ? number_format($total_cargar, 0, ".", "") : "0";
				$oItem->Haber =  $tipo_calculo == 'ajuste Entrada' ? "0" : number_format($total_cargar, 0, ".", "");
				$oItem->Debe_Niif = $tipo_calculo == 'ajuste Entrada' ? number_format($total_cargar, 0, ".", "") : "0";
				$oItem->Haber_Niif =  $tipo_calculo == 'ajuste Entrada' ? "0" : number_format($total_cargar, 0, ".", "");
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Ajuste_Individual');


				$oItem->save();
				unset($oItem);

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $asociacion2['Id_Plan_Cuenta'];
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $tipo_calculo == 'ajuste Entrada' ? "0" : number_format($total_cargar, 0, ".", "");
				$oItem->Haber = $tipo_calculo == 'ajuste Entrada' ? number_format($total_cargar, 0, ".", "") : "0";
				$oItem->Debe_Niif = $tipo_calculo == 'ajuste Entrada' ? "0" : number_format($total_cargar, 0, ".", "");
				$oItem->Haber_Niif = $tipo_calculo == 'ajuste Entrada' ? number_format($total_cargar, 0, ".", "") : "0";
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $nroFactura;
				$oItem->Numero_Comprobante = $nroFactura;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Ajuste_Individual');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function CalcularTotalesGravadosProductos($productos, $tipo)
	{
		$totales_gravados = array();

		if ($tipo == 'factura') {
			if (!is_array($productos)) {
				$this->logError('CalcularTotalesGravadosProductos: productos no es un array', ['tipo' => $tipo]);
				return $totales_gravados;
			}

			foreach ($productos as $p) {
				if (!is_array($p)) {
					continue;
				}

				$impuesto = isset($p['Impuesto']) ? $p['Impuesto'] : '0';
				$subtotal = isset($p['Subtotal_Con_Descuento']) ? floatval($p['Subtotal_Con_Descuento']) : 0;

				if (!isset($totales_gravados[$impuesto])) {
					$totales_gravados[$impuesto] = $subtotal;
				} else {
					$totales_gravados[$impuesto] += $subtotal;
				}
			}
		}

		if ($tipo == 'devolucion') {
			foreach ($productos as $p) {

				if (!$totales_gravados[$p['Impuesto']]) {
					$totales_gravados[$p['Impuesto']] = $p['Subtotal'];
				} else {
					$totales_gravados[$p['Impuesto']] += $p['Subtotal'];
				}
			}
		}

		if ($tipo == 'ajuste') {

			foreach ($productos as $p) {

				$gravado = (int)$this->GetGravadoProducto($p['Id_Producto']);

				if (! isset($totales_gravados[$gravado])) {
					$totales_gravados[$gravado] = floatval($p['Cantidad']) * floatval($p['Costo']);
				} else {
					$totales_gravados[$gravado] += floatval($p['Cantidad']) * floatval($p['Costo']);
				}
			}
		}


		return $totales_gravados;
	}

	private function GetGravadoProducto($idProducto)
	{
		$gravado = '';

		$query = '
				SELECT
					Gravado
				FROM Producto
				WHERE
					Id_Producto = ' . $idProducto;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		if ($result != false) {

			if ($result['Gravado'] == 'No') {

				$gravado = "0";
			} else {

				$gravado = "19";
			}
		}

		return $result != false ? $gravado : 'Error Gravado';
	}

	private function CalcularCostosProductosPorImpuesto($productos)
	{

		$costo_por_impuesto = array();

		if (!is_array($productos)) {
			$this->logError('CalcularCostosProductosPorImpuesto: productos no es un array');
			return $costo_por_impuesto;
		}

		foreach ($productos as $value) {
			if (!is_array($value)) {
				continue;
			}

			$costo_promedio = isset($value['Costo_Promedio']) ? floatval($value['Costo_Promedio']) : 0;
			$cantidad = isset($value['Cantidad']) ? intval($value['Cantidad']) : 0;
			$impuesto = isset($value['Impuesto']) ? $value['Impuesto'] : '0';

			$costo_producto = $costo_promedio * $cantidad;

			if (!isset($costo_por_impuesto[$impuesto])) {
				$costo_por_impuesto[$impuesto] = $costo_producto;
			} else {
				$costo_por_impuesto[$impuesto] += $costo_producto;
			}
		}

		return $costo_por_impuesto;
	}

	private function CalcularCostosProductosPorImpuestoFacturaVenta($productos)
	{

		$costo_por_impuesto = array();

		foreach ($productos as $value) {
			if (!isset($value['Impuesto'])) {
				continue;
			}
			$id_producto = isset($value['Id_Producto']) ? $value['Id_Producto'] : '';
			$id_prod_factura = isset($value['Id_Producto_Factura_Venta']) ? $value['Id_Producto_Factura_Venta'] : '';

			if ($id_producto == '') {
				$costo_producto = 0;
			} else {

				$prod = ['Id_Producto' => $id_producto, 'Id_Producto_Factura_Venta' => $id_prod_factura];

				$costo_producto = $this->GetCostoProducto($prod, 'Factura_Venta');
			}

			if (!isset($costo_por_impuesto[$value['Impuesto']])) {
				$costo_por_impuesto[$value['Impuesto']] = $costo_producto * intval($value['Cantidad']);
			} else {
				$costo_por_impuesto[$value['Impuesto']] += $costo_producto * intval($value['Cantidad']);
			}
		}

		return $costo_por_impuesto;
	}

	private function CalcularCostosProductosCapita($productos)
	{

		$costo = 0;
		$costo_producto = [];

		foreach ($productos as $value) {

			$costo = number_format($value['Subtotal'], 2, ".", "");

			if ($value['Gravado'] == 'Si') {

				$costo_producto['19'] += $costo;
			} else {
				$costo_producto['0'] += $costo;
			}
		}

		return $costo_producto;
	}

	private function CalcularCostosProductosNotaCredito($productos)
	{

		$costo = 0;

		foreach ($productos as $value) {

			if ($value['Gravado'] == 'Si') {

				$costo_producto = floatval($value['Costo']) * intval($value['Cantidad_Entregada']);
				$costo += $costo_producto;
			}
		}

		return $costo;
	}

	private function BuscarInformacionParaMovimiento($flag, $tipo = '', $debug = false)
	{
		try {
			$query = '';

			if ($tipo == 'facturas') {

				$query = '
					SELECT
						*
					FROM Asociacion_Plan_Cuentas
					WHERE
						Busqueda_Interna = "' . $flag . '"';
			} elseif ($tipo == '') {

				$query = '
						SELECT
							*
						FROM Asociacion_Plan_Cuentas
						WHERE
							Busqueda_Interna = "' . $flag . '" AND Id_Modulo = ' . $this->id_modulo;
			}

			if (empty($query)) {
				$this->logError('BuscarInformacionParaMovimiento: query vacío', ['flag' => $flag, 'tipo' => $tipo]);
				return false;
			}

			$this->queryObj->SetQuery($query);
			$result = $this->queryObj->ExecuteQuery('simple');

			if (!is_array($result) || empty($result)) {
				$this->logError('BuscarInformacionParaMovimiento: resultado vacío o no es array', [
					'flag' => $flag,
					'tipo' => $tipo,
					'id_modulo' => $this->id_modulo
				]);
				return false;
			}

			return $result;
		} catch (Exception $e) {
			$this->logError('BuscarInformacionParaMovimiento: excepción', [
				'error' => $e->getMessage(),
				'flag' => $flag,
				'tipo' => $tipo
			]);
			return false;
		}
	}

	private function BuscarTipoNit($nit)
	{
		try {
			if (empty($nit)) {
				$this->logError('BuscarTipoNit: NIT vacío');
				$this->tipo_nit = 'No se encontro';
				return;
			}

			$query_cliente = '
					SELECT
						*
					FROM Cliente
					WHERE
						Id_Cliente = ' . intval($nit);

			$this->queryObj->SetQuery($query_cliente);
			$cliente = $this->queryObj->ExecuteQuery('simple');

			if ($cliente != false && is_array($cliente)) {
				$this->tipo_nit = 'Cliente';
				return;
			}

			$query_proveedor = '
					SELECT
						*
					FROM Proveedor
					WHERE
						Id_Proveedor = ' . intval($nit);

			$this->queryObj->SetQuery($query_proveedor);
			$proveedor = $this->queryObj->ExecuteQuery('simple');

			if ($proveedor != false && is_array($proveedor)) {
				$this->tipo_nit = 'Proveedor';
				return;
			}

			$query_funcionario = '
					SELECT
						*
					FROM Funcionario
					WHERE
						Identificacion_Funcionario = ' . intval($nit);

			$this->queryObj->SetQuery($query_funcionario);
			$funcionario = $this->queryObj->ExecuteQuery('simple');

			if ($funcionario != false && is_array($funcionario)) {
				$this->tipo_nit = 'Funcionario';
				return;
			}

			$this->tipo_nit = 'No se encontro';
		} catch (Exception $e) {
			$this->logError('BuscarTipoNit: excepción', [
				'error' => $e->getMessage(),
				'nit' => $nit
			]);
			$this->tipo_nit = 'No se encontro';
		}
	}

	private function GetIdModulo($modulo)
	{
		try {
			if (empty($modulo)) {
				$this->logError('GetIdModulo: modulo vacío');
				$this->id_modulo = 'Error Modulo';
				return;
			}

			$query = '
					SELECT
						Id_Modulo
					FROM Modulo
					WHERE
						LOWER(Nombre) = "' . strtolower($modulo) . '"';

			$this->queryObj->SetQuery($query);
			$result = $this->queryObj->ExecuteQuery('simple');

			if ($result != false && is_array($result) && isset($result['Id_Modulo'])) {
				$this->id_modulo = $result['Id_Modulo'];
			} else {
				$this->logError('GetIdModulo: módulo no encontrado', ['modulo' => $modulo]);
				$this->id_modulo = 'Error Modulo';
			}
		} catch (Exception $e) {
			$this->logError('GetIdModulo: excepción', [
				'error' => $e->getMessage(),
				'modulo' => $modulo
			]);
			$this->id_modulo = 'Error Modulo';
		}
	}

	private function GetTipoFactura($idFactura)
	{
		$query = '
				SELECT
					F.*,
					TS.Id_Tipo_Servicio,
					TS.Nombre AS Tipo_Dispensacion,
					IFNULL(TS.Nombre, "No aplica") AS Tipo_Servicio
				FROM Factura F
				INNER JOIN Dispensacion D ON F.Id_Dispensacion = D.Id_Dispensacion
				LEFT JOIN Tipo_Servicio TS ON D.Id_Tipo_Servicio = TS.Id_Tipo_Servicio
				WHERE
					F.Id_Factura = ' . $idFactura;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result;
	}



	private function GetFacturaCapita($idFactura)
	{
		$query = '
				SELECT
					*,
					DATE(Fecha_Documento) AS Fecha_Sola
				FROM Factura_Capita 
				WHERE 
					Id_Factura_Capita = ' . $idFactura;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result;
	}

	private function GetFacturaAdministrativa($idFactura)
	{
		$query = '
				SELECT
					*,
					DATE(Fecha_Documento) AS Fecha_Sola
				FROM Factura_Administrativa 
				WHERE 
					Id_Factura_Administrativa = ' . $idFactura;
		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result;
	}



	private function GetDatosCapita($fecha, $idDepartamento)
	{
		$query = '
				SELECT
					Id_Factura_Capita,
					MIN(Fecha_Documento) AS Fecha_Doc,
					Mes,
					Codigo,
					Id_Departamento,
					Cuota_Moderadora
				FROM Factura_Capita 
				WHERE 
					DATE(Fecha_Documento) = "' . $fecha . '" AND Id_Departamento = ' . $idDepartamento . ' ORDER BY Id_Factura_Capita';

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result;
	}

	private function GetProductosFacturaCapita($idFactura)
	{
		$query = '
			SELECT
			D.Id_Factura AS Id_Factura_Capita,
			PD.Id_Producto_Dispensacion,
			PD.Id_Producto,
			PD.Cantidad_Entregada,
			P.Gravado,
			
			COALESCE( PD.Costo ,
				    (SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = PD.Id_Producto) ,0  ) AS Costo
				    
		
			FROM
			Producto_Dispensacion PD
			INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
			INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto
			WHERE D.Id_Tipo_Servicio = 7 AND D.Id_Factura = ' . $idFactura;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('multiple');

		foreach ($result as $i => $value) {
			$costo = $value['Costo'] * $value['Cantidad_Entregada'];
			$result[$i]['Subtotal'] = number_format($costo, 2, ".", "");
		}

		return $result;
	}

	private function GetProductosFacturaAdministrativa($idFactura)
	{
		$query = '
			SELECT 
			DA.Id_Plan_Cuenta,
			SUM(DA.Cantidad) AS Cantidades,
			SUM(DA.Cantidad*DA.Precio) AS Total_Precio,
			SUM( (DA.Cantidad*DA.Precio) - (DA.Cantidad*DA.Descuento) ) AS Total_Precio_Con_Descuento,
			SUM( DA.Cantidad*DA.Descuento ) AS T_Descuento,
			SUM( ( (DA.Cantidad*DA.Precio) - (DA.Cantidad*DA.Descuento) ) * (DA.Impuesto/100 ) ) AS T_Impuesto,

			SUM( DA.Saldo_Activo_Fijo_PCGA ) AS Saldo_Activo_Fijo_PCGA,
			SUM( DA.Saldo_Activo_Fijo_NIIF ) AS Saldo_Activo_Fijo_NIIF,
			SUM( IFNULL(A.Costo_PCGA, 0) ) AS Costo_Activo_Fijo_PCGA,
			SUM( IFNULL(A.Costo_NIIF, 0) ) AS Costo_Activo_Fijo_NIIF,
			
            SUM( IFNULL(A.Costo_PCGA - DA.Saldo_Activo_Fijo_PCGA,0)  ) AS Depreciacion_Activo_Fijo_PCGA,
			SUM( IFNULL(A.Costo_NIIF - DA.Saldo_Activo_Fijo_NIIF,0)  ) AS Depreciacion_Activo_Fijo_NIIF

			FROM Descripcion_Factura_Administrativa DA
            LEFT JOIN Activo_Fijo A ON A.Id_Activo_Fijo = DA.Id_Activo_Fijo
			WHERE DA.Id_Factura_Administrativa = ' . $idFactura . '
			GROUP BY DA.Id_Plan_Cuenta';

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('multiple');

		return $result;
	}

	private function GetTipoServicios()
	{
		$query = '
				SELECT
					Id_Tipo_Servicio,
					Codigo
				FROM Tipo_Servicio';

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('multiple');

		return $result;
	}

	private function GetTipoServicio($idTipo)
	{
		$query = '
				SELECT
					Id_Tipo_Servicio,
					Codigo
				FROM Tipo_Servicio
				WHERE
					Id_Tipo_Servicio = ' . $idTipo;

		$this->queryObj->SetQuery($query);
		$result = $this->queryObj->ExecuteQuery('simple');

		return $result['Codigo'];
	}

	private function GetFechaMovimiento($id, $tabla)
	{
		$oItem = new complex($tabla, "Id_$tabla", $id);
		$columna = 'Fecha';

		if ($tabla == 'Factura_Venta' || $tabla == 'Factura' || $tabla == 'Factura_Capita') {
			$columna = 'Fecha_Documento';
		} elseif ($tabla == 'Comprobante') {
			$columna = 'Fecha_Comprobante';
		} elseif ($tabla == 'Nota_Credito' || $tabla == 'Ajuste_Individual' || $tabla == 'Nomina' || $tabla == 'Nota_Credito_Global' || $tabla == 'Devolucion_Compra') {
			$columna = 'Fecha';
		} elseif ($tabla == 'Inventario_Fisico' || $tabla == 'Inventario_Fisico_Punto') {
			$columna = 'Fecha_Fin';
		} elseif ($tabla == 'Nacionalizacion_Parcial') {
			$columna = 'Fecha_Registro';
		} elseif ($tabla == 'Acta_Recepcion' || $tabla == 'Acta_Recepcion_Internacional')
			$columna = 'Fecha_Creacion';
		$fecha = $oItem->$columna;
		unset($oItem);

		return $fecha;
	}

	private function GetCostoProducto($producto, $tipo)
	{

		if ($tipo == 'Factura_Venta') {

			if ($producto['Id_Producto_Factura_Venta'] && $producto['Id_Producto_Factura_Venta']  != null) {

				$query = "SELECT Costo FROM Producto_Remision WHERE Id_Producto_Factura_Venta = " . $producto['Id_Producto_Factura_Venta'];
				$this->queryObj->SetQuery($query);
				$costo = $this->queryObj->ExecuteQuery('simple');
			} else {
				$costo = false;
			}

			if (!$costo || $costo['Costo'] == 0 || !$costo['Costo']) {

				$query = "SELECT Costo_Promedio as Costo FROM Costo_Promedio WHERE Id_Producto =" . $producto['Id_Producto'];
				$this->queryObj->SetQuery($query);
				$costo = $this->queryObj->ExecuteQuery('simple');

				if ($costo == null) {

					$costo = 0;
				} else {
					$costo = $costo['Costo'];
				}
			} else {
				$costo = $costo['Costo'];
			}
		}
		/*if('Inventario'){
			$query="SELECT Costo_Promedio as Costo FROM Costo_Promedio WHERE Id_Productoa =".$producto ;   
					$costo = $this->queryObj->ExecuteQuery('simple');
					
				if($costo == null){
					$costo = 0;
				}else{
				$costo=$costo['Costo'];
				}
			}
				/*$costo = 0;

				$query = '
					SELECT 
						AVG(Precio) AS Costo
					FROM Producto_Acta_Recepcion 
					WHERE 
						Id_Producto = '.$idProducto.' AND Precio > 0';

				$this->queryObj->SetQuery($query);
				$costo1 = $this->queryObj->ExecuteQuery('simple');
				
				if ($costo1['Costo'] != "") {
					$costo = $costo1['Costo'];
					return $costo;
				}

				$query = '
					SELECT 
						AVG(Costo) AS Costo
					FROM Inventario 
					WHERE 
						Id_Producto = '.$idProducto.' AND Costo > 0';

				$this->queryObj->SetQuery($query);
				$costo2 = $this->queryObj->ExecuteQuery('simple');

				if ($costo2['Costo']!="") {
					$costo = $costo2['Costo'];
					return $costo;
				}
			*/
		return $costo;
	}

	//Metodo para crear los movimientos de la nomina agregado el 2019-05-09 por Pedro Castillo
	private function CrearMovimientosNomina($datos)
	{
		$this->datos_funcionario = $this->getDatosFuncionario();
		$this->centro_costo = $this->resolveCentroCostoNomina($this->datos_funcionario);
		$texto = $this->datos_funcionario['Funcionario'] . " Causa Nomina " . $datos['Documento'];


		foreach ($datos['Conceptos'] as $key => $value) {
			$concepto = $this->GetPlanCuentasConceptoNomina($key);
			if ($concepto['Id_Cuenta_Contable'] && $key != 'Vacaciones' && $value > 0) {

				$nit = $this->ValidarNit($key);
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Cuenta_Contable'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $this->GetDatosDebe('Debe', $key) != false ? round($value, 0) : '0';
				$oItem->Haber = $this->GetDatosDebe('Haber', $key) != false ? round($value, 0) : '0';
				$oItem->Debe_Niif = $this->GetDatosDebe('Debe', $key) != false ? round($value, 0) : '0';
				$oItem->Haber_Niif = $this->GetDatosDebe('Haber', $key) != false ? round($value, 0) : '0';
				$oItem->Nit = $nit['Nit'];
				$oItem->Tipo_Nit = $nit['Tipo_Nit'];

				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $key == 'Prima de Servicios' ? $this->datos_funcionario['Funcionario'] . " Prima de Servicios " . $datos['Documento']  : $texto;
				$fechaActual = new DateTime();
				$ultimoDia = $fechaActual->format('d') > 20 ? $fechaActual->format('Y-m-t') : $fechaActual->modify('first day of last month')->format('Y-m-t');
				$oItem->Fecha_Movimiento = $ultimoDia;
				$oItem->save();

				unset($oItem);
				if ($key == 'Prima de Servicios') {
					$this->ContabilizarMoVimiento($key, $value, $datos['Documento']);
				}
			} else {
				$this->CrearMovimientoVacaciones($value, $datos['Documento']);
			}
		}
		foreach ($datos['Parafiscales'] as $key => $value) {
			$concepto = $this->GetPlanCuentasConceptoParafiscales($key);
			if ($concepto['Id_Cuenta_Contable'] && $value > 0) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Cuenta_Contable'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe =  round($value, 0);
				$oItem->Haber = '0';
				$oItem->Debe_Niif = round($value, 0);
				$oItem->Haber_Niif = '0';
				if ($key == 'Salud' || $key == 'Pension') {
					$nit = $this->ValidarNit($key);
					$oItem->Nit = $nit['Nit'];
					$oItem->Tipo_Nit = $nit['Tipo_Nit'];
				} else {
					$nit = $this->ValidarNitParafiscales($key);
					$oItem->Nit = $nit['Nit'];
					$oItem->Tipo_Nit = $nit['Tipo_Nit'];
				}

				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Contrapartida'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = '0';
				$oItem->Haber =  round($value, 0);
				$oItem->Debe_Niif = '0';
				$oItem->Haber_Niif =  round($value, 0);
				if ($key == 'Salud' || $key == 'Pension') {
					$nit = $this->ValidarNit($key);
					$oItem->Nit = $nit['Nit'];
					$oItem->Tipo_Nit = $nit['Tipo_Nit'];
				} else {
					$nit = $this->ValidarNitParafiscales($key);
					$oItem->Nit = $nit['Nit'];
					$oItem->Tipo_Nit = $nit['Tipo_Nit'];
				}

				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			}
		}
		foreach ($datos['Provision'] as $key => $value) {
			$concepto = $this->GetPlanCuentasConceptoParafiscales($key);
			if ($concepto['Id_Cuenta_Contable'] && $value > 0) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Cuenta_Contable'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe =  round($value, 0);
				$oItem->Haber = '0';
				$oItem->Debe_Niif = round($value, 0);
				$oItem->Haber_Niif = '0';
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);

				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Contrapartida'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = '0';
				$oItem->Haber =  round($value, 0);
				$oItem->Debe_Niif = '0';
				$oItem->Haber_Niif =  round($value, 0);
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function GetPlanCuentasConceptoNomina($concepto)
	{

		if ($concepto != 'Salarios por pagar' && $concepto != 'Salud' && $concepto != 'Pension' &&  $concepto != 'Prima de Servicios') {
			$query = "SELECT CPN.Id_Cuenta_Contable FROM Concepto_Parametro_Nomina CPN
					LEFT JOIN Plan_Cuentas_Centro_Costos PCCC
						ON PCCC.Id_Plan_Cuentas = CPN.Id_Cuenta_Contable
						AND PCCC.Id_Centro_Costo = " . $this->centro_costo . "
						AND PCCC.Estado = 'Activo'
					WHERE CPN.Nombre LIKE'$concepto%'
					ORDER BY (PCCC.Id_Plan_Cuentas_Centro_Costos IS NULL) ASC, CPN.Id_Concepto_Parametro_Nomina DESC
					LIMIT 1";
		} elseif ($concepto == 'Salud' || $concepto == 'Pension' || $concepto == 'Prima de Servicios') {
			$query = "SELECT CPN.Id_Contrapartida as Id_Cuenta_Contable  FROM Concepto_Parametro_Nomina CPN
					LEFT JOIN Plan_Cuentas_Centro_Costos PCCC
						ON PCCC.Id_Plan_Cuentas = CPN.Id_Contrapartida
						AND PCCC.Id_Centro_Costo = " . $this->centro_costo . "
						AND PCCC.Estado = 'Activo'
					WHERE CPN.Nombre LIKE'$concepto%'
					ORDER BY (PCCC.Id_Plan_Cuentas_Centro_Costos IS NULL) ASC, CPN.Id_Concepto_Parametro_Nomina DESC
					LIMIT 1";
		} elseif ($concepto == 'Salarios por pagar') {
			$query = "SELECT CPN.Id_Contrapartida as Id_Cuenta_Contable  FROM Concepto_Parametro_Nomina CPN
					LEFT JOIN Plan_Cuentas_Centro_Costos PCCC
						ON PCCC.Id_Plan_Cuentas = CPN.Id_Contrapartida
						AND PCCC.Id_Centro_Costo = " . $this->centro_costo . "
						AND PCCC.Estado = 'Activo'
					WHERE CPN.Nombre LIKE'Salario%'
					ORDER BY (PCCC.Id_Plan_Cuentas_Centro_Costos IS NULL) ASC, CPN.Id_Concepto_Parametro_Nomina DESC
					LIMIT 1";
		}
		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');
		return $datos;
	}

	private function GetDatosDebe($tipo, $key)
	{


		$debe = ["Nada", "Salario", "Bonificacion prestacional", "Comisiones", "Bonificacion no prestacional", "Auxilio de movilizacion", "Otros ingresos no prestacionales", "Incapacidad general", "Incapacidad laboral", "Licencia de maternidad", "Licencia de paternidad", "Licencia remunerada", "Auxilio Transporte", 'Prima de Servicios'];

		$haber = ["Nada", "Librazas", "Prestamo", "Celular", "Otras deducciones", "Aportes voluntarios a pension", "POLIZA FUNERARIA", "RESPONSABILIDADES", "Salud", "Pension", "Fondo pensional de subsistencia", "Fondo pensional de solidaridad", 'Salarios por pagar', 'RETENCION EN LA FUENTE'];

		if ($tipo == 'Debe') {
			$datos = $debe;
		} else {
			$datos = $haber;
		}

		$pos = array_search($key, $datos);
		return strval($pos);
	}

	private function GetPlanCuentasConceptoParafiscales($concepto)
	{
		$query = "SELECT CPN.Id_Cuenta_Contable, CPN.Id_Contrapartida
				FROM Concepto_Parametro_Nomina CPN
				LEFT JOIN Plan_Cuentas_Centro_Costos PCCC1
					ON PCCC1.Id_Plan_Cuentas = CPN.Id_Cuenta_Contable
					AND PCCC1.Id_Centro_Costo = " . $this->centro_costo . "
					AND PCCC1.Estado = 'Activo'
				LEFT JOIN Plan_Cuentas_Centro_Costos PCCC2
					ON PCCC2.Id_Plan_Cuentas = CPN.Id_Contrapartida
					AND PCCC2.Id_Centro_Costo = " . $this->centro_costo . "
					AND PCCC2.Estado = 'Activo'
				WHERE CPN.Nombre LIKE'$concepto%'
				ORDER BY (PCCC1.Id_Plan_Cuentas_Centro_Costos IS NULL AND PCCC2.Id_Plan_Cuentas_Centro_Costos IS NULL) ASC,
					CPN.Id_Concepto_Parametro_Nomina DESC
				LIMIT 1";

		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');
		return $datos;
	}

	private function ValidarNit($key)
	{
		$haber = ["Nada", "Salud", "Pension", "Fondo pensional de subsistencia", "Fondo pensional de solidaridad"];

		$pos = array_search($key, $haber);
		$nit['Nit'] = $this->nit;
		$nit['Tipo_Nit'] = $this->tipo_nit;
		if ($pos != false) {
			if ($key != 'Salud') {
				$nit['Nit'] = $this->datos_funcionario['Pension'];
				$nit['Tipo_Nit'] = 'Eps';
			} else {
				$nit['Nit'] = $this->datos_funcionario['Eps'];
				$nit['Tipo_Nit'] = 'Fondo_Pension';
			}
		}

		return $nit;
	}

	private function getDatosFuncionario()
	{
		$nit = (int)$this->nit;
		$query = "SELECT IFNULL((SELECT Nit FROM Eps WHERE Id_Eps=F.Id_Eps)," . $nit . ") as Eps, (SELECT SUBSTRING_INDEX(Nit,'-',1) FROM Fondo_Pension WHERE Id_Fondo_Pension=F.Id_Fondo_Pension) as Pension, (SELECT Nit FROM Caja_Compensacion WHERE Id_Caja_Compensacion=F.Id_Caja_Compensacion) as Caja,(SELECT Nit_Sena FROM Configuracion WHERE Id_Configuracion =1) as Nit_Sena, (SELECT ICBF FROM Configuracion WHERE Id_Configuracion =1) as ICBF, (SELECT A.Nit FROM Configuracion C INNER JOIN Arl A ON C.Id_Arl=A.Id_Arl WHERE Id_Configuracion =1) as Arl,CONCAT_WS(' ',F.Nombres,F.Apellidos) as Funcionario,Valor,F.Id_Centro_Costo,
			(SELECT Salario_Base FROM Configuracion  WHERE Id_Configuracion =1) as Salario_Base, (SELECT Salario_Auxilio_Transporte FROM Configuracion  WHERE Id_Configuracion =1) as Salario_Auxilio_Transporte
			FROM Funcionario F INNER JOIN Contrato_Funcionario CF ON F.Identificacion_Funcionario=CF.Identificacion_Funcionario WHERE CF.Estado='Activo' AND F.Identificacion_Funcionario= " . $nit;

		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');

		return $datos;
	}

	private function ValidarNitParafiscales($key)
	{
		$nit['Nit'] = $this->nit;
		if ($key == 'Caja de compensacion') {
			$nit['Nit'] = $this->datos_funcionario['Caja'];
			$nit['Tipo_Nit'] = 'Caja_Compensacion';
		} elseif ($key == 'ICBF') {
			$nit['Nit'] = $this->datos_funcionario['ICBF'];
			$nit['Tipo_Nit'] = 'ICBF';
		} elseif ($key == 'Riesgo ARL I' || $key == 'Riesgo ARL II' || $key == 'Riesgo ARL III' || $key == 'Riesgo ARL IV' || $key == 'Riesgo ARL IV') {
			$nit['Nit'] = $this->datos_funcionario['Arl'];
			$nit['Tipo_Nit'] = 'Arl';
		} elseif ($key == 'Sena') {
			$nit['Nit'] = $this->datos_funcionario['Nit_Sena'];
			$nit['Tipo_Nit'] = 'Sena';
		}
		return $nit;
	}

	private function ContabilizarMoVimiento($tipo, $valor, $documento)
	{
		if ($tipo == 'Prima de Servicios') {
			$query = "SELECT Id_Contrapartida  FROM Concepto_Parametro_Nomina WHERE Nombre LIKE'Salario%'";
			$this->queryObj->SetQuery($query);
			$datos = $this->queryObj->ExecuteQuery('simple');

			$texto = $this->datos_funcionario['Funcionario'] . " Prima de Servicios " . $documento;

			$query = "SELECT Valor  FROM Nomina_Funcionario NF INNER JOIN Movimiento_Nomina_Funcionario MN ON NF.Id_Nomina_Funcionario=MN.Id_Nomina_Funcionario WHERE MN.Concepto LIKE '$tipo%' AND NF.Id_Nomina=" . $this->id_registro_modulo;
			$this->queryObj->SetQuery($query);
			$prima = $this->queryObj->ExecuteQuery('simple');

			if ($valor < $prima['Valor']) {

				$texto = $this->datos_funcionario['Funcionario'] . " Prima de Servicios " . $documento;
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $datos['Id_Contrapartida'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = '0';
				$oItem->Haber =  round($prima['Valor'], 0);
				$oItem->Debe_Niif = '0';
				$oItem->Haber_Niif =  round($prima['Valor'], 0);
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $documento;
				$oItem->Numero_Comprobante = $documento;
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);

				$faltante_prima = $prima['Valor'] - $valor;

				$texto = $this->datos_funcionario['Funcionario'] . " Prima de Servicios " . $documento;
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = 546;
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $faltante_prima;
				$oItem->Haber =  '0';
				$oItem->Debe_Niif = $faltante_prima;
				$oItem->Haber_Niif =  '0';
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $documento;
				$oItem->Numero_Comprobante = $documento;
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function CrearMovimientoVacaciones($valor, $documento)
	{
		if ($valor > 0) {
			$cuentasVacaciones = $this->GetPlanCuentasConceptoParafiscales('Vacaciones');
			$cuentaCreditoVacaciones = !empty($cuentasVacaciones['Id_Contrapartida']) ? $cuentasVacaciones['Id_Contrapartida'] : 16;
			$query = 'SELECT IFNULL((SELECT SUM(Valor) as Valor  FROM Provision_Funcionario PF  INNER JOIN Nomina N ON PF.Id_Nomina=N.Id_Nomina WHERE N.Nomina LIKE"' . date('Y') . '%" AND PF.Tipo="Vacaciones" AND PF.Estado="Pendiente" AND PF.Identificacion_Funcionario=' . $this->nit . '),0) as Valor';
			$this->queryObj->SetQuery($query);
			$vacaciones_anio_actual = $this->queryObj->ExecuteQuery('simple');
			$fecha = (date('Y') - 1) . "-12-31";

			$query = 'SELECT IFNULL((SELECT B.Credito_PCGA FROM Balance_Inicial_Contabilidad B WHERE B.Fecha LIKE "' . $fecha . '" AND B.Nit=' . $this->nit . ' AND B.Id_Plan_Cuentas=371),0) as Valor';
			$this->queryObj->SetQuery($query);
			$vacaciones_anio_anterior = $this->queryObj->ExecuteQuery('simple');

			$query = "UPDATE Provision_Funcionario SET Estado='Pagadas' WHERE Tipo='Vacaciones' AND Estado='Pendiente' AND Identificacion_Funcionario=" . $this->nit;
			$this->queryObj->SetQuery($query);
			$this->queryObj->QueryUpdate();

			$texto = $this->datos_funcionario['Funcionario'] . " Vacaciones Disf. " . $documento;
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $cuentaCreditoVacaciones;
			$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = '0';
			$oItem->Haber =  $valor;
			$oItem->Debe_Niif = '0';
			$oItem->Haber_Niif = $valor;
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $documento;
			$oItem->Numero_Comprobante = $documento;
			$oItem->Detalles = $texto;
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
			$oItem->save();
			unset($oItem);

			$texto = $this->datos_funcionario['Funcionario'] . " Vacaciones Disf. " . $documento;
			if ($vacaciones_anio_anterior['Valor'] > 0) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = 371;
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $vacaciones_anio_anterior['Valor'];
				$oItem->Haber =  '0';
				$oItem->Debe_Niif = $vacaciones_anio_anterior['Valor'];
				$oItem->Haber_Niif = '0';
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $documento;
				$oItem->Numero_Comprobante = $documento;
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			}


			$texto = $this->datos_funcionario['Funcionario'] . " Vacaciones Disf. " . $documento;
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = 379;
			$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = $vacaciones_anio_actual['Valor'];
			$oItem->Haber =  '0';
			$oItem->Debe_Niif = $vacaciones_anio_actual['Valor'];
			$oItem->Haber_Niif = '0';
			$oItem->Nit = $this->nit;
			$oItem->Tipo_Nit = $this->tipo_nit;
			$oItem->Documento = $documento;
			$oItem->Numero_Comprobante = $documento;
			$oItem->Detalles = $texto;
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
			$oItem->save();
			unset($oItem);

			$sobrante_vacaciones = 0;
			$base = $valor - ($vacaciones_anio_actual['Valor'] + $vacaciones_anio_anterior['Valor']);



			if ($base != 0) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $base > 0 ? 676 : 516;
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $base > 0 ? number_format(abs($base), 2, ".", "") : '0';
				$oItem->Haber =  $base < 0 ? number_format(abs($base), 2, ".", "") : '0';
				$oItem->Debe_Niif = $base > 0 ? number_format(abs($base), 2, ".", "") : '0';
				$oItem->Haber_Niif = $base < 0 ? number_format(abs($base), 2, ".", "") : '0';
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $documento;
				$oItem->Numero_Comprobante = $documento;
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			}
			if ($sobrante_vacaciones != 0) {
			}
		}
	}

	private function CrearMovimientosLiquidacionFuncionario($datos)
	{

		$this->datos_funcionario = $this->getDatosFuncionario();
		$this->centro_costo = $this->resolveCentroCostoNomina($this->datos_funcionario);
		$texto = $this->datos_funcionario['Funcionario'] . " Liquidacion Contrato " . $datos['Documento'];

		$conceptos_permitidos = [
			'Cesantias' => true,
			'Intereses a las Cesantias' => true,
			'Intereses sobre cesantias' => true,
			'Vacaciones' => true,
			'Prima' => true,
			'Prima de Servicios' => true,
			'Bancos' => true,
			'Liquidacion por pagar' => true,
		];

		foreach ($datos['Contabilizacion_Liquidacion'] as $key => $value) {
			if (!isset($conceptos_permitidos[$key]) || $value <= 0) {
				continue;
			}
			$es_liquidacion_por_pagar = ($key == 'Bancos' || $key == 'Liquidacion por pagar');
			$concepto_key = $key;
			if ($key == 'Intereses sobre cesantias') {
				$concepto_key = 'Intereses a las Cesantias';
			}
			$concepto = $this->GetPlanCuentasConceptoParafiscales($concepto_key);
			if ($concepto['Id_Cuenta_Contable'] && $concepto['Id_Contrapartida'] && !$es_liquidacion_por_pagar) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Contrapartida'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe =  round($value, 0);
				$oItem->Haber =  '0';
				$oItem->Debe_Niif =  round($value, 0);
				$oItem->Haber_Niif = '0';
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;

				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			} elseif ($es_liquidacion_por_pagar) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = 367;
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = '0';
				$oItem->Haber = round($value, 0);
				$oItem->Debe_Niif = '0';
				$oItem->Haber_Niif = round($value, 0);
				$oItem->Nit = $this->nit;
				$oItem->Tipo_Nit = $this->tipo_nit;
				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function RegistrarMovimientos($datos, $campo, $texto)
	{
		foreach ($datos[$campo] as $key => $value) {
			$concepto = $this->GetPlanCuentasConceptoNomina($key);
			if ($concepto['Id_Cuenta_Contable'] && $key != 'Vacaciones' && $value > 0) {

				$nit = $this->ValidarNit($key);
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $concepto['Id_Cuenta_Contable'];
				$this->applyCentroCostoNomina($oItem, $oItem->Id_Plan_Cuenta);
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = $this->GetDatosDebe('Debe', $key) != false ? round($value, 0) : '0';
				$oItem->Haber = $this->GetDatosDebe('Haber', $key) != false ? round($value, 0) : '0';
				$oItem->Debe_Niif = $this->GetDatosDebe('Debe', $key) != false ? round($value, 0) : '0';
				$oItem->Haber_Niif = $this->GetDatosDebe('Haber', $key) != false ? round($value, 0) : '0';
				$oItem->Nit = $nit['Nit'];
				$oItem->Tipo_Nit = $nit['Tipo_Nit'];

				$oItem->Documento = $datos['Documento'];
				$oItem->Numero_Comprobante = $datos['Documento'];
				$oItem->Detalles = $key == 'Prima de Servicios' ? $this->datos_funcionario['Funcionario'] . " Prima de Servicios " . $datos['Documento']  : $texto;
				if ($this->save_fecha)
					$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nomina');
				$oItem->save();
				unset($oItem);
				if ($key == 'Prima de Servicios') {
					$this->ContabilizarMoVimiento($key, $value, $datos['Documento']);
				}
			} else {
				$this->CrearMovimientoVacaciones($value, $datos['Documento']);
			}
		}
	}

	//metodo de activos fijos 

	private function CrearMovimientosActivoFijo($datos)
	{

		foreach ($datos['Datos'] as $item) {
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			foreach ($item as $index => $value) {
				$oItem->$index = $value;
			}
			// $oItem->Fecha_Movimiento = $datos['Fecha'];
			$oItem->save();
			unset($oItem);
		}

		if (count($datos['Datos_Anticipos']) > 0) {
			foreach ($datos['Datos_Anticipos'] as $item) {
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				foreach ($item as $index => $value) {
					$oItem->$index = $value;
				}
				// $oItem->Fecha_Movimiento = $datos['Fecha'];
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function CrearMovimientosDepreciacion($datos)
	{

		$meses = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];

		$mes = $this->mesFormat($datos['Datos']['Mes']);

		$str_fecha = $datos['Datos']['Year'] . "-" . $mes . "-01";
		$fecha_movimiento = date('Y-m-t', strtotime($str_fecha));

		foreach ($datos['Contabilizacion']["Debito"] as $plan => $value) {
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $plan;
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Debe = number_format($value['Pcga'], 2, ".", "");
			$oItem->Haber = '0';
			$oItem->Debe_Niif = number_format($value['Niif'], 2, ".", "");
			$oItem->Haber_Niif = '0';
			$oItem->Nit = 804016084;
			$oItem->Tipo_Nit = 'Cliente';
			$oItem->Documento = $datos['Datos']['Codigo'];
			$oItem->Detalles = $meses[$datos['Datos']['Mes'] - 1] . " " . $datos['Datos']['Year'];
			$oItem->Numero_Comprobante = $datos['Datos']['Codigo'];
			$oItem->Fecha_Movimiento = $fecha_movimiento;
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Depreciacion');
			$oItem->save();
			unset($oItem);
		}

		foreach ($datos['Contabilizacion']["Credito"] as $plan => $value) {
			$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
			$oItem->Id_Plan_Cuenta = $plan;
			$oItem->Id_Modulo = $this->id_modulo;
			$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
			$oItem->Haber = number_format($value['Pcga'], 2, ".", "");
			$oItem->Debe = '0';
			$oItem->Haber_Niif = number_format($value['Niif'], 2, ".", "");
			$oItem->Debe_Niif = '0';
			$oItem->Nit = 804016084;
			$oItem->Tipo_Nit = 'Cliente';
			$oItem->Documento = $datos['Datos']['Codigo'];
			$oItem->Detalles = $meses[$datos['Datos']['Mes'] - 1] . " " . $datos['Datos']['Year'];
			$oItem->Numero_Comprobante = $datos['Datos']['Codigo'];
			$oItem->Fecha_Movimiento = $fecha_movimiento;
			if ($this->save_fecha)
				$oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Depreciacion');
			$oItem->save();
			unset($oItem);
		}
	}

	/*FIN METODOS GENERALES*/

	/*ANULAR MOVIMIENTOS*/

	public function AnularMovimientoContable($idRegistroModulo, $idModulo)
	{
		$query_anular = 'UPDATE Movimiento_Contable SET Estado = "Anulado" WHERE Id_Registro_Modulo IN (' . $idRegistroModulo . ') AND Id_Modulo IN (' . $idModulo . ')';

		$this->queryObj->SetQuery($query_anular);
		$this->queryObj->QueryUpdate();
	}
	/*FIN ANULAR MOVIMIENTOS*/


	private function mesFormat($mes)
	{
		$mes = $mes > 9 ? $mes : '0' . $mes; // Para que me dé el formato 01,02,03...

		return $mes;
	}

	private function getIdCentroCostoByCliente($id_cliente)
	{
		$query = "SELECT Id_Centro_Costo FROM Centro_Costo CC INNER JOIN Cliente C ON CC.Valor_Tipo_Centro = C.Id_Zona WHERE C.Id_Cliente = $id_cliente AND Id_Tipo_Centro = 5 AND CC.Estado = 'Activo'";

		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');

		return isset($datos['Id_Centro_Costo']) ? $datos['Id_Centro_Costo'] : 6;
	}

	private function getIdCentroCostoByTipo($tipo, $id = null)
	{
		$query = '';
		switch ($tipo) {
			case 'Punto_Dispensacion':
				$query = "SELECT Id_Centro_Costo FROM Centro_Costo WHERE Id_Tipo_Centro = 3 AND Valor_Tipo_Centro = $id AND Estado = 'Activo'";
				break;
		}

		if ($query != '') {
			$this->queryObj->SetQuery($query);
			$datos = $this->queryObj->ExecuteQuery('simple');
		}

		return ($id != null && isset($datos['Id_Centro_Costo'])) ? $datos['Id_Centro_Costo'] : 6;
	}

	private function isEnableCentroCostoByPUC($id_plan_cuenta)
	{
		$query = "SELECT Centro_Costo FROM Plan_Cuentas WHERE Id_Plan_Cuentas = $id_plan_cuenta";

		$this->queryObj->SetQuery($query);
		$datos = $this->queryObj->ExecuteQuery('simple');

		if ($datos['Centro_Costo'] == 'S') {
			return true;
		}

		return false;
	}

	private function resolveCentroCostoNomina($datos_funcionario)
	{
		if (!is_array($datos_funcionario)) {
			return 6;
		}
		$centro = isset($datos_funcionario['Id_Centro_Costo']) ? $datos_funcionario['Id_Centro_Costo'] : 0;
		if ($centro === '' || $centro === null || $centro == 0) {
			return 6;
		}

		return $centro;
	}

	private function applyCentroCostoIfRequired($oItem, $id_plan_cuenta, $force = false)
	{
		$id_plan_cuenta = $this->resolvePlanCuentaFromItem($oItem, $id_plan_cuenta);
		if (empty($id_plan_cuenta)) {
			return;
		}
		if ($force || $this->isEnableCentroCostoByPUC($id_plan_cuenta)) {
			$oItem->Id_Centro_Costo = $this->centro_costo;
		}
	}

	private function applyCentroCostoNomina($oItem, $id_plan_cuenta)
	{
		if ($this->centro_costo === null || $this->centro_costo === '' || $this->centro_costo == 0) {
			$this->datos_funcionario = $this->getDatosFuncionario();
			$this->centro_costo = $this->resolveCentroCostoNomina($this->datos_funcionario);
		}
		if ($this->centro_costo === null || $this->centro_costo === '' || $this->centro_costo == 0) {
			$this->centro_costo = 6;
		}
		$this->applyCentroCostoIfRequired($oItem, $id_plan_cuenta, true);
	}

	private function resolvePlanCuentaFromItem($oItem, $id_plan_cuenta)
	{
		if (!empty($id_plan_cuenta)) {
			return $id_plan_cuenta;
		}
		if (!is_object($oItem) || !method_exists($oItem, 'getData')) {
			return $id_plan_cuenta;
		}
		$data = $oItem->getData();
		return isset($data['Id_Plan_Cuenta']) ? $data['Id_Plan_Cuenta'] : $id_plan_cuenta;
	}

	private function CrearMovimientosCierreAnio($datos)
	{
		$movimientos = $this->ingresosCostosGastosTerceros($datos['Anio']);

		$response = $this->buildMovimientosCierre($datos, $movimientos);

		$this->addUtilidadOrPerdida($datos, $response['asiento'], $response['tipo_cuenta']);

		$movimientosActPas = $this->movimientosActivosPasivos($datos['Anio']);

		$nits_cierres = $this->buildMovimientosActPas($datos, $movimientosActPas);

		$this->saveContabilizacionActPas($datos, $nits_cierres);
	}

	private function buildMovimientosCierre($datos, $movimientos)
	{
		$inserts = [];

		$asiento = [
			"debe" => 0,
			"haber" => 0,
			"debe_niif" => 0,
			"haber_niif" => 0
		];
		$tipo_cuenta = [
			"ingresos" => 0,
			"costos" => 0,
			"gastos" => 0,
			"ingresos_niif" => 0,
			"costos_niif" => 0,
			"gastos_niif" => 0
		];

		foreach ($movimientos as $value) {
			$debe = $this->calcularDebeOrHaberCierre($value, 'debe', '');
			$haber = $this->calcularDebeOrHaberCierre($value, 'haber', '');
			$debe_niif = $this->calcularDebeOrHaberCierre($value, 'debe', '_Niif');
			$haber_niif = $this->calcularDebeOrHaberCierre($value, 'haber', '_Niif');

			$tipo_cuenta['ingresos'] += $value['Tipo_Cuenta'] == 'Ingresos' ? ($value['Valor_Saldo'] < 0 ? 0 : $value['Valor_Saldo']) : 0;
			$tipo_cuenta['costos'] += $value['Tipo_Cuenta'] == 'Costos' ? ($value['Valor_Saldo'] < 0 ? 0 : $value['Valor_Saldo']) : 0;
			$tipo_cuenta['gastos'] += $value['Tipo_Cuenta'] == 'Gastos' ? ($value['Valor_Saldo'] < 0 ? 0 : $value['Valor_Saldo']) : 0;
			$tipo_cuenta['ingresos_niif'] += $value['Tipo_Cuenta'] == 'Ingresos' ? ($value['Valor_Saldo_Niif'] < 0 ? 0 : $value['Valor_Saldo_Niif']) : 0;
			$tipo_cuenta['costos_niif'] += $value['Tipo_Cuenta'] == 'Costos' ? ($value['Valor_Saldo_Niif'] < 0 ? 0 : $value['Valor_Saldo_Niif']) : 0;
			$tipo_cuenta['gastos_niif'] += $value['Tipo_Cuenta'] == 'Gastos' ? ($value['Valor_Saldo_Niif'] < 0 ? 0 : $value['Valor_Saldo_Niif']) : 0;


			$inserts[] = "(NULL, $value[Id_Plan_Cuenta],'$datos[Anio]-12-31 00:00:00',{$this->id_modulo},{$this->id_registro_modulo}," . number_format($debe, 2, '.', '') . "," . number_format($haber, 2, '.', '') . "," . number_format($debe_niif, 2, '.', '') . "," . number_format($haber_niif, 2, '.', '') . ",$value[Tercero],'$value[Tipo_Tercero]','Activo','$datos[Codigo]','Cierre $datos[Anio]',NOW(),0,'No','$datos[Codigo]')";

			if (count($inserts) == 2000) {
				$this->saveContabilizacionCierre($inserts);
				$inserts = [];
			}

			$asiento["debe"] += $debe;
			$asiento["haber"] += $haber;
			$asiento["debe_niif"] += $debe_niif;
			$asiento["haber_niif"] += $haber_niif;
		}

		if (count($inserts) > 0) {
			$this->saveContabilizacionCierre($inserts);
		}

		$response = [
			"asiento" => $asiento,
			"tipo_cuenta" => $tipo_cuenta
		];

		return $response;
	}

	private function calcularDebeOrHaberCierre($value, $tipo_valor, $tipo_cont)
	{
		$valor = 0;
		switch ($tipo_valor) {
			case 'debe':
				if ($value['Valor_Saldo' . $tipo_cont] < 0) {
					if ($value['Naturaleza'] == 'C') {
						$valor = 0;
					} else {
						$valor = abs($value['Valor_Saldo' . $tipo_cont]);
					}
				} else {
					if ($value['Naturaleza'] == 'D') {
						$valor = 0;
					} else {
						$valor = $value['Valor_Saldo' . $tipo_cont];
					}
				}

				break;

			default:
				if ($value['Valor_Saldo' . $tipo_cont] < 0) {
					if ($value['Naturaleza'] == 'D') {
						$valor = 0;
					} else {
						$valor = abs($value['Valor_Saldo' . $tipo_cont]);
					}
				} else {
					if ($value['Naturaleza'] == 'C') {
						$valor = 0;
					} else {
						$valor = $value['Valor_Saldo' . $tipo_cont];
					}
				}
				break;
		}

		return $valor;
	}

	private function buildMovimientosActPas($datos, $movimientos)
	{
		$cierre_tercero = [];
		$inserts = [];

		foreach ($movimientos as $value) {
			$debe = $this->calcularDebeOrHaberCierre($value, 'debe', '');
			$haber = $this->calcularDebeOrHaberCierre($value, 'haber', '');
			$debe_niif = $this->calcularDebeOrHaberCierre($value, 'debe', '_Niif');
			$haber_niif = $this->calcularDebeOrHaberCierre($value, 'haber', '_Niif');

			if (!array_key_exists($value['Nit_Cierre'], $cierre_tercero)) {
				$cierre_tercero[$value['Nit_Cierre']] = [];
				$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']] = [
					"D" => 0,
					"C" => 0,
					"DN" => 0,
					"CN" => 0
				];

				if ($value['Valor_Saldo'] < 0 && $value['Naturaleza'] == 'D') {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = 0;
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = abs($value['Valor_Saldo']);
				} elseif ($value['Valor_Saldo'] > 0 && $value['Naturaleza'] == 'D') {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = $value['Valor_Saldo'];
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = 0;
				} elseif ($value['Valor_Saldo'] > 0 && $value['Naturaleza'] == 'C') {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = $value['Valor_Saldo'];
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = 0;
				} else {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = 0;
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = abs($value['Valor_Saldo']);
				}
				if ($value['Valor_Saldo_Niif'] < 0 && $value['Naturaleza'] == 'D') {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = 0;
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = abs($value['Valor_Saldo_Niif']);
				} elseif ($value['Valor_Saldo_Niif'] > 0 && $value['Naturaleza'] == 'D') {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = $value['Valor_Saldo_Niif'];
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = 0;
				} elseif ($value['Valor_Saldo_Niif'] > 0 && $value['Naturaleza'] == 'C') {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = $value['Valor_Saldo_Niif'];
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = 0;
				} else {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = 0;
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = abs($value['Valor_Saldo_Niif']);
				}
			} else {
				if (!array_key_exists($value['Id_Plan_Cuenta'], $cierre_tercero[$value['Nit_Cierre']])) {
					$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']] = [
						"D" => 0,
						"C" => 0,
						"DN" => 0,
						"CN" => 0
					];
					if ($value['Valor_Saldo'] < 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = abs($value['Valor_Saldo']);
					} elseif ($value['Valor_Saldo'] > 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = $value['Valor_Saldo'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = 0;
					} elseif ($value['Valor_Saldo'] > 0 && $value['Naturaleza'] == 'C') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = $value['Valor_Saldo'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = 0;
					} else {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] = 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] = abs($value['Valor_Saldo']);
					}
					if ($value['Valor_Saldo_Niif'] < 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = abs($value['Valor_Saldo_Niif']);
					} elseif ($value['Valor_Saldo_Niif'] > 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = $value['Valor_Saldo_Niif'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = 0;
					} elseif ($value['Valor_Saldo_Niif'] > 0 && $value['Naturaleza'] == 'C') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = $value['Valor_Saldo_Niif'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = 0;
					} else {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] = 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] = abs($value['Valor_Saldo_Niif']);
					}
				} else {
					if ($value['Valor_Saldo'] < 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] += 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] += abs($value['Valor_Saldo']);
					} elseif ($value['Valor_Saldo'] > 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] += $value['Valor_Saldo'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] += 0;
					} elseif ($value['Valor_Saldo'] > 0 && $value['Naturaleza'] == 'C') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] += $value['Valor_Saldo'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] += 0;
					} else {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["C"] += 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["D"] += abs($value['Valor_Saldo']);
					}
					if ($value['Valor_Saldo_Niif'] < 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] += 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] += abs($value['Valor_Saldo_Niif']);
					} elseif ($value['Valor_Saldo_Niif'] > 0 && $value['Naturaleza'] == 'D') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] += $value['Valor_Saldo_Niif'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] += 0;
					} elseif ($value['Valor_Saldo_Niif'] > 0 && $value['Naturaleza'] == 'C') {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] += $value['Valor_Saldo_Niif'];
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] += 0;
					} else {
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["CN"] += 0;
						$cierre_tercero[$value['Nit_Cierre']][$value['Id_Plan_Cuenta']]["DN"] += abs($value['Valor_Saldo_Niif']);
					}
				}
			}

			$inserts[] = "(NULL, $value[Id_Plan_Cuenta],'$datos[Anio]-12-31 00:00:00',{$this->id_modulo},{$this->id_registro_modulo}," . number_format($debe, 2, '.', '') . "," . number_format($haber, 2, '.', '') . "," . number_format($debe_niif, 2, '.', '') . "," . number_format($haber_niif, 2, '.', '') . ",$value[Tercero],'$value[Tipo_Tercero]','Activo','$datos[Codigo]','ACTIVOS-PASIVOS Cierre $datos[Anio]',NOW(),0,'No','$datos[Codigo]')";

			if (count($inserts) == 2000) {
				$this->saveContabilizacionCierre($inserts);
				$inserts = [];
			}
		}

		if (count($inserts) > 0) {
			$this->saveContabilizacionCierre($inserts);
		}

		return $cierre_tercero;
	}

	private function saveContabilizacionCierre($values)
	{
		$insert_into = "INSERT INTO Movimiento_Contable VALUES ";

		$query = $insert_into . implode(",", $values);

		$this->queryObj->SetQuery($query);
		$this->queryObj->QueryUpdate();
	}

	private function saveContabilizacionActPas($datos, $nits)
	{
		foreach ($nits as $nit => $data) {
			foreach ($data as $id_plan => $naturaleza) {
				//GUARDAR EL MOVIMIENTO CONTABLE
				$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
				$oItem->Id_Plan_Cuenta = $id_plan;
				$oItem->Id_Modulo = $this->id_modulo;
				$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
				$oItem->Debe = number_format($naturaleza['D'], 2, ".", "");
				$oItem->Haber = number_format($naturaleza['C'], 2, ".", "");
				$oItem->Debe_Niif = number_format($naturaleza['DN'], 2, ".", "");
				$oItem->Haber_Niif = number_format($naturaleza['CN'], 2, ".", "");
				$oItem->Nit = $nit;
				$oItem->Tipo_Nit = "Proveedor";
				$oItem->Documento = $datos['Codigo'];
				$oItem->Detalles = "ACTIVOS-PASIVOS CIERRE " . $datos['Anio'];
				$oItem->Numero_Comprobante = $datos['Codigo'];
				/* if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
						$oItem->Id_Centro_Costo = $this->centro_costo; */

				$oItem->Fecha_Movimiento = $datos['Anio'] . "-12-31 00:00:00";
				$oItem->save();
				unset($oItem);
			}
		}
	}

	private function addUtilidadOrPerdida($datos, $asiento, $total_tipo_cuentas)
	{
		$busqueda = $asiento['debe'] > $asiento['haber'] ? "utilidad ejercicio" : "perdida ejercicio";
		// $valor = $total_tipo_cuentas['ingresos'] - $total_tipo_cuentas['costos'] - $total_tipo_cuentas['gastos'];
		// $valor_niif = $total_tipo_cuentas['ingresos_niif'] - $total_tipo_cuentas['costos_niif'] - $total_tipo_cuentas['gastos_niif'];
		$valor = $asiento['debe'] - $asiento['haber'];
		$valor_niif = $asiento['debe_niif'] - $asiento['haber_niif'];
		$debe = $valor < 0 ? number_format(abs($valor), 2, ".", "") : "0";
		$haber = $valor > 0 ? number_format($valor, 2, ".", "") : "0";
		$debe_niif = $valor_niif < 0 ? number_format(abs($valor_niif), 2, ".", "") : "0";
		$haber_niif = $valor_niif > 0 ? number_format($valor_niif, 2, ".", "") : "0";

		$asociacion = $this->BuscarInformacionParaMovimiento($busqueda);

		//GUARDAR EL MOVIMIENTO CONTABLE
		$oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
		$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
		$oItem->Id_Modulo = $this->id_modulo;
		$oItem->Id_Registro_Modulo = $this->id_registro_modulo;
		$oItem->Debe = $debe;
		$oItem->Haber = $haber;
		$oItem->Debe_Niif = $debe_niif;
		$oItem->Haber_Niif = $haber_niif;
		$oItem->Nit = 804016084;
		$oItem->Tipo_Nit = "Proveedor";
		$oItem->Documento = $datos['Codigo'];
		$oItem->Detalles = "CIERRE " . $datos['Anio'];
		$oItem->Numero_Comprobante = $datos['Codigo'];
		/* if ($this->isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
				$oItem->Id_Centro_Costo = $this->centro_costo; */

		$oItem->Fecha_Movimiento = $datos['Anio'] . "-12-31 00:00:00";
		$oItem->save();
		unset($oItem);
	}

	private function ingresosCostosGastosTerceros($anio)
	{
		$query = "SELECT 
				MC.Id_Plan_Cuenta,
				PC.Codigo,
				PC.Nombre,
				MC.Nit AS Tercero,
				MC.Tipo_Nit AS Tipo_Tercero,
				'Ingresos' AS Tipo_Cuenta,
				(CASE PC.Naturaleza
					WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
					ELSE (SUM(MC.Debe) - SUM(MC.Haber))
				END) AS Valor_Saldo,
				(CASE PC.Naturaleza
					WHEN 'C' THEN (SUM(MC.Haber_Niif) - SUM(MC.Debe_Niif))
					ELSE (SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif))
				END) AS Valor_Saldo_Niif,
				PC.Naturaleza
				FROM
				Movimiento_Contable MC
				INNER JOIN
				Plan_Cuentas PC	ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
				
				#(SELECT * FROM Plan_Cuentas WHERE Codigo LIKE '4%' AND Movimiento = 'S') PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas

				WHERE
				MC.Estado != 'Anulado' AND YEAR(MC.Fecha_Movimiento) = $anio
				AND PC.Movimiento = 'S' AND PC.Tipo_Cierre_Anual = 'Ingresos'
				GROUP BY MC.Id_Plan_Cuenta, MC.Nit HAVING  (Valor_Saldo != 0 OR Valor_Saldo_Niif != 0)
			
			#-----COSTOS-----
			UNION ALL (
				SELECT 
				MC.Id_Plan_Cuenta,
				PC.Codigo,
				PC.Nombre,
				MC.Nit AS Tercero,
				MC.Tipo_Nit AS Tipo_Tercero,
				'Costos' AS Tipo_Cuenta,
				(CASE PC.Naturaleza
					WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
					ELSE (SUM(MC.Debe) - SUM(MC.Haber))
				END) AS Valor_Saldo,
				(CASE PC.Naturaleza
					WHEN 'C' THEN (SUM(MC.Haber_Niif) - SUM(MC.Debe_Niif))
					ELSE (SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif))
				END) AS Valor_Saldo_Niif,
				PC.Naturaleza
				FROM
				Movimiento_Contable MC
				INNER JOIN
				Plan_Cuentas PC	ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
				#(SELECT * FROM Plan_Cuentas WHERE Codigo LIKE '61%' AND Movimiento = 'S') PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
				WHERE
				MC.Estado != 'Anulado' AND YEAR(MC.Fecha_Movimiento) = $anio
				AND PC.Movimiento = 'S' AND PC.Tipo_Cierre_Anual = 'Costos'
				GROUP BY MC.Id_Plan_Cuenta, MC.Nit HAVING  (Valor_Saldo != 0 OR Valor_Saldo_Niif != 0)
				) 

			#-----GASTOS-----
			UNION ALL (
				SELECT 
				MC.Id_Plan_Cuenta,
				PC.Codigo,
				PC.Nombre,
				MC.Nit AS Tercero,
				MC.Tipo_Nit AS Tipo_Tercero,
				'Gastos' AS Tipo_Cuenta,
				(CASE PC.Naturaleza
					WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
					ELSE (SUM(MC.Debe) - SUM(MC.Haber))
				END) AS Valor_Saldo,
				(CASE PC.Naturaleza
					WHEN 'C' THEN (SUM(MC.Haber_Niif) - SUM(MC.Debe_Niif))
					ELSE (SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif))
				END) AS Valor_Saldo_Niif,
				PC.Naturaleza
				FROM
				Movimiento_Contable MC
				INNER JOIN
				Plan_Cuentas PC	ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
				#(SELECT * FROM Plan_Cuentas WHERE Codigo LIKE '51%' OR Codigo LIKE '52%' OR Codigo LIKE '53%' AND Movimiento = 'S') PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
				
				WHERE
				MC.Estado != 'Anulado' AND YEAR(MC.Fecha_Movimiento) = $anio
				AND PC.Movimiento = 'S' AND PC.Tipo_Cierre_Anual = 'Gastos'

				GROUP BY MC.Id_Plan_Cuenta, MC.Nit HAVING  (Valor_Saldo != 0 OR Valor_Saldo_Niif != 0))";

		$this->queryObj->SetQuery($query);
		$resultado = $this->queryObj->ExecuteQuery('Multiple');

		return $resultado;
	}

	private function movimientosActivosPasivos($anio)
	{
		$query = "SELECT 
			MC.Id_Plan_Cuenta,
			PC.Codigo,
			PC.Nombre,
			MC.Nit AS Tercero,
			MC.Tipo_Nit AS Tipo_Tercero,
			PC.Nit_Cierre,
			(CASE PC.Naturaleza
				WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
				ELSE (SUM(MC.Debe) - SUM(MC.Haber))
			END) AS Valor_Saldo,
			(CASE PC.Naturaleza
				WHEN 'C' THEN (SUM(MC.Haber_Niif) - SUM(MC.Debe_Niif))
				ELSE (SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif))
			END) AS Valor_Saldo_Niif,
			PC.Naturaleza
			FROM
			Movimiento_Contable MC
				INNER JOIN
			(
			SELECT
				*
				FROM
				Plan_Cuentas
				WHERE Estado = 'ACTIVO' 
				AND Movimiento = 'S'
				AND Cierra_Terceros = 'S'
				AND Nit_Cierre IS NOT NULL AND Nit_Cierre != '' 
				AND (Codigo LIKE '135515%'
				OR Codigo LIKE '135517%'
				OR Codigo LIKE '135518%'
				OR Codigo LIKE '240801%'
				OR Codigo LIKE '240802%'
				OR Codigo LIKE '240804%'
				OR Codigo LIKE '240805%'
				OR Codigo LIKE '240806%'
				OR (Codigo LIKE '2365%' AND Codigo NOT LIKE '236585')
				OR Codigo LIKE '2368%')
			) PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
			WHERE
			MC.Estado != 'Anulado' AND YEAR(MC.Fecha_Movimiento)=$anio
			GROUP BY MC.Id_Plan_Cuenta, MC.Nit HAVING Valor_Saldo != 0";

		$this->queryObj->SetQuery($query);
		$resultado = $this->queryObj->ExecuteQuery('Multiple');

		return $resultado;
	}

	public function validarMesOrAnioCerrado($fecha)
	{
		$mes = date('n', strtotime($fecha));
		$anio = date('Y', strtotime($fecha));

		$query = "SELECT Id_Cierre_Contable FROM Cierre_Contable WHERE Tipo_Cierre = 'Mes' AND Mes = $mes AND Anio = $anio AND Estado = 'Cerrado' UNION (SELECT Id_Cierre_Contable FROM Cierre_Contable WHERE Tipo_Cierre = 'Anio' AND Anio = $anio AND Estado = 'Cerrado')";

		$this->queryObj->SetQuery($query);
		$resultado = $this->queryObj->ExecuteQuery('simple');

		return $resultado ? false : true;
	}
}
