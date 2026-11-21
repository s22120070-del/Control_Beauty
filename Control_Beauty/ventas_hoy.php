<?php
include('conexion.php');

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

// Consulta de ventas del día con unión a productos y usuarios
$sql_ventas = "SELECT v.*, p.nombre AS producto_nombre, u.nombre AS usuario_nombre 
               FROM ventas v 
               LEFT JOIN productos p ON v.producto_id = p.id 
               LEFT JOIN usuarios u ON v.usuario_id = u.id 
               WHERE DATE(v.fecha) = CURDATE() 
               ORDER BY v.id DESC";

$res_ventas = $conexion->query($sql_ventas);

// Calcular total acumulado del día
$total_dia = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas de Hoy - BZone</title>
    <!-- Iconos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=5.0">
    <style>
        .summary-card-today {
            background: linear-gradient(135deg, #111111 0%, #2a2a2a 100%);
            color: #ffffff;
            padding: 20px 25px;
            border-radius: 14px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #d81b60;
        }
        .summary-card-today h3 {
            margin: 0;
            font-size: 18px;
            color: #ffffff;
        }
        .summary-card-today p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #cccccc;
        }
        .total-badge-today {
            font-size: 24px;
            font-weight: 800;
            color: #ff80ab;
        }
        table.sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            text-align: left;
        }
        table.sales-table th {
            background: #f8f8f8;
            padding: 12px 10px;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #eee;
        }
        table.sales-table td {
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
            <span><strong>BZone</strong> | Reporte de Ventas de Hoy</span>
        </div>
        <a href="dashboard.php" class="btn-volver"><i class="fa-solid fa-house"></i> Volver al Inicio</a>
    </header>

    <div class="main-wrapper" style="max-width: 950px;">
        <div class="watermark-bg">BZone</div>

        <div class="dashboard-card" style="padding: 30px;">
            <h2 style="text-align: left; margin-top: 0; margin-bottom: 20px;">
                <i class="fa-solid fa-calendar-day" style="color: #d81b60;"></i> Registro de Ventas del Día
            </h2>

            <!-- Tarjeta Resumen -->
            <div class="summary-card-today">
                <div>
                    <h3><i class="fa-solid fa-clock"></i> Jornada de Hoy</h3>
                    <p>Fecha: <?php echo date('d/m/Y'); ?></p>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #aaa;">Total Cobrado</span>
                    <div class="total-badge-today" id="lbl_total_dia">$0.00</div>
                </div>
            </div>

            <!-- Tabla de Transacciones -->
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Vendido Por</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($res_ventas && $res_ventas->num_rows > 0): 
                        while($v = $res_ventas->fetch_assoc()): 
                            $monto_venta = floatval($v['total']);
                            $total_dia += $monto_venta;
                            // Formato 12 Horas AM / PM
                            $hora_ampm = date('h:i a', strtotime($v['fecha']));
                    ?>
                        <tr>
                            <td><i class="fa-regular fa-clock" style="color: #888;"></i> <?php echo htmlspecialchars($hora_ampm, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo !empty($v['producto_nombre']) ? htmlspecialchars($v['producto_nombre'], ENT_QUOTES, 'UTF-8') : 'Producto N/A'; ?></strong></td>
                            <td><?php echo intval($v['cantidad']); ?> unidades</td>
                            <td><i class="fa-solid fa-user-tie" style="color: #d81b60;"></i> <?php echo !empty($v['usuario_nombre']) ? htmlspecialchars($v['usuario_nombre'], ENT_QUOTES, 'UTF-8') : 'Usuario'; ?></td>
                            <td><strong style="color: #2e7d32;">$<?php echo number_format($monto_venta, 2); ?></strong></td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888; padding: 25px;">
                                <i class="fa-solid fa-inbox" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                                No se han registrado ventas el día de hoy.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Actualizar el total general en el banner
        document.getElementById('lbl_total_dia').innerText = '$<?php echo number_format($total_dia, 2); ?>';
    </script>
</body>
</html>