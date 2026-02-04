<?php
// Mini interfaz para ajustar inventario por CUM y lote en puntos (sin contabilidad).
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../helper/response.php');

function read_payload()
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }
    return [];
}

function only_cum($cum)
{
    return preg_match('/^[A-Za-z0-9-]+$/', $cum) === 1;
}

function as_int($value, $default = null)
{
    if ($value === '' || $value === null) {
        return $default;
    }
    if (!is_numeric($value)) {
        return $default;
    }
    return (int) $value;
}

function registrarInventarioKardexPuntos($items, $funcionario, $id_punto)
{
    if (!is_array($items) || count($items) === 0) {
        return;
    }

    // Agrupar por estiba
    $por_estiba = [];
    foreach ($items as $item) {
        $id_estiba = (int) $item['Id_Estiba'];
        if ($id_estiba <= 0) {
            continue;
        }
        if (!isset($por_estiba[$id_estiba])) {
            $por_estiba[$id_estiba] = [];
        }
        $por_estiba[$id_estiba][] = $item;
    }

    if (count($por_estiba) === 0) {
        return;
    }

    // Obtener datos de estibas
    $ids = implode(',', array_keys($por_estiba));
    $query = "SELECT Id_Estiba, Id_Grupo_Estiba, Id_Punto_Dispensacion
              FROM Estiba
              WHERE Id_Estiba IN ($ids)";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $estibas = $oCon->getData();
    unset($oCon);

    $estiba_info = [];
    foreach ($estibas as $e) {
        $estiba_info[(int)$e['Id_Estiba']] = [
            'Id_Grupo_Estiba' => (int)$e['Id_Grupo_Estiba'],
            'Id_Punto_Dispensacion' => (int)$e['Id_Punto_Dispensacion']
        ];
    }

    // Crear encabezados por grupo
    $inventarios_por_grupo = [];
    foreach ($por_estiba as $id_estiba => $lista) {
        if (!isset($estiba_info[$id_estiba])) {
            continue;
        }
        $grupo = (int)$estiba_info[$id_estiba]['Id_Grupo_Estiba'];
        if ($grupo <= 0) {
            $grupo = 0;
        }
        if (!isset($inventarios_por_grupo[$grupo])) {
            $oItem = new complex('Inventario_Fisico_Punto_Nuevo', 'Id_Inventario_Fisico_Punto_Nuevo');
            $oItem->Funcionario_Autoriza = $funcionario;
            $oItem->Id_Punto_Dispensacion = $id_punto;
            $oItem->Id_Grupo_Estiba = $grupo;
            $oItem->Fecha = date('Y-m-d');
            $oItem->save();
            $inventarios_por_grupo[$grupo] = $oItem->getId();
            unset($oItem);
        }
    }

    // Crear documentos por estiba y productos
    foreach ($por_estiba as $id_estiba => $lista) {
        if (!isset($estiba_info[$id_estiba])) {
            continue;
        }
        $grupo = (int)$estiba_info[$id_estiba]['Id_Grupo_Estiba'];
        $inv_id = isset($inventarios_por_grupo[$grupo]) ? $inventarios_por_grupo[$grupo] : null;
        if (!$inv_id) {
            continue;
        }

        $oDoc = new complex('Doc_Inventario_Fisico_Punto', 'Id_Doc_Inventario_Fisico_Punto');
        $oDoc->Id_Estiba = $id_estiba;
        $oDoc->Funcionario_Digita = $funcionario;
        $oDoc->Funcionario_Cuenta = $funcionario;
        $oDoc->Fecha_Inicio = date('Y-m-d H:i:s');
        $oDoc->Fecha_Fin = date('Y-m-d H:i:s');
        $oDoc->Estado = 'Terminado';
        $oDoc->Funcionario_Autorizo = $funcionario;
        $oDoc->Id_Inventario_Fisico_Punto_Nuevo = $inv_id;
        $oDoc->Lista_Productos = '';
        $oDoc->save();
        $doc_id = $oDoc->getId();
        unset($oDoc);

        foreach ($lista as $item) {
            $oProd = new complex('Producto_Doc_Inventario_Fisico_Punto', 'Id_Producto_Doc_Inventario_Fisico_Punto');
            $oProd->Id_Producto = $item['Id_Producto'];
            $oProd->Id_Inventario_Nuevo = $item['Id_Inventario_Nuevo'];
            $oProd->Primer_Conteo = 0;
            $oProd->Segundo_Conteo = (int) $item['Cantidad_Final'];
            $oProd->Cantidad_Inventario = (int) $item['Cantidad_Anterior'];
            $oProd->Fecha_Primer_Conteo = date('Y-m-d');
            $oProd->Fecha_Segundo_Conteo = date('Y-m-d');
            $oProd->Id_Doc_Inventario_Fisico_Punto = $doc_id;
            $oProd->Lote = strtoupper($item['Lote']);
            $oProd->Fecha_Vencimiento = $item['Fecha_Vencimiento'];
            $oProd->save();
            unset($oProd);
        }
    }
}

$payload = read_payload();
$req = array_merge($_REQUEST, $payload);
$action = isset($req['action']) ? $req['action'] : '';

if ($action !== '') {
    header('Content-Type: application/json');

    if ($action === 'buscar') {
        $cum = isset($req['cum']) ? trim($req['cum']) : '';
        $id_punto = as_int(isset($req['id_punto']) ? $req['id_punto'] : 372, 372);

        if ($cum === '' || !only_cum($cum)) {
            echo json_encode(myerror('CUM inválido.'));
            exit;
        }

        $cum_safe = addslashes($cum);

        $query = "SELECT I.Id_Inventario_Nuevo, I.Id_Producto, I.Lote, I.Fecha_Vencimiento,
                         I.Cantidad, I.Cantidad_Apartada, I.Cantidad_Seleccionada, I.Codigo_CUM,
                         P.Nombre_Comercial, I.Id_Estiba, E.Nombre AS Nombre_Estiba
                  FROM Inventario_Nuevo I
                  INNER JOIN Producto P ON P.Id_Producto = I.Id_Producto
                  INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                  WHERE I.Codigo_CUM = '{$cum_safe}'
                    AND E.Id_Punto_Dispensacion = {$id_punto}
                  ORDER BY I.Fecha_Vencimiento ASC, I.Lote ASC";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $data = $oCon->getData();
        unset($oCon);

        echo json_encode(mysuccess([
            'cum' => $cum,
            'id_punto' => $id_punto,
            'inventarios' => $data ?: []
        ]));
        exit;
    }

    if ($action === 'guardar') {
        $cum = isset($req['cum']) ? trim($req['cum']) : '';
        $id_punto = as_int(isset($req['id_punto']) ? $req['id_punto'] : 372, 372);
        $funcionario = as_int(isset($req['funcionario']) ? $req['funcionario'] : 0, 0);
        $observaciones = isset($req['observaciones']) ? trim($req['observaciones']) : '';
        $items = isset($req['items']) ? $req['items'] : [];
        $registrar_kardex = isset($req['registrar_kardex']) ? (bool) $req['registrar_kardex'] : false;

        if ($cum === '' || !only_cum($cum)) {
            echo json_encode(myerror('CUM inválido.'));
            exit;
        }

        if (!is_array($items) || count($items) === 0) {
            echo json_encode(myerror('No hay filas para actualizar.'));
            exit;
        }

        $cum_safe = addslashes($cum);
        $actualizados = [];
        $saltados = [];

        $kardex_items = [];
        foreach ($items as $item) {
            $id_inv = as_int(isset($item['id']) ? $item['id'] : null, null);
            $nueva = isset($item['cantidad']) ? $item['cantidad'] : null;
            $cantidad_nueva = as_int($nueva, null);
            $id_estiba = as_int(isset($item['id_estiba']) ? $item['id_estiba'] : null, null);

            if ($id_inv === null || $cantidad_nueva === null || $cantidad_nueva < 0) {
                $saltados[] = [
                    'id' => $id_inv,
                    'motivo' => 'Cantidad inválida'
                ];
                continue;
            }

            $q = "SELECT I.Id_Inventario_Nuevo, I.Codigo_CUM, I.Id_Punto_Dispensacion, I.Lote, I.Cantidad,
                         I.Id_Producto, I.Fecha_Vencimiento, I.Id_Estiba,
                         E.Id_Punto_Dispensacion AS Punto_Estiba
                  FROM Inventario_Nuevo I
                  INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                  WHERE I.Id_Inventario_Nuevo = {$id_inv}
                  LIMIT 1";
            $oCon = new consulta();
            $oCon->setQuery($q);
            $inv = $oCon->getData();
            unset($oCon);

            $punto_estiba = isset($inv['Punto_Estiba']) ? (int) $inv['Punto_Estiba'] : 0;
            if (!$inv || $inv['Codigo_CUM'] !== $cum || $punto_estiba !== $id_punto) {
                $saltados[] = [
                    'id' => $id_inv,
                    'motivo' => 'No coincide con CUM/Punto'
                ];
                continue;
            }

            $cantidad_anterior = (int) $inv['Cantidad'];
            $cantidad_final = $cantidad_nueva < 0 ? 0 : $cantidad_nueva;

            $set_func = $funcionario > 0 ? ", Identificacion_Funcionario = {$funcionario}" : '';
            $update = "UPDATE Inventario_Nuevo
                       SET Cantidad = {$cantidad_final},
                           Cantidad_Apartada = 0,
                           Cantidad_Seleccionada = 0
                           {$set_func}
                       WHERE Id_Inventario_Nuevo = {$id_inv}";

            $oCon = new consulta();
            $oCon->setQuery($update);
            $oCon->createData();
            unset($oCon);

            $actualizados[] = [
                'id' => $id_inv,
                'lote' => $inv['Lote'],
                'antes' => $cantidad_anterior,
                'despues' => $cantidad_final
            ];

            if ($registrar_kardex) {
                $kardex_items[] = [
                    'Id_Inventario_Nuevo' => $id_inv,
                    'Id_Producto' => $inv['Id_Producto'],
                    'Lote' => $inv['Lote'],
                    'Fecha_Vencimiento' => $inv['Fecha_Vencimiento'],
                    'Cantidad_Anterior' => $cantidad_anterior,
                    'Cantidad_Final' => $cantidad_final,
                    'Id_Estiba' => $id_estiba ?: $inv['Id_Estiba']
                ];
            }

            $log_line = date('Y-m-d H:i:s') .
                " | funcionario={$funcionario}" .
                " | punto={$id_punto}" .
                " | cum={$cum_safe}" .
                " | lote={$inv['Lote']}" .
                " | antes={$cantidad_anterior}" .
                " | despues={$cantidad_final}" .
                " | obs=" . str_replace(["\n", "\r"], ' ', $observaciones) .
                PHP_EOL;
            @file_put_contents(__DIR__ . '/../../storage/logs/inventario_parcial_puntos.log', $log_line, FILE_APPEND);
        }

        if ($registrar_kardex && count($kardex_items) > 0) {
            registrarInventarioKardexPuntos($kardex_items, $funcionario, $id_punto);
        }

        echo json_encode(mysuccess([
            'actualizados' => $actualizados,
            'saltados' => $saltados
        ]));
        exit;
    }

    echo json_encode(myerror('Acción no válida.'));
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inventario parcial por CUM (Puntos)</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f6f7fb; color: #222; margin: 0; }
    .wrap { max-width: 1100px; margin: 24px auto; padding: 16px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    label { font-size: 12px; color: #444; display: block; margin-bottom: 6px; }
    input, textarea, button { font-size: 14px; padding: 8px 10px; border: 1px solid #cfd4dc; border-radius: 6px; }
    input:focus, textarea:focus { outline: none; border-color: #4b7bec; box-shadow: 0 0 0 2px rgba(75,123,236,0.12); }
    button { cursor: pointer; background: #1f6feb; color: #fff; border: none; }
    button.secondary { background: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 13px; }
    th { background: #f3f4f6; }
    .muted { color: #6b7280; font-size: 12px; }
    .status { margin-top: 12px; font-size: 13px; }
    .ok { color: #0f7b0f; }
    .err { color: #b00020; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h3>Inventario parcial por CUM (Puntos)</h3>
      <p class="muted">Flujo rápido: consulta por CUM en punto 372, ajusta cantidades por lote y guarda sin movimiento contable.</p>

      <div class="row">
        <div>
          <label for="cum">CUM</label>
          <input id="cum" type="text" placeholder="Ej: 207411-4">
        </div>
        <div>
          <label for="punto">Id Punto</label>
          <input id="punto" type="number" value="372" readonly>
        </div>
        <div>
          <label for="funcionario">Funcionario (Identificación)</label>
          <input id="funcionario" type="number" placeholder="Opcional">
        </div>
        <div style="align-self:flex-end;">
          <button id="btnBuscar">Buscar inventario</button>
        </div>
      </div>

      <div class="row">
        <div style="flex:1;">
          <label for="observaciones">Observaciones (opcional)</label>
          <textarea id="observaciones" rows="2" placeholder="Inventario parcial nocturno - Clínica Arenas"></textarea>
        </div>
      </div>
      <div class="row">
        <div>
          <label>
            <input id="registrar_kardex" type="checkbox" checked>
            Registrar en Kardex (Inventario)
          </label>
          <div class="muted">Crea un documento de inventario físico (puntos/estibas) para reflejar el ajuste.</div>
        </div>
      </div>

      <div id="resultado"></div>
      <div id="status" class="status"></div>
    </div>
  </div>

  <script>
    const $ = (id) => document.getElementById(id);

    function setStatus(text, ok = true) {
      const el = $('status');
      el.textContent = text;
      el.className = 'status ' + (ok ? 'ok' : 'err');
    }

    function renderTable(data) {
      if (!data || !data.length) {
        $('resultado').innerHTML = '<p class="muted">Sin registros para este CUM en el punto.</p>';
        return;
      }

      let html = '<table><thead><tr>' +
        '<th>Estiba</th><th>Lote</th><th>Vencimiento</th><th>Cant. actual</th><th>Cant. nueva</th>' +
        '<th>Id Inventario</th></tr></thead><tbody>';

      data.forEach((row, idx) => {
        const hasId = !!row.Id_Inventario_Nuevo;
        html += '<tr>' +
          '<td>' + (row.Nombre_Estiba || '') + '</td>' +
          '<td>' + (row.Lote || '') + '</td>' +
          '<td>' + (row.Fecha_Vencimiento || '') + '</td>' +
          '<td>' + (row.Cantidad ?? '') + '</td>' +
          '<td><input type="number" min="0" data-idx="' + idx + '" class="cant-nueva" placeholder="(vacío = no ajustar)" ' + (hasId ? '' : 'disabled') + '></td>' +
          '<td>' + (row.Id_Inventario_Nuevo || '<span class="muted">sin ID</span>') + '</td>' +
          '</tr>';
      });
      html += '</tbody></table>';
      html += '<div style="margin-top:12px;"><button id="btnGuardar">Guardar cambios</button></div>';
      $('resultado').innerHTML = html;

      $('btnGuardar').addEventListener('click', () => guardarCambios(data));
    }

    function normalizarRespuesta(json) {
      if (!json) {
        return { ok: false, mensaje: 'Respuesta vacía', datos: null };
      }
      if (json.Respuesta) {
        const ok = json.Respuesta.Codigo === 0;
        return {
          ok,
          mensaje: ok ? '' : (json.Respuesta.Mensaje || 'Error'),
          datos: json.Respuesta.Datos || null
        };
      }
      if (json.codigo) {
        const ok = json.codigo === 'success';
        return {
          ok,
          mensaje: ok ? '' : (json.mensaje || 'Error'),
          datos: json.query_result || null
        };
      }
      return { ok: false, mensaje: 'Formato de respuesta desconocido', datos: null };
    }

    async function buscar() {
      const cum = $('cum').value.trim();
      const punto = $('punto').value;
      setStatus('Consultando...', true);
      const res = await fetch('inventario_parcial_puntos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'buscar', cum, id_punto: punto })
      });
      const json = await res.json();
      const r = normalizarRespuesta(json);
      if (r.ok) {
        renderTable((r.datos && r.datos.inventarios) || []);
        setStatus('Inventario cargado.', true);
        return;
      }
      setStatus(r.mensaje || 'Error al consultar.', false);
    }

    async function guardarCambios(data) {
      const cum = $('cum').value.trim();
      const punto = $('punto').value;
      const funcionario = $('funcionario').value.trim();
      const observaciones = $('observaciones').value.trim();

      const inputs = Array.from(document.querySelectorAll('.cant-nueva'));
      const items = [];

      inputs.forEach((input) => {
        const idx = parseInt(input.getAttribute('data-idx'), 10);
        const val = input.value;
        if (val !== '') {
          items.push({
            id: data[idx].Id_Inventario_Nuevo,
            cantidad: val,
            id_estiba: data[idx].Id_Estiba || null
          });
        }
      });

      if (!items.length) {
        setStatus('No hay cantidades nuevas para guardar.', false);
        return;
      }

      setStatus('Guardando...', true);
      const registrarKardex = document.getElementById('registrar_kardex').checked;
      const res = await fetch('inventario_parcial_puntos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'guardar', cum, id_punto: punto, funcionario, observaciones, items, registrar_kardex: registrarKardex })
      });
      const json = await res.json();

      const r = normalizarRespuesta(json);
      if (r.ok) {
        const datos = r.datos || { actualizados: [], saltados: [] };
        const ok = (datos.actualizados || []).length;
        const skip = (datos.saltados || []).length;
        setStatus(`Actualizados: ${ok}. Saltados: ${skip}.`, true);
        return;
      }
      setStatus(r.mensaje || 'Error al guardar.', false);
    }

    $('btnBuscar').addEventListener('click', buscar);
  </script>
</body>
</html>
