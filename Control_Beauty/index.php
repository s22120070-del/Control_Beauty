<?php
include('conexion.php');

// Si el usuario ya está autenticado, redirigir según su rol
if (isset($_SESSION['usuario_id'])) {
    if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'cliente') {
        header("Location: catalogo_cliente.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar Token Anti-CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Error de validación de seguridad. Por favor, intenta de nuevo.";
    } else {
        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password']);

        // Verificar credenciales
        $stmt = $conexion->prepare("SELECT id, nombre, rol FROM usuarios WHERE usuario = ? AND password = ? LIMIT 1");
        $stmt->bind_param("ss", $usuario, $password);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();
            
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['rol'] = $row['rol'];
            $_SESSION['hora_inicio'] = time();

            $stmt_upd = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            $stmt_upd->bind_param("i", $row['id']);
            @$stmt_upd->execute();
            $stmt_upd->close();

            // Redirección según el rol (Administrador vs Cliente)
            if (strtolower($row['rol']) === 'cliente') {
                header("Location: catalogo_cliente.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - BZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="estilos.css?v=2.0">
    
    <style>
        body.login-body {
            background: linear-gradient(135deg, #111111 0%, #222222 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .login-center-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 80px;
            padding: 45px 20px;
            position: relative;
        }

        .side-illustrations {
            display: flex;
            flex-direction: column;
            gap: 45px;
            align-items: center;
            justify-content: center;
        }

        .art-badge {
            width: 102px;
            height: 102px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            border: 1.5px solid rgba(216, 27, 96, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease, border-color 0.3s ease, background 0.3s ease;
        }

        .art-badge:hover {
            transform: translateY(-5px) scale(1.05);
            border-color: #d81b60;
            background: rgba(216, 27, 96, 0.1);
        }

        .art-badge span {
            font-size: 13px;
            color: #d0d0d0;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .side-left .art-badge:nth-child(2) { transform: translateX(-35px); }
        .side-left .art-badge:nth-child(2):hover { transform: translateX(-35px) translateY(-5px) scale(1.05); }
        .side-right .art-badge:nth-child(2) { transform: translateX(35px); }
        .side-right .art-badge:nth-child(2):hover { transform: translateX(35px) translateY(-5px) scale(1.05); }

        .login-card {
            background: #ffffff;
            padding: 48px 42px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 420px;
            text-align: center;
            z-index: 2;
        }

        .login-header img {
            width: 145px;
            height: auto;
            margin-bottom: 14px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .login-header h2 {
            margin: 0;
            color: #111111;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .login-header p {
            margin: 8px 0 30px 0;
            color: #555555;
            font-size: 16px;
        }

        .form-group-login {
            text-align: left;
            margin-bottom: 24px;
        }

        .form-group-login label {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #222;
            margin-bottom: 8px;
        }

        .input-with-icon {
            display: flex;
            align-items: center;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            padding: 0 16px;
            background: #fafafa;
            gap: 12px;
            transition: border-color 0.3s;
        }

        .input-with-icon:focus-within {
            border-color: #d81b60;
            background: #ffffff;
        }

        .input-with-icon i {
            color: #777;
            font-size: 17px;
        }

        .input-with-icon input {
            border: none;
            background: transparent;
            padding: 15px 0;
            width: 100%;
            outline: none;
            font-size: 16px;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: #111111;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #d81b60;
            box-shadow: 0 6px 18px rgba(216, 27, 96, 0.3);
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 20px;
        }

        .beauty-footer {
            background-color: #0a0a0a;
            color: #e0e0e0;
            border-top: 3px solid #d81b60;
            padding: 45px 30px 25px;
            width: 100%;
            box-sizing: border-box;
        }

        .footer-inner {
            max-width: 1150px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 35px;
            padding-bottom: 35px;
            border-bottom: 1px solid #222222;
        }

        .footer-brand-col h3 {
            margin: 0 0 12px 0;
            font-size: 26px;
            color: #ffffff;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-brand-col h3 span { color: #d81b60; }
        .footer-brand-col p { margin: 0 0 18px 0; font-size: 15px; color: #b0b0b0; line-height: 1.6; }
        .footer-badges { display: flex; gap: 12px; flex-wrap: wrap; }

        .footer-pill {
            font-size: 13.5px;
            font-weight: 600;
            background: #181818;
            border: 1px solid #2d2d2d;
            color: #f8bbd0;
            padding: 7px 15px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .footer-col h4 {
            margin: 0 0 16px 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-col h4::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 34px;
            height: 2.5px;
            background-color: #d81b60;
        }

        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { font-size: 15px; color: #cccccc; margin-bottom: 11px; display: flex; align-items: center; gap: 9px; }
        .footer-col ul li i { color: #d81b60; font-size: 13px; }

        .footer-bottom {
            max-width: 1150px;
            margin: 22px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 13.5px;
            color: #888888;
        }

        @media (max-width: 900px) {
            .side-illustrations { display: none; }
            .footer-inner { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 560px) {
            .footer-inner { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body class="login-body">

    <div class="login-center-area">
        <!-- DIBUJOS LADO IZQUIERDO -->
        <div class="side-illustrations side-left">
            <div class="art-badge" title="Labiales">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ff6090" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9.5 11V4.5l4-2v8.5"/><rect x="7.5" y="11" width="8" height="3.5" rx="1"/><rect x="6.5" y="14.5" width="10" height="7" rx="1.5"/>
                </svg>
                <span>Labiales</span>
            </div>
            <div class="art-badge" title="Rímel">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ff6090" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="10" width="5" height="11" rx="1"/><path d="M5 10V7h3v3"/><rect x="14" y="3" width="4" height="6" rx="1"/><line x1="16" y1="9" x2="16" y2="15"/><line x1="14" y1="15.5" x2="18" y2="15.5"/><line x1="13.5" y1="17.5" x2="18.5" y2="17.5"/><line x1="14" y1="19.5" x2="18" y2="19.5"/>
                </svg>
                <span>Rímel</span>
            </div>
            <div class="art-badge" title="Bases">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ff6090" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 3h5"/><path d="M12 3v3"/><rect x="9.5" y="6" width="5" height="3" rx="0.5"/><rect x="7" y="9" width="10" height="12" rx="2"/><line x1="9" y1="14" x2="15" y2="14"/>
                </svg>
                <span>Bases</span>
            </div>
        </div>

        <!-- TARJETA DE LOGIN -->
        <div class="login-card">
            <div class="login-header">
                <img src="Logo.png" alt="BZone Logo" onerror="this.src='https://via.placeholder.com/150?text=BZone'">
                <h2>BZone</h2>
                <p>Control de Inventario & Ventas</p>
            </div>

            <?php if ($error != ""): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group-login">
                    <label>Usuario</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="usuario" placeholder="Ej. admin o cliente" required autocomplete="off">
                    </div>
                </div>

                <div class="form-group-login">
                    <label>Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">Ingresar al Sistema</button>
            </form>
        </div>

        <!-- DIBUJOS LADO DERECHO -->
        <div class="side-illustrations side-right">
            <div class="art-badge" title="Bolsas">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ff6090" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 8h14l1.2 12H3.8L5 8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2"/><circle cx="12" cy="13" r="1"/>
                </svg>
                <span>Bolsas</span>
            </div>
            <div class="art-badge" title="Aretes">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ff6090" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7.5 6a1.5 1.5 0 0 1 3 0c0 1.5-1.5 2-1.5 3"/><polygon points="9,9 6,14 9,19 12,14"/><path d="M14.5 7a1.5 1.5 0 0 1 3 0c0 1.5-1.5 2-1.5 3"/><polygon points="16,10 13.5,14.5 16,19 18.5,14.5"/>
                </svg>
                <span>Aretes</span>
            </div>
            <div class="art-badge" title="Pinzas">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ff6090" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 15c2-6 5-9 8-9s6 3 8 9"/><path d="M4 15h16"/><line x1="7" y1="15" x2="7" y2="18"/><line x1="10" y1="15" x2="10" y2="18.5"/><line x1="14" y1="15" x2="14" y2="18.5"/><line x1="17" y1="15" x2="17" y2="18"/>
                </svg>
                <span>Pinzas</span>
            </div>
        </div>
    </div>

    <!-- PIE DE PÁGINA INFORMATIVO -->
    <footer class="beauty-footer">
        <div class="footer-inner">
            <div class="footer-brand-col">
                <h3>BZone <span>Beauty</span></h3>
                <p>Tu boutique especializada en cosméticos de alta calidad, tendencias de maquillaje y accesorios de moda para resaltar tu estilo cada día.</p>
                <div class="footer-badges">
                    <span class="footer-pill"><i class="fa-solid fa-wand-magic-sparkles"></i> Maquillaje</span>
                    <span class="footer-pill"><i class="fa-solid fa-gem"></i> Accesorios</span>
                </div>
            </div>
            <div class="footer-col">
                <h4>Maquillaje</h4>
                <ul>
                    <li><i class="fa-solid fa-angle-right"></i> Labiales y Gloss</li>
                    <li><i class="fa-solid fa-angle-right"></i> Bases, Polvos y Rubores</li>
                    <li><i class="fa-solid fa-angle-right"></i> Paletas de Sombras</li>
                    <li><i class="fa-solid fa-angle-right"></i> Delineadores y Máscaras</li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Accesorios</h4>
                <ul>
                    <li><i class="fa-solid fa-angle-right"></i> Joyería y Bisutería</li>
                    <li><i class="fa-solid fa-angle-right"></i> Sets de Brochas y Esponjas</li>
                    <li><i class="fa-solid fa-angle-right"></i> Bolsos y Cosmetiqueras</li>
                    <li><i class="fa-solid fa-angle-right"></i> Espejos y Complementos</li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Sistema BZone</h4>
                <ul>
                    <li><i class="fa-solid fa-check"></i> Vista Administrador y Clientes</li>
                    <li><i class="fa-solid fa-check"></i> Control de Stock por Tono</li>
                    <li><i class="fa-solid fa-check"></i> Catálogo Virtual Interactivo</li>
                    <li><i class="fa-solid fa-check"></i> Punto de Venta Rápido</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?php echo date("Y"); ?> <strong>BZone</strong> — Maquillaje & Accesorios. Todos los derechos reservados.</span>
            <span>Portal de Clientes y Administración</span>
        </div>
    </footer>
</body>
</html>