<?php
session_start();
require_once 'config/database.php';
// Incluir funciones_auth.php para usar sus funciones si es necesario aquí,
// aunque principalmente se usarán después del login.
// require_once 'funciones_auth.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password_ingresada = $_POST['password']; // Renombrado para claridad

    $stmt = $pdo->prepare("
        SELECT u.*, r.nombre_rol 
        FROM usuarios u
        LEFT JOIN roles r ON u.rol_id = r.id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password_ingresada, $usuario['password'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol_id'] = $usuario['rol_id']; // Guardar rol_id
        $_SESSION['nombre_rol'] = $usuario['nombre_rol']; // Guardar nombre_rol

        // Limpiar información de usuario y permisos cacheados de una sesión anterior
        unset($_SESSION['usuario_info']);
        unset($_SESSION['usuario_permisos']);
        
        // Opcional: Cargar permisos inmediatamente (o dejar que funciones_auth.php lo haga on-demand)
        // require_once 'funciones_auth.php'; // Asegurar que está incluido
        // obtener_permisos_usuario_actual(); // Esto cargará y cacheará los permisos en la sesión

        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .form-control {
            border-radius: 20px;
            padding: 10px 20px;
        }
        .btn-login {
            border-radius: 20px;
            padding: 10px 20px;
            background: #2980b9;
            border: none;
            width: 100%;
            margin-top: 20px;
        }
        .btn-login:hover {
            background: #3498db;
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Inicio de Sesión</h2>
        <?php if (isset($error)): ?>
            <div class="error-message text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Iniciar Sesión</button>
        </form>
        <div class="text-center mt-3">
            <a href="registro.php" class="text-decoration-none">¿No tienes cuenta? Regístrate</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>