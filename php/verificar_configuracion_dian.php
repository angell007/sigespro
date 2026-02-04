<?php

/**
 * Script para verificar la configuración local vs DIAN
 * Verifica los 3 puntos críticos del error FAB05c:
 * 1. SoftwareID registrado en DIAN para ese rango
 * 2. Desfase entre configuración local y DIAN
 * 3. Resolución activa en DIAN
 */

header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/start.inc.php');
include_once(__DIR__ . '/../class/class.consulta.php');

$DBApiDian = "sigesproph_apidian";

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificación Configuración DIAN - FAB05c</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background: #f5f5f5;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    h1 {
      color: #333;
      border-bottom: 3px solid #4CAF50;
      padding-bottom: 10px;
    }

    h2 {
      color: #555;
      margin-top: 30px;
      border-left: 4px solid #2196F3;
      padding-left: 10px;
    }

    h3 {
      color: #666;
      margin-top: 20px;
    }

    .check-item {
      background: #f9f9f9;
      padding: 15px;
      margin: 10px 0;
      border-radius: 5px;
      border-left: 4px solid #ccc;
    }

    .check-item.ok {
      border-left-color: #4CAF50;
      background: #e8f5e9;
    }

    .check-item.warning {
      border-left-color: #FF9800;
      background: #fff3e0;
    }

    .check-item.error {
      border-left-color: #f44336;
      background: #ffebee;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 15px 0;
    }

    th {
      background: #2196F3;
      color: white;
      padding: 12px;
      text-align: left;
    }

    td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }

    tr:nth-child(even) {
      background: #f9f9f9;
    }

    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 3px;
      font-size: 12px;
      font-weight: bold;
    }

    .badge-success {
      background: #4CAF50;
      color: white;
    }

    .badge-warning {
      background: #FF9800;
      color: white;
    }

    .badge-error {
      background: #f44336;
      color: white;
    }

    .badge-info {
      background: #2196F3;
      color: white;
    }

    .code {
      background: #f5f5f5;
      padding: 10px;
      border-radius: 4px;
      font-family: monospace;
      margin: 10px 0;
    }

    .action-box {
      background: #e3f2fd;
      padding: 15px;
      border-radius: 5px;
      margin: 15px 0;
      border-left: 4px solid #2196F3;
    }

    .action-box h4 {
      margin-top: 0;
      color: #1976D2;
    }

    ul {
      line-height: 1.8;
    }

    .highlight {
      background: #fff9c4;
      padding: 2px 4px;
      border-radius: 3px;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1>🔍 Verificación de Configuración DIAN - Error FAB05c</h1>
    <p><strong>Fecha:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <hr>

    <?php
    try {
      // ============================================
      // PUNTO 1: Verificar SoftwareID y Rangos
      // ============================================
      echo "<h2>1️⃣ Verificación: SoftwareID registrado en DIAN para ese rango</h2>";

      // Obtener SoftwareID de la API
      $query = "SELECT s.*, c.identification_number as nit_empresa 
                      FROM {$DBApiDian}.software s
                      INNER JOIN {$DBApiDian}.companies c ON s.company_id = c.id
                      WHERE s.type = 'FE'
                      LIMIT 1";
      $oCon = new consulta();
      $oCon->setQuery($query);
      $software = $oCon->getData();
      unset($oCon);

      $softwareId = $software['identifier'] ?? null;

      if ($softwareId) {
        echo "<div class='check-item ok'>";
        echo "<h3>✅ SoftwareID encontrado en configuración local</h3>";
        echo "<p><strong>SoftwareID:</strong> <span class='highlight'>{$softwareId}</span></p>";
        echo "<p><strong>URL:</strong> {$software['url']}</p>";
        echo "<p><strong>NIT Empresa:</strong> {$software['nit_empresa']}</p>";
        echo "</div>";
      } else {
        echo "<div class='check-item error'>";
        echo "<h3>❌ ERROR: No se encontró SoftwareID configurado</h3>";
        echo "<p>No hay software de facturación electrónica configurado en la base de datos.</p>";
        echo "</div>";
      }

      // Obtener resoluciones activas con sus rangos
      $query = "SELECT 
                        r.Id_Resolucion,
                        r.Codigo,
                        r.Nombre,
                        r.Resolucion,
                        r.Numero_Inicial,
                        r.Numero_Final,
                        r.Id_Software,
                        r.resolution_id,
                        r.Estado,
                        r.Fecha_Inicio,
                        r.Fecha_Fin,
                        res.from as rango_desde_api,
                        res.to as rango_hasta_api,
                        res.prefix as prefijo_api,
                        res.resolution as resolucion_numero_api,
                        res.resolution_date as fecha_resolucion_api
                      FROM Resolucion r
                      LEFT JOIN {$DBApiDian}.resolutions res ON r.resolution_id = res.id
                      WHERE r.Estado = 'Activo'
                      ORDER BY r.Id_Resolucion DESC";

      $oCon = new consulta();
      $oCon->setTipo('Multiple');
      $oCon->setQuery($query);
      $resoluciones = $oCon->getData();
      unset($oCon);

      if ($resoluciones && count($resoluciones) > 0) {
        echo "<h3>📋 Resoluciones Activas y sus Rangos:</h3>";
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th><th>Código</th><th>Resolución</th><th>Rango Local</th><th>Rango API</th><th>SoftwareID</th><th>Estado</th>";
        echo "</tr>";

        foreach ($resoluciones as $res) {
          $rangoLocal = "{$res['Numero_Inicial']} - {$res['Numero_Final']}";
          $rangoApi = $res['rango_desde_api'] ? "{$res['rango_desde_api']} - {$res['rango_hasta_api']}" : "N/A";
          $softwareMatch = ($res['Id_Software'] == $softwareId) ? "✅" : "⚠️";

          echo "<tr>";
          echo "<td>{$res['Id_Resolucion']}</td>";
          echo "<td><strong>{$res['Codigo']}</strong></td>";
          echo "<td>{$res['Resolucion']}</td>";
          echo "<td>{$rangoLocal}</td>";
          echo "<td>{$rangoApi}</td>";
          echo "<td>{$softwareMatch} " . ($res['Id_Software'] ?: 'Vacío') . "</td>";
          echo "<td>{$res['Estado']}</td>";
          echo "</tr>";
        }
        echo "</table>";

        echo "<div class='action-box'>";
        echo "<h4>🔍 Cómo verificar en el Portal DIAN:</h4>";
        echo "<ol>";
        echo "<li>Acceder al <strong>Portal Único de Facturación Electrónica de la DIAN</strong></li>";
        echo "<li>Ir a la sección <strong>\"Software de Facturación\"</strong> o <strong>\"Configuración de Software\"</strong></li>";
        echo "<li>Buscar el SoftwareID: <span class='highlight'>{$softwareId}</span></li>";
        echo "<li>Verificar que esté asociado a los siguientes rangos de numeración:</li>";
        echo "<ul>";
        foreach ($resoluciones as $res) {
          $rango = $res['rango_desde_api'] ? "{$res['rango_desde_api']} - {$res['rango_hasta_api']}" : "{$res['Numero_Inicial']} - {$res['Numero_Final']}";
          echo "<li><strong>{$res['Codigo']}</strong>: Rango {$rango} (Resolución: {$res['Resolucion']})</li>";
        }
        echo "</ul>";
        echo "<li>Si el SoftwareID <strong>NO</strong> aparece asociado a estos rangos, ese es el problema</li>";
        echo "</ol>";
        echo "</div>";
      } else {
        echo "<div class='check-item warning'>";
        echo "<h3>⚠️ No se encontraron resoluciones activas</h3>";
        echo "</div>";
      }

      // ============================================
      // PUNTO 2: Verificar Desfase de Configuración
      // ============================================
      echo "<h2>2️⃣ Verificación: Desfase entre configuración local y DIAN</h2>";

      $desfases = [];

      if ($resoluciones && $softwareId) {
        foreach ($resoluciones as $res) {
          $problemas = [];

          // Verificar SoftwareID
          if (empty($res['Id_Software'])) {
            $problemas[] = "Id_Software vacío en tabla Resolucion";
          } elseif ($res['Id_Software'] != $softwareId) {
            $problemas[] = "Id_Software diferente: '{$res['Id_Software']}' vs '{$softwareId}'";
          }

          // Verificar rangos
          if ($res['rango_desde_api']) {
            if ($res['Numero_Inicial'] != $res['rango_desde_api']) {
              $problemas[] = "Rango inicial diferente: {$res['Numero_Inicial']} vs {$res['rango_desde_api']}";
            }
            if ($res['Numero_Final'] != $res['rango_hasta_api']) {
              $problemas[] = "Rango final diferente: {$res['Numero_Final']} vs {$res['rango_hasta_api']}";
            }
          } else {
            $problemas[] = "No tiene resolution_id asociado (no sincronizado con API)";
          }

          // Verificar fechas
          $fechaActual = date('Y-m-d');
          if ($res['Fecha_Fin'] && $res['Fecha_Fin'] < $fechaActual) {
            $problemas[] = "Resolución vencida desde: {$res['Fecha_Fin']}";
          }

          if (!empty($problemas)) {
            $desfases[] = [
              'resolucion' => $res['Codigo'],
              'problemas' => $problemas
            ];
          }
        }
      }

      if (empty($desfases)) {
        echo "<div class='check-item ok'>";
        echo "<h3>✅ No se detectaron desfases obvios en la configuración local</h3>";
        echo "<p>La configuración local parece estar sincronizada correctamente.</p>";
        echo "</div>";
      } else {
        echo "<div class='check-item error'>";
        echo "<h3>❌ Se detectaron desfases en la configuración:</h3>";
        echo "<table>";
        echo "<tr><th>Resolución</th><th>Problemas Detectados</th></tr>";
        foreach ($desfases as $desfase) {
          echo "<tr>";
          echo "<td><strong>{$desfase['resolucion']}</strong></td>";
          echo "<td><ul>";
          foreach ($desfase['problemas'] as $problema) {
            echo "<li>{$problema}</li>";
          }
          echo "</ul></td>";
          echo "</tr>";
        }
        echo "</table>";
        echo "</div>";

        echo "<div class='action-box'>";
        echo "<h4>🔧 Acciones para corregir desfases:</h4>";
        echo "<ol>";
        echo "<li><strong>Actualizar Id_Software en Resolucion:</strong>";
        echo "<div class='code'>";
        echo "UPDATE Resolucion SET Id_Software = '{$softwareId}' WHERE Estado = 'Activo' AND (Id_Software IS NULL OR Id_Software != '{$softwareId}');";
        echo "</div></li>";
        echo "<li><strong>Sincronizar rangos:</strong> Verificar que los rangos en la tabla Resolucion coincidan con los de resolutions</li>";
        echo "<li><strong>Verificar resolution_id:</strong> Asegurarse de que cada Resolucion tenga un resolution_id válido</li>";
        echo "</ol>";
        echo "</div>";
      }

      // ============================================
      // PUNTO 3: Verificar Resolución Activa en DIAN
      // ============================================
      echo "<h2>3️⃣ Verificación: Resolución activa en DIAN</h2>";

      if ($resoluciones) {
        echo "<h3>📅 Estado de las Resoluciones:</h3>";
        echo "<table>";
        echo "<tr>";
        echo "<th>Resolución</th><th>Número</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado Local</th><th>Vigencia</th>";
        echo "</tr>";

        $resolucionesVencidas = [];
        $resolucionesVigentes = [];

        foreach ($resoluciones as $res) {
          $fechaActual = date('Y-m-d');
          $fechaInicio = $res['Fecha_Inicio'] ?? 'N/A';
          $fechaFin = $res['Fecha_Fin'] ?? 'N/A';

          $vigente = true;
          $estadoVigencia = '';

          if ($fechaFin != 'N/A' && $fechaFin < $fechaActual) {
            $vigente = false;
            $estadoVigencia = "<span class='badge badge-error'>VENCIDA</span>";
            $resolucionesVencidas[] = $res;
          } elseif ($fechaInicio != 'N/A' && $fechaInicio > $fechaActual) {
            $vigente = false;
            $estadoVigencia = "<span class='badge badge-warning'>FUTURA</span>";
          } else {
            $estadoVigencia = "<span class='badge badge-success'>VIGENTE</span>";
            $resolucionesVigentes[] = $res;
          }

          echo "<tr>";
          echo "<td><strong>{$res['Codigo']}</strong></td>";
          echo "<td>{$res['Resolucion']}</td>";
          echo "<td>{$fechaInicio}</td>";
          echo "<td>{$fechaFin}</td>";
          echo "<td>{$res['Estado']}</td>";
          echo "<td>{$estadoVigencia}</td>";
          echo "</tr>";
        }
        echo "</table>";

        if (!empty($resolucionesVencidas)) {
          echo "<div class='check-item error'>";
          echo "<h3>❌ Resoluciones Vencidas:</h3>";
          echo "<ul>";
          foreach ($resolucionesVencidas as $res) {
            echo "<li><strong>{$res['Codigo']}</strong> - Venció el {$res['Fecha_Fin']}</li>";
          }
          echo "</ul>";
          echo "</div>";
        }

        if (!empty($resolucionesVigentes)) {
          echo "<div class='check-item ok'>";
          echo "<h3>✅ Resoluciones Vigentes:</h3>";
          echo "<ul>";
          foreach ($resolucionesVigentes as $res) {
            echo "<li><strong>{$res['Codigo']}</strong> - {$res['Resolucion']}</li>";
          }
          echo "</ul>";
          echo "</div>";
        }

        echo "<div class='action-box'>";
        echo "<h4>🔍 Cómo verificar en el Portal DIAN:</h4>";
        echo "<ol>";
        echo "<li>Acceder al <strong>Portal Único de Facturación Electrónica de la DIAN</strong></li>";
        echo "<li>Ir a la sección <strong>\"Resoluciones de Facturación\"</strong> o <strong>\"Rangos de Numeración\"</strong></li>";
        echo "<li>Verificar el estado de cada resolución:</li>";
        echo "<ul>";
        foreach ($resoluciones as $res) {
          echo "<li><strong>{$res['Codigo']}</strong> - Resolución {$res['Resolucion']}</li>";
          echo "<ul>";
          echo "<li>Número de resolución: <span class='highlight'>{$res['Resolucion']}</span></li>";
          if ($res['resolucion_numero_api']) {
            echo "<li>Número en API: <span class='highlight'>{$res['resolucion_numero_api']}</span></li>";
          }
          echo "<li>Rango: {$res['Numero_Inicial']} - {$res['Numero_Final']}</li>";
          echo "<li>Estado en DIAN: Debe estar <strong>\"ACTIVA\"</strong> o <strong>\"VIGENTE\"</strong></li>";
          echo "<li>Fecha de vigencia: {$res['Fecha_Inicio']} a {$res['Fecha_Fin']}</li>";
          echo "</ul>";
        }
        echo "</ul>";
        echo "<li>Si alguna resolución aparece como <strong>\"INACTIVA\"</strong>, <strong>\"VENCIDA\"</strong> o <strong>\"SUSPENDIDA\"</strong> en la DIAN, ese es el problema</li>";
        echo "</ol>";
        echo "</div>";
      }

      // ============================================
      // RESUMEN Y RECOMENDACIONES FINALES
      // ============================================
      echo "<h2>📊 Resumen y Recomendaciones</h2>";

      $tieneProblemas = !empty($desfases) || !empty($resolucionesVencidas);

      if ($tieneProblemas) {
        echo "<div class='check-item error'>";
        echo "<h3>⚠️ Se detectaron problemas que pueden causar el error FAB05c</h3>";
        echo "</div>";
      } else {
        echo "<div class='check-item ok'>";
        echo "<h3>✅ La configuración local parece correcta</h3>";
        echo "<p>Si aún así recibes el error FAB05c, el problema está en la configuración del portal de la DIAN.</p>";
        echo "</div>";
      }

      echo "<div class='action-box'>";
      echo "<h4>📋 Checklist de Verificación en Portal DIAN:</h4>";
      echo "<ul>";
      echo "<li>☐ SoftwareID <span class='highlight'>{$softwareId}</span> está registrado</li>";
      echo "<li>☐ SoftwareID está asociado a todos los rangos de numeración activos</li>";
      echo "<li>☐ Todas las resoluciones están en estado ACTIVA/VIGENTE</li>";
      echo "<li>☐ Las fechas de vigencia de las resoluciones son correctas</li>";
      echo "<li>☐ Los rangos de numeración coinciden con la configuración local</li>";
      echo "</ul>";
      echo "</div>";
    } catch (Exception $e) {
      echo "<div class='check-item error'>";
      echo "<h3>❌ Error al ejecutar verificación:</h3>";
      echo "<p>{$e->getMessage()}</p>";
      echo "</div>";
    }
    ?>

    <hr>
    <p><small>Generado el: <?php echo date('Y-m-d H:i:s'); ?></small></p>
  </div>
</body>

</html>