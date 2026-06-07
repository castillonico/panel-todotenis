<?php

session_start();

require_once "db.php";

$error = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $usuario = trim($_POST["usuario"]);
    $password = $_POST["password"];

    $sql = "

    SELECT *

    FROM usuarios_panel

    WHERE usuario = :usuario
    AND activo = 1

    LIMIT 1

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":usuario" => $usuario
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(
        $user &&
        password_verify($password, $user["password_hash"])
    ){

        $_SESSION["usuario_panel"] = $user["usuario"];

        header("Location: index.php");

        exit;

    }else{

        $error = "Usuario o contraseña incorrectos";

    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login Panel</title>

<style>

body{

    margin:0;
    background:#0f1223;
    color:#fefefe;
    font-family:Arial,sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    padding:20px;

}

.login-box{

    width:100%;
    max-width:420px;
    background:#151935;
    padding:32px;
    border-radius:20px;
    border:1px solid rgba(6,126,201,.35);

}

h1{

    text-align:center;
    margin-top:0;
    margin-bottom:28px;

}

input,
button{

    width:100%;
    padding:14px;
    margin-bottom:16px;
    border:none;
    border-radius:12px;
    font-size:16px;
    box-sizing:border-box;

}

input{

    background:#0f1223;
    color:#fefefe;

}

button{

    background:#067ec9;
    color:white;
    font-weight:bold;
    cursor:pointer;

}

.error{

    background:#ff4d4f22;
    border:1px solid #ff4d4f;
    color:#ffb3b3;
    padding:12px;
    border-radius:12px;
    margin-bottom:20px;
    text-align:center;

}

.login-logo{

    text-align:center;
    font-size:20px;
    margin-bottom:10px;
    opacity:.7;

}

</style>

</head>

<body>

<div class="login-box">

    <div class="login-logo">
        Todo Tenis
    </div>

    <h1>
        Panel Organizador
    </h1>

    <?php if($error): ?>

        <div class="error">
            <?= $error ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="usuario"
            placeholder="Usuario"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Contraseña"
            required
        >

        <button type="submit">
            Ingresar
        </button>

    </form>

</div>

</body>
</html>
