<?php
include('conexion.php');

// Verificar que haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$nombre_cliente = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Cliente';

// Consultar productos disponibles con stock mayor a 0
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
if ($busqueda !== '') {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE stock > 0 AND nombre LIKE ? ORDER BY id DESC");
    $like = "%" . $busqueda . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conexion->query("SELECT * FROM productos WHERE stock > 0 ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Clientes - BZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=2.0">
    <style>
        body {
            background-color: #f4f5f7;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Barra superior exclusiva de Clientes */
        .client-header {
            background-color: #111111;
            color: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #d81b60;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .client-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-brand img {
            height: 40px;
            background: #fff;
            padding: 3px;
            border-radius: 8px;
        }

        .client-brand h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }

        .client-brand h1 span {
            color: #d81b60;
        }

        .client-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .user-welcome {
            font-size: 14px;
            color: #dddddd;
        }

        .btn-cart-top {
            background: #d81b60;
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .btn-cart-top:hover {
            background: #ad1457;
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background: #c62828;
        }

        /* Banner de bienvenida */
        .hero-banner {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .hero-banner h2 {
            margin: 0 0 10px 0;
            font-size: 30px;
        }

        .hero-banner p {
            margin: 0 0 22px 0;
            color: #cccccc;
            font-size: 16px;
        }

        .search-bar {
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
            padding: 5px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .search-bar input {
            flex: 1;
            border: none;
            padding: 12px 20px;
            font-size: 15px;
            outline: none;
        }

        .search-bar button {
            background: #d81b60;
            color: white;
            border: none;
            padding: 0 24px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
        }

        /* Cuadrícula de productos */
        .catalog-container {
            max-width: 1180px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            border: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(216, 27, 96, 0.15);
            border-color: #f8bbd0;
        }

        .product-img-box {
            height: 200px;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-img-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-info {
            padding: 18px;
            text-align: left;
        }

        .product-code {
            font-size: 11.5px;
            color: #888888;
            text-transform: uppercase;
        }

        .product-title {
            margin: 5px 0 8px 0;
            font-size: 17px;
            color: #111111;
            font-weight: 700;
        }

        .product-stock {
            font-size: 13px;
            color: #2e7d32;
            background: #e8f5e9;
            padding: 3px 10px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 12px;
        }

        .product-price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 12px;
            border-top: 1px solid #f2f2f2;
        }

        .product-price {
            font-size: 20px;
            font-weight: 800;
            color: #d81b60;
        }

        .btn-add-cart {
            background: #111111;
            color: #ffffff;
            border: none;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-add-cart:hover {
            background: #d81b60;
        }

        /* Modal Carrito del Cliente */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-cart {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .modal-header h3 { margin: 0; font-size: 20px; }
        .close-modal { background: none; border: none; font-size: 22px; cursor: pointer; }

        .cart-items-list {
            max-height: 280px;
            overflow-y: auto;
            margin-bottom: 18px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f2f2f2;
            font-size: 14.5px;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #111;
        }

        .btn-confirm-order {
            width: 100%;
            padding: 14px;
            background: #d81b60;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- ENCABEZADO CLIENTE -->
    <header class="client-header">
        <div class="client-brand">
            <img src="Logo.png" alt="BZone" onerror="this.style.display='none'">
            <h1>BZone <span>Tienda</span></h1>
        </div>
        <div class="client-actions">
            <span class="user-welcome"><i class="fa-solid fa-user"></i> Hola, <strong><?php echo htmlspecialchars($nombre_cliente); ?></strong></span>
            <button class="btn-cart-top" onclick="abrirCarrito()">
                <i class="fa-solid fa-bag-shopping"></i> Mi Bolsa (<span id="cart-count">0</span>)
            </button>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </div>
    </header>

    <!-- BANNER DE CATÁLOGO -->
    <section class="hero-banner">
        <h2>Catálogo de Maquillaje & Accesorios</h2>
        <p>Explora nuestros productos disponibles y arma tu pedido en línea</p>
        <form method="GET" class="search-bar">
            <input type="text" name="buscar" placeholder="Buscar labial, rímel, bolsa, aretes..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </form>
    </section>

    <!-- LISTA DE PRODUCTOS -->
    <main class="catalog-container">
        <div class="products-grid">
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while ($prod = $resultado->fetch_assoc()): ?>
                    <?php
                        // Detectar columna de precio (puede llamarse precio o precio_venta según tu BD)
                        $precio = isset($prod['precio_venta']) ? $prod['precio_venta'] : (isset($prod['precio']) ? $prod['precio'] : 0);
                        // Detectar imagen si existe en uploads/
                        $ruta_img = !empty($prod['imagen']) ? 'uploads/' . $prod['imagen'] : 'Logo.png';
                    ?>
                    <div class="product-card">
                        <div class="product-img-box">
                            <img src="<?php echo htmlspecialchars($ruta_img); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" onerror="this.src='Logo.png'">
                        </div>
                        <div class="product-info">
                            <span class="product-code">Código: <?php echo htmlspecialchars($prod['codigo'] ?? $prod['id']); ?></span>
                            <h3 class="product-title"><?php echo htmlspecialchars($prod['nombre']); ?></h3>
                            <span class="product-stock"><i class="fa-solid fa-check"></i> Disponibles: <?php echo (int)$prod['stock']; ?></span>
                            
                            <div class="product-price-row">
                                <span class="product-price">$<?php echo number_format($precio, 2); ?></span>
                                <button class="btn-add-cart" onclick="agregarAlCarrito(<?php echo $prod['id']; ?>, '<?php echo addslashes(htmlspecialchars($prod['nombre'])); ?>', <?php echo $precio; ?>)">
                                    <i class="fa-solid fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; font-size: 16px; color: #666;">No se encontraron productos disponibles con ese nombre.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- VENTANA EMERGENTE DEL CARRITO -->
    <div class="modal-overlay" id="modal-carrito">
        <div class="modal-cart">
            <div class="modal-header">
                <h3><i class="fa-solid fa-bag-shopping" style="color:#d81b60;"></i> Tu Bolsa de Compras</h3>
                <button class="close-modal" onclick="cerrarCarrito()">&times;</button>
            </div>
            <div class="cart-items-list" id="cart-items">
                <p style="text-align:center; color:#777;">Tu bolsa está vacía.</p>
            </div>
            <div class="cart-total">
                <span>Total estimado:</span>
                <span id="cart-total-price">$0.00</span>
            </div>
            <button class="btn-confirm-order" onclick="finalizarPedido()">Confirmar Pedido</button>
        </div>
    </div>

    <script>
        let carrito = [];

        function agregarAlCarrito(id, nombre, precio) {
            const existente = carrito.find(item => item.id === id);
            if (existente) {
                existente.cantidad++;
            } else {
                carrito.push({ id, nombre, precio, cantidad: 1 });
            }
            actualizarVistaCarrito();
        }

        function eliminarDelCarrito(index) {
            carrito.splice(index, 1);
            actualizarVistaCarrito();
        }

        function actualizarVistaCarrito() {
            const countSpan = document.getElementById('cart-count');
            const listDiv = document.getElementById('cart-items');
            const totalSpan = document.getElementById('cart-total-price');

            let totalItems = 0;
            let totalPrecio = 0;
            listDiv.innerHTML = '';

            if (carrito.length === 0) {
                listDiv.innerHTML = '<p style="text-align:center; color:#777;">Tu bolsa está vacía.</p>';
            } else {
                carrito.forEach((item, index) => {
                    totalItems += item.cantidad;
                    totalPrecio += item.precio * item.cantidad;
                    listDiv.innerHTML += `
                        <div class="cart-item">
                            <div>
                                <strong>${item.nombre}</strong><br>
                                <small>${item.cantidad} x $${item.precio.toFixed(2)}</small>
                            </div>
                            <div>
                                <strong>$${(item.precio * item.cantidad).toFixed(2)}</strong>
                                <button onclick="eliminarDelCarrito(${index})" style="background:none; border:none; color:#c62828; cursor:pointer; margin-left:8px;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            }

            countSpan.textContent = totalItems;
            totalSpan.textContent = '$' + totalPrecio.toFixed(2);
        }

        function abrirCarrito() {
            document.getElementById('modal-carrito').style.display = 'flex';
        }

        function cerrarCarrito() {
            document.getElementById('modal-carrito').style.display = 'none';
        }

        function finalizarPedido() {
            if (carrito.length === 0) {
                alert('Agrega al menos un producto a tu bolsa.');
                return;
            }
            alert('¡Pedido registrado con éxito! Puedes pasar a recogerlo o pagarlo en caja.');
            carrito = [];
            actualizarVistaCarrito();
            cerrarCarrito();
        }
    </script>
</body>
</html>