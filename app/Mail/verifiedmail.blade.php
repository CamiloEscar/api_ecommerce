<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        .button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h1>¡Hola, {{ $user->name }}!</h1>
    <p>Gracias por registrarte en nuestra tienda de Funkos. Para activar tu cuenta, por favor haz clic en el siguiente botón:</p>
    
    <p>
        <a href="http://localhost:4200/activar-cuenta/{{ $user->uniqd }}" class="button">
            Verificar mi cuenta
        </a>
    </p>

    <p>Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:</p>
    <p>http://localhost:4200/activar-cuenta/{{ $user->uniqd }}</p>
</body>
</html>