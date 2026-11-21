<?php
include('conexion.php');

if (!isset($_SESSION['usuario_id']) || !isset($_GET['venta'])) {
    exit("Acceso no autorizado.");
}

$ticket_json = base64_decode($_GET['venta'], true);
$ticket_data = $ticket_json ? json_decode($ticket_json, true) : null;

if (!is_array($ticket_data) || !isset($ticket_data['items']) || !is_array($ticket_data['items'])) {
    exit("Datos de ticket no válidos.");
}

$subtotal = isset($ticket_data['subtotal']) ? floatval($ticket_data['subtotal']) : 0;
$descuento = isset($ticket_data['descuento']) ? floatval($ticket_data['descuento']) : 0;
$total = isset($ticket_data['total']) ? floatval($ticket_data['total']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket - BZone</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 280px;
            margin: 20px auto;
            text-align: center;
            font-size: 13px;
            color: #000;
        }
        .header {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }
        table {
            width: 100%;
            text-align: left;
            border-collapse: collapse;
        }
        th, td {
            padding: 3px 0;
            font-size: 12px;
        }
        .total-box {
            text-align: right;
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }
        .footer {
            margin-top: 15px;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">BZone</div>
    <div>Control de Inventario & Ventas</div>
    <div>Fecha: <?php echo date('d/m/Y h:i a'); ?></div>
    <div class="divider"></div>

    <table>
        <?php foreach ($ticket_data['items'] as $item): ?>
            <?php 
                $nombre_prod = isset($item['nombre']) ? htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') : 'Producto';
                $cant_prod = isset($item['cantidad']) ? intval($item['cantidad']) : 0;
                $precio_prod = isset($item['precio']) ? floatval($item['precio']) : 0;
            ?>
            <tr>
                <td colspan="2"><strong><?php echo $nombre_prod; ?></strong></td>
            </tr>
            <tr>
                <td><?php echo $cant_prod; ?> x $<?php echo number_format($precio_prod, 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format($cant_prod * $precio_prod, 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="divider"></div>

    <div style="text-align: right; font-size: 12px;">
        Subtotal: $<?php echo number_format($subtotal, 2); ?><br>
        Descuento: -$<?php echo number_format($descuento, 2); ?>
    </div>

    <div class="total-box">
        TOTAL: $<?php echo number_format($total, 2); ?>
    </div>

    <div class="divider"></div>
    <div class="footer">
        ¡Gracias por su compra!<br>
        <strong>BZone</strong>
    </div>

    <script>
        // Disparar la ventana de impresión/Guardar PDF de inmediato
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>