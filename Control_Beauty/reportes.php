<?php
include('conexion.php');

// Verificar sesión y rol de Administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') { 
    header("Location: dashboard.php"); 
    exit(); 
}

// Consulta tolerante a la estructura de la base de datos
$sql_reporte = "SELECT v.*, p.nombre AS producto_nombre, p.precio_compra, u.nombre AS usuario_nombre 
                FROM ventas v 
                LEFT JOIN productos p ON v.producto_id = p.id 
                LEFT JOIN usuarios u ON v.usuario_id = u.id 
                ORDER BY v.id DESC";

$res_reporte = $conexion->query($sql_reporte);

// Inicializar acumuladores para el reporte
$total_ingresos = 0;
$total_costos = 0;
$total_ganancias = 0;
$total_unidades = 0;
$ventas_lista = [];

if ($res_reporte && $res_reporte->num_rows > 0) {
    while ($r = $res_reporte->fetch_assoc()) {
        $monto_total = floatval($r['total']);
        $cantidad = intval($r['cantidad']);
        $precio_compra = isset($r['precio_compra']) ? floatval($r['precio_compra']) : 0;
        
        $costo_total_item = $precio_compra * $cantidad;
        $ganancia_item = $monto_total - $costo_total_item;

        $total_ingresos += $monto_total;
        $total_costos += $costo_total_item;
        $total_ganancias += $ganancia_item;
        $total_unidades += $cantidad;

        $r['costo_calculado'] = $costo_total_item;
        $r['ganancia_calculada'] = $ganancia_item;
        $ventas_lista[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ganancias - BZone</title>
    <!-- Iconos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=6.0">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
            text-align: left;
        }
        .stat-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 14px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .stat-info h4 {
            margin: 0;
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-info p {
            margin: 4px 0 0 0;
            font-size: 22px;
            font-weight: 800;
            color: #111;
        }
        table.rep-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            text-align: left;
        }
        table.rep-table th {
            background: #f8f8f8;
            padding: 12px 10px;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #eee;
        }
        table.rep-table td {
            padding: 11px 10px;
            font-size: 13px;
            border-bottom: 1px solid #eee;
        }
        .btn-volver {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-volver:hover {
            color: #d81b60;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-brand">
            <img src="Logo.png" alt="BZone Logo" onerror="this.style.display='none'">
            <span><strong>BZone</strong> | Reporte Financiero & Ganancias</span>
        </div>
        <a href="dashboard.php" class="btn-volver"><i class="fa-solid fa-house"></i> Volver al Inicio</a>
    </header>

    <div class="main-wrapper" style="max-width: 1050px;">
        <div class="watermark-bg">BZone</div>

        <div class="dashboard-card" style="padding: 30px;">
            <h2 style="text-align: left; margin-top: 0; margin-bottom: 20px;">
                <i class="fa-solid fa-chart-line" style="color: #d81b60;"></i> Balance General de Ventas
            </h2>

            <!-- Tarjetas de Resumen Financiero -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9; color: #2e7d32;">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Ventas Totales</h4>
                        <p>$<?php echo number_format($total_ingresos, 2); ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0; color: #ef6c00;">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Inversión (Costo)</h4>
                        <p>$<?php echo number_format($total_costos, 2); ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fce4ec; color: #c2185b;">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Ganancia Neta</h4>
                        <p style="color: #d81b60;">$<?php echo number_format($total_ganancias, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tabla de Detalle de Transacciones -->
            <h3 style="text-align: left; margin-bottom: 15px;"><i class="fa-solid fa-receipt"></i> Historial Completo de Transacciones</h3>
            <table class="rep-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Vendido Por</th>
                        <th>Total Venta</th>
                        <th>Ganancia Est.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas_lista)): ?>
                        <?php foreach($ventas_lista as $item): ?>
                            <?php 
                                $fecha_ampm = date('d/m/Y h:i a', strtotime($item['fecha']));
                            ?>
                            <tr>
                                <td><i class="fa-regular fa-clock" style="color: #888;"></i> <?php echo htmlspecialchars($fecha_ampm, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong><?php echo !empty($item['producto_nombre']) ? htmlspecialchars($item['producto_nombre'], ENT_QUOTES, 'UTF-8') : 'Producto General'; ?></strong></td>
                                <td><?php echo intval($item['cantidad']); ?> pza(s)</td>
                                <td><i class="fa-solid fa-user-tie" style="color: #d81b60;"></i> <?php echo !empty($item['usuario_nombre']) ? htmlspecialchars($item['usuario_nombre'], ENT_QUOTES, 'UTF-8') : 'Usuario'; ?></td>
                                <td><strong>$<?php echo number_format($item['total'], 2); ?></strong></td>
                                <td>
                                    <span style="color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 4px 8px; border-radius: 6px; font-size: 12px;">
                                        +$<?php echo number_format($item['ganancia_calculada'], 2); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888; padding: 25px;">
                                <i class="fa-solid fa-inbox" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                                No hay registros de ventas almacenados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>