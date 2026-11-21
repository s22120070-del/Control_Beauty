<?php
include('conexion.php');

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

// Calcular tiempo de sesión activa
$tiempo_conectado = 0;
if (isset($_SESSION['hora_inicio'])) {
    $tiempo_conectado = round((time() - $_SESSION['hora_inicio']) / 60);
}

// Consultar productos en alerta de stock
$sql_faltantes = "SELECT * FROM productos WHERE stock <= stock_minimo";
$res_faltantes = $conexion->query($sql_faltantes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - BZone</title>
    <!-- Iconos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=5.0">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, #111111 0%, #2a2a2a 100%);
            color: #ffffff;
            padding: 22px 28px;
            border-radius: 16px;
            margin-bottom: 25px;
            text-align: left;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #d81b60;
        }
        .welcome-text h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .welcome-text p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: #dddddd;
            line-height: 1.4;
        }
        .welcome-badge {
            background: rgba(216, 27, 96, 0.2);
            border: 1px solid #d81b60;
            color: #ff80ab;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
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

    <div class="main-wrapper">
        <div class="watermark-bg">BZone</div>

        <div class="dashboard-card">
            <!-- Banner con Saludo Dinámico AM/PM -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h3>
                        <i id="saludo-icono" class="fa-solid fa-sun" style="color: #ffca28;"></i> 
                        <span id="saludo-texto">¡Buenos días</span>, Usuario!
                    </h3>
                    <p id="saludo-frase">Cargando saludo...</p>
                </div>
                <div class="welcome-badge">
                    <i class="fa-solid fa-circle-check" style="color: #2e7d32;"></i> Sistema Listo
                </div>
            </div>

            <h2>Panel de Control</h2>

            <!-- Alerta de productos agotados o faltantes -->
            <?php if ($res_faltantes && $res_faltantes->num_rows > 0): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> 
                    <span><strong>Alerta del sistema:</strong> Hay <strong><?php echo $res_faltantes->num_rows; ?></strong> producto(s) faltantes que requieren surtirse.</span>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> 
                    <span><strong>Estado:</strong> Inventario en óptimas condiciones.</span>
                </div>
            <?php endif; ?>

            <!-- Menú de opciones -->
            <div class="menu-grid">
                <a href="dashboard.php" class="menu-card active">
                    <div class="icon-box"><i class="fa-solid fa-house"></i></div>
                    <span>Inicio</span>
                </a>
                <a href="ventas.php" class="menu-card">
                    <div class="icon-box"><i class="fa-solid fa-cash-register"></i></div>
                    <span>Ventas</span>
                </a>
                <a href="ventas_hoy.php" class="menu-card">
                    <div class="icon-box"><i class="fa-solid fa-calendar-day"></i></div>
                    <span>Ventas de Hoy</span>
                </a>
                <a href="productos.php" class="menu-card">
                    <div class="icon-box"><i class="fa-solid fa-box-open"></i></div>
                    <span>Agregar Producto</span>
                </a>
                <?php if($_SESSION['rol'] == 'Administrador'): ?>
                    <a href="usuarios.php" class="menu-card">
                        <div class="icon-box"><i class="fa-solid fa-users-gear"></i></div>
                        <span>Usuarios</span>
                    </a>
                    <a href="reportes.php" class="menu-card">
                        <div class="icon-box"><i class="fa-solid fa-chart-line"></i></div>
                        <span>Reporte Ganancias</span>
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="menu-card logout">
                    <div class="icon-box"><i class="fa-solid fa-door-open"></i></div>
                    <span>Cerrar Sesión</span>
                </a>
            </div>

            <!-- Indicador de tiempo activo -->
            <div class="time-footer">
                <div class="badge-tiempo">
                    ⏱️ Tiempo activo: <strong><?php echo $tiempo_conectado; ?> min</strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        function actualizarSaludoAMPM() {
            const ahora = new Date();
            const hora = ahora.getHours();
            
            const lblTexto = document.getElementById('saludo-texto');
            const lblFrase = document.getElementById('saludo-frase');
            const icono = document.getElementById('saludo-icono');

            // 06:00 AM - 11:59 AM -> Buenos días
            if (hora >= 6 && hora < 12) {
                lblTexto.innerText = "¡Buenos días";
                lblFrase.innerText = "Te deseamos una excelente jornada laboral, llena de ventas y el mejor servicio en BZone. 🚀✨";
                icono.className = "fa-solid fa-sun";
                icono.style.color = "#ffca28";
            } 
            // 12:00 PM - 06:59 PM (18:59 hrs) -> Buenas tardes
            else if (hora >= 12 && hora < 19) {
                lblTexto.innerText = "¡Buenas tardes";
                lblFrase.innerText = "Sigamos con la mejor actitud para cerrar un excelente día de ventas en BZone. 💪✨";
                icono.className = "fa-solid fa-cloud-sun";
                icono.style.color = "#ff9800";
            } 
            // 07:00 PM - 05:59 AM -> Buenas noches
            else {
                lblTexto.innerText = "¡Buenas noches";
                lblFrase.innerText = "Excelente trabajo el día de hoy. Recuerda revisar los reportes antes de concluir la jornada. 🌙✨";
                icono.className = "fa-solid fa-moon";
                icono.style.color = "#90caf9";
            }
        }

        actualizarSaludoAMPM();
        setInterval(actualizarSaludoAMPM, 60000);
    </script>
</body>
</html>