<?php
include('conexion.php');

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

mysqli_report(MYSQLI_REPORT_OFF);

$mensaje = "";
$error = "";
$ticket_data_json = "";

// 1. REPARACIÓN Y RESTRUCTURACIÓN AUTOMÁTICA DE LA TABLA VENTAS
@$conexion->query("CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cantidad INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

@$conexion->query("ALTER TABLE ventas ADD COLUMN producto_id INT NOT NULL AFTER id;");
@$conexion->query("ALTER TABLE ventas ADD COLUMN usuario_id INT NOT NULL AFTER producto_id;");
@$conexion->query("ALTER TABLE ventas ADD COLUMN cantidad INT NOT NULL AFTER usuario_id;");
@$conexion->query("ALTER TABLE ventas ADD COLUMN total DECIMAL(10,2) NOT NULL AFTER cantidad;");
@$conexion->query("ALTER TABLE ventas ADD COLUMN fecha DATETIME NOT NULL AFTER total;");

// 2. PROCESAR GUARDADO DE LA VENTA (CARRITO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['procesar_venta_carrito'])) {
    $carrito_json = $_POST['carrito_data'];
    $items = json_decode($carrito_json, true);
    $usuario_id = intval($_SESSION['usuario_id']);
    $monto_descuento = floatval($_POST['descuento_aplicado']);

    if (!empty($items) && is_array($items)) {
        $conexion->begin_transaction();
        $exito = true;
        $subtotal_general = 0;

        $stmt_check = $conexion->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt_ins = $conexion->prepare("INSERT INTO ventas (producto_id, usuario_id, cantidad, total, fecha) VALUES (?, ?, ?, ?, NOW())");
        $stmt_upd = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

        foreach ($items as $item) {
            $prod_id = intval($item['id']);
            $cant = intval($item['cantidad']);
            $precio = floatval($item['precio']);
            $subtotal = $cant * $precio;

            // Verificar existencia y stock
            $stmt_check->bind_param("i", $prod_id);
            $stmt_check->execute();
            $check = $stmt_check->get_result();

            if ($check && $row = $check->fetch_assoc()) {
                if ($row['stock'] >= $cant && $cant > 0) {
                    $subtotal_general += $subtotal;
                    
                    // Insertar en la tabla ventas
                    $stmt_ins->bind_param("iiid", $prod_id, $usuario_id, $cant, $subtotal);
                    if (!$stmt_ins->execute()) {
                        $exito = false;
                        break;
                    }

                    // Actualizar el stock del producto
                    $stmt_upd->bind_param("ii", $cant, $prod_id);
                    if (!$stmt_upd->execute()) {
                        $exito = false;
                        break;
                    }
                } else {
                    $exito = false;
                    $error = "Stock insuficiente para uno de los productos.";
                    break;
                }
            } else {
                $exito = false;
                $error = "Producto no encontrado.";
                break;
            }
        }

        $stmt_check->close();
        $stmt_ins->close();
        $stmt_upd->close();

        if ($exito) {
            $total_final = max(0, $subtotal_general - $monto_descuento);
            $conexion->commit();
            $mensaje = "¡Venta registrada con éxito!";

            // Generar datos estructurados para el Modal de Ticket (Hora en formato AM/PM)
            $ticket_data_json = json_encode([
                'items' => $items,
                'subtotal' => $subtotal_general,
                'descuento' => $monto_descuento,
                'total' => $total_final,
                'fecha' => date('d/m/Y h:i a')
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        } else {
            $conexion->rollback();
            if (empty($error)) {
                $error = "Error al procesar la venta en la base de datos.";
            }
        }
    } else {
        $error = "El carrito está vacío. Agrega productos antes de guardar.";
    }
}

// Obtener la lista completa de productos para la selección
$productos_query = $conexion->query("SELECT * FROM productos WHERE stock > 0 ORDER BY nombre ASC");
$productos_array = array();

if ($productos_query && $productos_query->num_rows > 0) {
    while($row = $productos_query->fetch_assoc()) {
        $precio_real = isset($row['precio']) ? $row['precio'] : (isset($row['precio_venta']) ? $row['precio_venta'] : 0);
        $row['precio_final'] = floatval($precio_real);
        $row['codigo_barras'] = !empty($row['codigo_barras']) ? $row['codigo_barras'] : '';
        $productos_array[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Ventas - BZone</title>
    <!-- Iconos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=8.0">
    <style>
        .pos-container {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 20px;
            margin-top: 15px;
            text-align: left;
        }
        .pos-card {
            background: #ffffff;
            padding: 22px;
            border-radius: 16px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .form-group-pos { margin-bottom: 14px; }
        .form-group-pos label { display: block; font-size: 13px; font-weight: 600; color: #222; margin-bottom: 6px; }
        .form-group-pos input, .form-group-pos select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; background: #fafafa;
        }
        .btn-add {
            width: 100%; padding: 12px; background: #1a1a1a; color: #ffffff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;
        }
        .btn-add:hover { background: #d81b60; }
        table.pos-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.pos-table th { background: #f8f8f8; padding: 10px; font-size: 13px; color: #333; border-bottom: 2px solid #eee; text-align: left; }
        table.pos-table td { padding: 10px; font-size: 13px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .btn-remove { background: #ffebee; color: #c62828; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-remove:hover { background: #c62828; color: white; }
        .discount-section { background: #fdf2f5; border: 1px solid #f8bbd0; padding: 14px; border-radius: 10px; margin-top: 15px; }
        .discount-group { display: flex; gap: 10px; align-items: center; }
        .discount-group select, .discount-group input { padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; }
        .summary-box { margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 10px; border: 1px solid #eee; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px; color: #555; }
        .summary-row.total-row { border-top: 2px solid #ddd; padding-top: 8px; margin-top: 8px; font-size: 18px; font-weight: bold; color: #111; }
        .summary-row.total-row h3 { margin: 0; color: #d81b60; font-size: 22px; }
        .btn-checkout {
            width: 100%; margin-top: 15px; background: #2e7d32; color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s;
        }
        .btn-checkout:hover { background: #1b5e20; }
        .btn-volver { color: #ffffff; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
        .btn-volver:hover { color: #d81b60; }

        /* Modal de Ticket */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-ticket-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            width: 300px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            font-family: 'Courier New', Courier, monospace;
        }
        .ticket-divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        .btn-print-ticket {
            background: #111; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%;
        }
        .btn-close-ticket {
            background: #ccc; color: #333; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 8px; width: 100%;
        }

        @media print {
            body * { visibility: hidden; }
            #printable-ticket, #printable-ticket * { visibility: visible; }
            #printable-ticket { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <header class="no-print">
        <div class="header-brand">
            <img src="Logo.png" alt="BZone Logo" onerror="this.style.display='none'">
            <span><strong>BZone</strong> | Catálogo & Ventas</span>
        </div>
        <a href="dashboard.php" class="btn-volver"><i class="fa-solid fa-house"></i> Volver al Inicio</a>
    </header>

    <div class="main-wrapper no-print" style="max-width: 1050px;">
        <div class="watermark-bg">BZone</div>

        <div class="dashboard-card" style="padding: 25px;">
            <h2 style="text-align: left; margin: 0 0 15px 0;"><i class="fa-solid fa-cart-shopping"></i> Realizar Venta Directa</h2>

            <?php if ($mensaje != ""): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error != ""): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="pos-container">
                <div class="pos-card">
                    <h3 style="margin-top:0;"><i class="fa-solid fa-barcode"></i> Lector / Selección</h3>
                    
                    <div class="form-group-pos">
                        <label>Código de Barras:</label>
                        <input type="text" id="codigo_barras" placeholder="Escanear o escribir..." autofocus autocomplete="off">
                    </div>

                    <div class="form-group-pos">
                        <label>O Selecciona un Producto:</label>
                        <select id="select_producto">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach($productos_array as $p): ?>
                                <option value="<?php echo intval($p['id']); ?>">
                                    <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?> - $<?php echo number_format($p['precio_final'], 2); ?> (Stock: <?php echo intval($p['stock']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-pos">
                        <label>Cantidad:</label>
                        <input type="number" id="cantidad_input" value="1" min="1">
                    </div>

                    <button type="button" class="btn-add" onclick="agregarAlCarrito()"><i class="fa-solid fa-plus"></i> Agregar al Carrito</button>
                </div>

                <div class="pos-card">
                    <h3 style="margin-top:0;"><i class="fa-solid fa-list-check"></i> Productos en la Venta</h3>
                    
                    <table class="pos-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cant.</th>
                                <th>Stock</th>
                                <th>Subtotal</th>
                                <th>Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="carrito_body">
                            <tr>
                                <td colspan="6" style="text-align: center; color: #888;">No hay productos agregados.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="discount-section">
                        <label style="font-size: 13px; font-weight: bold; color: #c2185b; display: block; margin-bottom: 6px;">
                            <i class="fa-solid fa-tag"></i> Aplicar Descuento Especial:
                        </label>
                        <div class="discount-group">
                            <select id="tipo_descuento" onchange="calcularTotales()">
                                <option value="monto">Monto Fijo ($)</option>
                                <option value="porcentaje">Porcentaje (%)</option>
                            </select>
                            <input type="number" id="val_descuento" value="0" min="0" step="0.01" style="width: 110px;" oninput="calcularTotales()">
                        </div>
                    </div>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <strong id="lbl_subtotal">$0.00</strong>
                        </div>
                        <div class="summary-row" style="color: #c62828;">
                            <span>Descuento Aplicado:</span>
                            <strong id="lbl_descuento">-$0.00</strong>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total a Pagar:</span>
                            <h3 id="lbl_total">$0.00</h3>
                        </div>
                    </div>

                    <form method="POST" action="" onsubmit="return validarVenta()">
                        <input type="hidden" name="procesar_venta_carrito" value="1">
                        <input type="hidden" name="carrito_data" id="carrito_data">
                        <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="0">
                        <button type="submit" class="btn-checkout"><i class="fa-solid fa-floppy-disk"></i> Guardar Venta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL POPUP PARA IMPRESIÓN DE TICKET -->
    <div class="modal-overlay" id="ticketModal">
        <div class="modal-ticket-box" id="printable-ticket">
            <h3 style="margin:0; font-size:18px;">BZone</h3>
            <div style="font-size:11px;">Control de Inventario & Ventas</div>
            <div style="font-size:11px;" id="tk_fecha">--/--/---- --:-- --</div>
            <div class="ticket-divider"></div>

            <table style="width:100%; text-align:left; font-size:12px;" id="tk_items"></table>

            <div class="ticket-divider"></div>

            <div style="text-align:right; font-size:12px;">
                Subtotal: <span id="tk_subtotal">$0.00</span><br>
                Descuento: <span id="tk_descuento">-$0.00</span>
            </div>

            <div style="text-align:right; font-size:15px; font-weight:bold; margin-top:5px;">
                TOTAL: <span id="tk_total">$0.00</span>
            </div>

            <div class="ticket-divider"></div>
            <div style="font-size:11px;">¡Gracias por su compra!</div>

            <button type="button" class="btn-print-ticket no-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Ticket</button>
            <button type="button" class="btn-close-ticket no-print" onclick="cerrarTicket()">Cerrar</button>
        </div>
    </div>

    <script>
        const dbProductos = <?php echo json_encode($productos_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const ticketGenerado = <?php echo !empty($ticket_data_json) ? $ticket_data_json : 'null'; ?>;
        let carrito = [];

        function escaparHTML(texto) {
            const div = document.createElement('div');
            div.textContent = texto;
            return div.innerHTML;
        }

        if (ticketGenerado) {
            document.addEventListener('DOMContentLoaded', function() {
                mostrarModalTicket(ticketGenerado);
            });
        }

        function mostrarModalTicket(data) {
            document.getElementById('tk_fecha').innerText = data.fecha;
            let container = document.getElementById('tk_items');
            container.innerHTML = '';

            data.items.forEach(item => {
                let sub = item.cantidad * item.precio;
                container.innerHTML += `
                    <tr>
                        <td colspan="2"><strong>${escaparHTML(item.nombre)}</strong></td>
                    </tr>
                    <tr>
                        <td>${parseInt(item.cantidad)} x $${parseFloat(item.precio).toFixed(2)}</td>
                        <td style="text-align:right;">$${sub.toFixed(2)}</td>
                    </tr>
                `;
            });

            document.getElementById('tk_subtotal').innerText = '$' + parseFloat(data.subtotal).toFixed(2);
            document.getElementById('tk_descuento').innerText = '-$' + parseFloat(data.descuento).toFixed(2);
            document.getElementById('tk_total').innerText = '$' + parseFloat(data.total).toFixed(2);

            document.getElementById('ticketModal').style.display = 'flex';
        }

        function cerrarTicket() {
            document.getElementById('ticketModal').style.display = 'none';
        }

        document.getElementById('codigo_barras').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                let code = this.value.trim();
                let prod = dbProductos.find(p => p.codigo_barras === code);
                if (prod) {
                    agregarItem(prod, parseInt(document.getElementById('cantidad_input').value) || 1);
                    this.value = '';
                } else {
                    alert('Producto no encontrado con el código de barras ingresado.');
                }
            }
        });

        function agregarAlCarrito() {
            let select = document.getElementById('select_producto');
            let prodId = select.value;
            if (!prodId) {
                alert('Por favor selecciona un producto o escanea un código.');
                return;
            }
            let prod = dbProductos.find(p => p.id == prodId);
            let cant = parseInt(document.getElementById('cantidad_input').value) || 1;
            agregarItem(prod, cant);
        }

        function agregarItem(prod, cantidad) {
            let itemExistente = carrito.find(item => item.id == prod.id);
            let cantActual = itemExistente ? itemExistente.cantidad : 0;

            if ((cantActual + cantidad) > parseInt(prod.stock)) {
                alert('No hay suficiente stock. Disponible: ' + prod.stock);
                return;
            }

            if (itemExistente) {
                itemExistente.cantidad += cantidad;
            } else {
                carrito.push({
                    id: prod.id,
                    nombre: prod.nombre,
                    precio: parseFloat(prod.precio_final),
                    stock: parseInt(prod.stock),
                    cantidad: cantidad
                });
            }
            actualizarTabla();
        }

        function quitarItem(index) {
            carrito.splice(index, 1);
            actualizarTabla();
        }

        function actualizarTabla() {
            let tbody = document.getElementById('carrito_body');
            tbody.innerHTML = '';

            if (carrito.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888;">No hay productos agregados.</td></tr>';
            } else {
                carrito.forEach((item, index) => {
                    let subtotal = item.precio * item.cantidad;
                    let tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${escaparHTML(item.nombre)}</strong></td>
                        <td>$${item.precio.toFixed(2)}</td>
                        <td>${item.cantidad}</td>
                        <td>${item.stock}</td>
                        <td><strong>$${subtotal.toFixed(2)}</strong></td>
                        <td><button type="button" class="btn-remove" onclick="quitarItem(${index})"><i class="fa-solid fa-trash"></i></button></td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            calcularTotales();
        }

        function calcularTotales() {
            let subtotalGen = 0;
            carrito.forEach(item => {
                subtotalGen += (item.precio * item.cantidad);
            });

            let tipoDesc = document.getElementById('tipo_descuento').value;
            let valDesc = parseFloat(document.getElementById('val_descuento').value) || 0;
            let montoDescuento = 0;

            if (tipoDesc === 'porcentaje') {
                montoDescuento = (subtotalGen * valDesc) / 100;
            } else {
                montoDescuento = valDesc;
            }

            if (montoDescuento > subtotalGen) {
                montoDescuento = subtotalGen;
            }

            let totalFinal = subtotalGen - montoDescuento;

            document.getElementById('lbl_subtotal').innerText = '$' + subtotalGen.toFixed(2);
            document.getElementById('lbl_descuento').innerText = '-$' + montoDescuento.toFixed(2);
            document.getElementById('lbl_total').innerText = '$' + totalFinal.toFixed(2);

            document.getElementById('carrito_data').value = JSON.stringify(carrito);
            document.getElementById('descuento_aplicado').value = montoDescuento.toFixed(2);
        }

        function validarVenta() {
            if (carrito.length === 0) {
                alert('El carrito está vacío. Agrega al menos un producto.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>