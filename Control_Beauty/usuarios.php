<?php
include('conexion.php');

// Verificar sesión y que el rol sea Administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') { 
    header("Location: dashboard.php"); 
    exit(); 
}

$mensaje = "";
$error = "";

// 1. Crear nuevo usuario (Con consultas preparadas)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_usuario'])) {
    $usuario = trim($_POST['usuario']);
    $nombre = trim($_POST['nombre']);
    $password = trim($_POST['password']);
    $rol = trim($_POST['rol']);

    if (!empty($usuario) && !empty($nombre) && !empty($password)) {
        // Verificar si el usuario ya existe
        $stmt_check = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check && $res_check->num_rows > 0) {
            $error = "El nombre de usuario ya está en uso.";
        } else {
            $stmt_ins = $conexion->prepare("INSERT INTO usuarios (usuario, nombre, password, rol) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("ssss", $usuario, $nombre, $password, $rol);
            if ($stmt_ins->execute()) {
                $mensaje = "Usuario registrado correctamente.";
            } else {
                $error = "Error al registrar el usuario.";
            }
            $stmt_ins->close();
        }
        $stmt_check->close();
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// 2. Cambiar contraseña de un usuario (Con consulta preparada)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambiar_password'])) {
    $id_usuario = intval($_POST['user_id']);
    $nueva_password = trim($_POST['nueva_password']);

    if (!empty($nueva_password)) {
        $stmt_upd = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $nueva_password, $id_usuario);
        if ($stmt_upd->execute()) {
            $mensaje = "Contraseña actualizada correctamente.";
        } else {
            $error = "Error al actualizar la contraseña.";
        }
        $stmt_upd->close();
    } else {
        $error = "La contraseña no puede estar vacía.";
    }
}

// Consultar la lista de usuarios
$usuarios_res = $conexion->query("SELECT id, usuario, nombre, rol, ultimo_acceso FROM usuarios");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - BZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=3.0">
    <style>
        .users-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
            margin-top: 20px;
            text-align: left;
        }
        .form-card, .table-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .form-card h3, .table-card h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #111;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .btn-submit {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background: #d81b60;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            font-size: 13px;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f8f8;
            text-align: left;
        }
        .inline-form {
            display: flex;
            gap: 6px;
        }
        .inline-form input {
            padding: 6px;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 110px;
        }
        .btn-sm {
            padding: 6px 10px;
            background: #1a1a1a;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
        }
        .btn-sm:hover {
            background: #d81b60;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-brand">
            <img src="Logo.png" alt="BZone Logo" onerror="this.style.display='none'">
            <span><strong>BZone</strong> | <?php echo htmlspecialchars($_SESSION['nombre'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8'); ?>)</span>
        </div>
    </header>

    <div class="main-wrapper" style="max-width: 1050px;">
        <div class="watermark-bg">BZone</div>

        <div class="dashboard-card" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">Gestión de Usuarios</h2>
                <a href="dashboard.php" class="btn-sm" style="text-decoration: none; padding: 8px 15px; font-size: 13px;"><i class="fa-solid fa-arrow-left"></i> Volver al Inicio</a>
            </div>

            <?php if ($mensaje != ""): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error != ""): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="users-grid">
                <!-- Formulario para Registrar Nuevo Usuario -->
                <div class="form-card">
                    <h3><i class="fa-solid fa-user-plus"></i> Nuevo Usuario</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="crear_usuario" value="1">
                        
                        <div class="form-group">
                            <label>Nombre de Usuario (Login)</label>
                            <input type="text" name="usuario" placeholder="Ej. maria" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre" placeholder="Ej. María Pérez" required>
                        </div>

                        <div class="form-group">
                            <label>Contraseña</label>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label>Rol de Usuario</label>
                            <select name="rol" required>
                                <option value="Empleado">Empleado</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-submit">Guardar Usuario</button>
                    </form>
                </div>

                <!-- Tabla de Usuarios Registrados con opción de cambiar clave -->
                <div class="table-card">
                    <h3><i class="fa-solid fa-users"></i> Usuarios Registrados</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario / Nombre</th>
                                <th>Rol</th>
                                <th>Cambiar Contraseña</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usuarios_res && $usuarios_res->num_rows > 0): ?>
                                <?php while($u = $usuarios_res->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </td>
                                        <td><span style="font-size: 11px; padding: 3px 8px; background: #eee; border-radius: 4px; font-weight: 600;"><?php echo htmlspecialchars($u['rol'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td>
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="cambiar_password" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo intval($u['id']); ?>">
                                                <input type="password" name="nueva_password" placeholder="Nueva clave" required>
                                                <button type="submit" class="btn-sm" title="Actualizar Clave"><i class="fa-solid fa-key"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No se encontraron usuarios.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>