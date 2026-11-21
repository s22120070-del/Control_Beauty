<?php
include('conexion.php');

if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$mensaje = "";
$error = "";

// Crear carpeta para subir imágenes si no existe
$carpeta_uploads = 'uploads/';
if (!file_exists($carpeta_uploads)) {
    mkdir($carpeta_uploads, 0755, true);
}

// 1. ELIMINAR PRODUCTO (Con consulta preparada)
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $stmt_del = $conexion->prepare("DELETE FROM productos WHERE id = ?");
    $stmt_del->bind_param("i", $id_eliminar);
    if ($stmt_del->execute()) {
        $mensaje = "Producto eliminado correctamente del inventario.";
    } else {
        $error = "No se pudo eliminar el producto.";
    }
    $stmt_del->close();
}

// 2. MODO EDICIÓN: Obtener datos del producto a editar (Con consulta preparada)
$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $stmt_edit = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt_edit->bind_param("i", $id_editar);
    $stmt_edit->execute();
    $res_edit = $stmt_edit->get_result();
    if ($res_edit && $res_edit->num_rows > 0) {
        $producto_editar = $res_edit->fetch_assoc();
    }
    $stmt_edit->close();
}

// 3. GUARDAR O ACTUALIZAR PRODUCTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_producto'])) {
    $id_prod = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;
    $nombre = trim($_POST['nombre']);
    $codigo_barras = trim($_POST['codigo_barras']);
    $precio_compra = floatval($_POST['precio_compra']);
    $precio_venta = floatval($_POST['precio_venta']);
    $stock = intval($_POST['stock']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $nombre_imagen = "";

    // Validar y procesar la subida de la foto del producto de forma segura
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extension, $extensiones_permitidas)) {
            $nombre_tmp = $_FILES['imagen']['tmp_name'];
            $nombre_imagen = time() . '_' . uniqid() . '.' . $extension;
            $ruta_destino = $carpeta_uploads . $nombre_imagen;
            move_uploaded_file($nombre_tmp, $ruta_destino);
        } else {
            $error = "Formato de imagen no válido. Solo se permiten JPG, PNG o WEBP.";
        }
    }

    if ($error === "") {
        if ($id_prod > 0) {
            // ACTUALIZAR PRODUCTO EXISTENTE (Con consultas preparadas)
            if (!empty($nombre_imagen)) {
                $stmt_upd = $conexion->prepare("UPDATE productos SET nombre = ?, codigo_barras = ?, precio_compra = ?, precio_venta = ?, stock = ?, stock_minimo = ?, imagen = ? WHERE id = ?");
                $stmt_upd->bind_param("ssddiisi", $nombre, $codigo_barras, $precio_compra, $precio_venta, $stock, $stock_minimo, $nombre_imagen, $id_prod);
            } else {
                $stmt_upd = $conexion->prepare("UPDATE productos SET nombre = ?, codigo_barras = ?, precio_compra = ?, precio_venta = ?, stock = ?, stock_minimo = ? WHERE id = ?");
                $stmt_upd->bind_param("ssddiii", $nombre, $codigo_barras, $precio_compra, $precio_venta, $stock, $stock_minimo, $id_prod);
            }

            if ($stmt_upd->execute()) {
                $mensaje = "Producto actualizado exitosamente.";
                $producto_editar = null;
            } else {
                $error = "Error al actualizar el producto.";
            }
            $stmt_upd->close();
        } else {
            // INSERTAR NUEVO PRODUCTO (Con consultas preparadas)
            $stmt_check = $conexion->prepare("SELECT id FROM productos WHERE nombre = ? OR (codigo_barras != '' AND codigo_barras = ?)");
            $stmt_check->bind_param("ss", $nombre, $codigo_barras);
            $stmt_check->execute();
            $check_res = $stmt_check->get_result();

            if ($check_res && $check_res->num_rows > 0) {
                $error = "El producto o código de barras ya se encuentra registrado.";
            } else {
                $stmt_ins = $conexion->prepare("INSERT INTO productos (nombre, codigo_barras, precio_compra, precio_venta, stock, stock_minimo, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_ins->bind_param("ssddiis", $nombre, $codigo_barras, $precio_compra, $precio_venta, $stock, $stock_minimo, $nombre_imagen);
                
                if ($stmt_ins->execute()) {
                    $mensaje = "Producto registrado exitosamente.";
                } else {
                    $error = "Error al registrar el producto.";
                }
                $stmt_ins->close();
            }
            $stmt_check->close();
        }
    }
}

// Consultar lista de productos
$productos_res = $conexion->query("SELECT * FROM productos ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro y Edición de Productos - BZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=3.0">
    <style>
        .form-grid-prod {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            text-align: left;
            margin-bottom: 25px;
        }
        .form-group-prod {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group-prod.full-width {
            grid-column: span 2;
        }
        .form-group-prod label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }
        .form-group-prod input {
            padding: 11px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            background: #fafafa;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group-prod input[type="file"] {
            background: #ffffff;
            padding: 8px;
            cursor: pointer;
        }
        .form-group-prod input:focus {
            border-color: #d81b60;
            background: #ffffff;
        }
        .btn-guardar-prod {
            grid-column: span 2;
            padding: 14px;
            background: #1a1a1a;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-guardar-prod:hover {
            background: #d81b60;
        }
        .btn-cancelar-edit {
            grid-column: span 2;
            padding: 10px;
            background: #757575;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
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
        table.prod-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: left;
        }
        table.prod-table th {
            background: #f8f8f8;
            padding: 12px 10px;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #eee;
        }
        table.prod-table td {
            padding: 10px;
            font-size: 13px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .img-preview-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .btn-action-edit {
            background: #e3f2fd;
            color: #1565c0;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-edit:hover {
            background: #1565c0;
            color: white;
        }
        .btn-action-delete {
            background: #ffebee;
            color: #c62828;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 5px;
        }
        .btn-action-delete:hover {
            background: #c62828;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-brand">
            <img src="Logo.png" alt="BZone Logo" onerror="this.style.display='none'">
            <span><strong>BZone</strong> | Registro de Productos</span>
        </div>
        <a href="dashboard.php" class="btn-volver"><i class="fa-solid fa-house"></i> Volver al Inicio</a>
    </header>

    <div class="main-wrapper" style="max-width: 1000px;">
        <div class="watermark-bg">BZone</div>

        <div class="dashboard-card" style="padding: 35px;">
            <h2 style="text-align: left; margin-top: 0; margin-bottom: 20px;">
                <i class="fa-solid <?php echo $producto_editar ? 'fa-pen-to-square' : 'fa-box-open'; ?>" style="color: #d81b60;"></i> 
                <?php echo $producto_editar ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?>
            </h2>

            <?php if ($mensaje != ""): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error != ""): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <!-- Formulario para Crear / Editar -->
            <form method="POST" action="productos.php" enctype="multipart/form-data">
                <input type="hidden" name="guardar_producto" value="1">
                <?php if ($producto_editar): ?>
                    <input type="hidden" name="id_producto" value="<?php echo intval($producto_editar['id']); ?>">
                <?php endif; ?>

                <div class="form-grid-prod">
                    <div class="form-group-prod full-width">
                        <label><i class="fa-solid fa-tag"></i> Nombre del Producto:</label>
                        <input type="text" name="nombre" value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['nombre'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="Ej. Pintalabios Velvet Rose" required autocomplete="off">
                    </div>

                    <div class="form-group-prod">
                        <label><i class="fa-solid fa-barcode"></i> Código de Barras:</label>
                        <input type="text" name="codigo_barras" value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['codigo_barras'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="Escanear o ingresar número">
                    </div>

                    <div class="form-group-prod">
                        <label><i class="fa-solid fa-boxes-stacked"></i> Cantidad (Stock):</label>
                        <input type="number" name="stock" value="<?php echo $producto_editar ? intval($producto_editar['stock']) : '0'; ?>" min="0" required>
                    </div>

                    <div class="form-group-prod">
                        <label><i class="fa-solid fa-file-invoice-dollar"></i> Precio Compra ($):</label>
                        <input type="number" name="precio_compra" step="0.01" value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['precio_compra'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="0.00" required>
                    </div>

                    <div class="form-group-prod">
                        <label><i class="fa-solid fa-hand-holding-dollar"></i> Precio Venta ($):</label>
                        <input type="number" name="precio_venta" step="0.01" value="<?php echo $producto_editar ? (isset($producto_editar['precio_venta']) ? htmlspecialchars($producto_editar['precio_venta'], ENT_QUOTES, 'UTF-8') : '') : ''; ?>" placeholder="0.00" required>
                    </div>

                    <div class="form-group-prod">
                        <label><i class="fa-solid fa-triangle-exclamation"></i> Stock Mínimo Alerta:</label>
                        <input type="number" name="stock_minimo" value="<?php echo $producto_editar ? intval($producto_editar['stock_minimo']) : '5'; ?>" min="1" required>
                    </div>

                    <div class="form-group-prod">
                        <label><i class="fa-solid fa-image"></i> Foto del Producto:</label>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <button type="submit" class="btn-guardar-prod">
                        <i class="fa-solid fa-floppy-disk"></i> <?php echo $producto_editar ? 'Actualizar Producto' : 'Guardar Producto en Inventario'; ?>
                    </button>

                    <?php if ($producto_editar): ?>
                        <a href="productos.php" class="btn-cancelar-edit"><i class="fa-solid fa-xmark"></i> Cancelar Edición</a>
                    <?php endif; ?>
                </div>
            </form>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

            <!-- Listado con Acciones de Edición y Eliminación -->
            <h3 style="text-align: left; margin-bottom: 15px;"><i class="fa-solid fa-list"></i> Productos Registrados</h3>
            <table class="prod-table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Precio Venta</th>
                        <th>Stock Actual</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($productos_res && $productos_res->num_rows > 0): ?>
                        <?php while($p = $productos_res->fetch_assoc()): ?>
                            <?php 
                                $precio_mostrar = isset($p['precio_venta']) ? $p['precio_venta'] : 0;
                                $img_src = (!empty($p['imagen']) && file_exists('uploads/' . $p['imagen'])) ? 'uploads/' . htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8') : 'https://via.placeholder.com/45?text=BZone';
                            ?>
                            <tr>
                                <td><img src="<?php echo $img_src; ?>" alt="Foto" class="img-preview-thumb"></td>
                                <td><strong><?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><code><?php echo !empty($p['codigo_barras']) ? htmlspecialchars($p['codigo_barras'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></code></td>
                                <td>$<?php echo number_format($precio_mostrar, 2); ?></td>
                                <td><?php echo intval($p['stock']); ?> unidades</td>
                                <td>
                                    <?php if ($p['stock'] <= $p['stock_minimo']): ?>
                                        <span style="color: #c62828; font-weight: bold; background: #ffebee; padding: 4px 8px; border-radius: 6px; font-size: 11px;">Agotándose</span>
                                    <?php else: ?>
                                        <span style="color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 4px 8px; border-radius: 6px; font-size: 11px;">Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="productos.php?editar=<?php echo intval($p['id']); ?>" class="btn-action-edit" title="Editar"><i class="fa-solid fa-pen"></i> Editar</a>
                                    <a href="productos.php?eliminar=<?php echo intval($p['id']); ?>" class="btn-action-delete" onclick="return confirm('¿Estás seguro de eliminar este producto?');" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: #888;">No hay productos en el inventario.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>