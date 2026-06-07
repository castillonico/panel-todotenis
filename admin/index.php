<?php

require_once __DIR__ . "/../core/auth.php";


/* =========================
   LOGOUT (NUEVO - AGREGADO)
========================= */
if (isset($_GET["logout"])) {

    session_start();

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: index.php");
    exit;
}

$usuario = $_SESSION["usuario_panel"];

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Panel Todo Tenis</title>

<style>

/* === CSS ORIGINAL INTACTO === */

body{

    margin:0;
    background:#0f1223;
    color:#fefefe;
    font-family:Arial,sans-serif;
    padding:20px;

}

.container{

    max-width:1100px;
    margin:auto;

}

.topbar{

    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    gap:20px;
    flex-wrap:wrap;

}

.welcome{

    font-size:18px;
    opacity:.85;

}

.logout{

    background:#ff4d4f;
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:12px;
    font-weight:bold;

}

h1{

    margin-top:0;
    margin-bottom:10px;
    font-size:34px;

}

.subtitle{

    opacity:.7;
    margin-bottom:40px;
    line-height:1.6;

}

.grid{

    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:20px;

}

.card{

    background:#151935;
    border:1px solid rgba(6,126,201,.3);
    border-radius:22px;
    padding:28px;
    text-decoration:none;
    color:white;
    transition:.2s;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    min-height:180px;

}

.card:hover{

    transform:translateY(-3px);
    border-color:#067ec9;

}

.card-icon{

    font-size:42px;
    margin-bottom:20px;

}

.card-title{

    font-size:22px;
    font-weight:bold;
    margin-bottom:10px;

}

.card-text{

    opacity:.7;
    line-height:1.5;

}

.status-box{

    margin-top:40px;
    background:#151935;
    border-radius:20px;
    padding:24px;
    border:1px solid rgba(255,255,255,.08);

}

.status-title{

    font-size:18px;
    margin-bottom:14px;
    font-weight:bold;

}

.status-item{

    margin-bottom:10px;
    opacity:.8;

}

@media(max-width:768px){

    body{

        padding:14px;

    }

    h1{

        font-size:28px;

    }

    .grid{

        grid-template-columns:1fr;

    }

    .card{

        min-height:auto;
        padding:24px;

    }

    .card-title{

        font-size:24px;

    }

    .card-text{

        font-size:16px;

    }

    .logout{

        width:100%;
        text-align:center;
        box-sizing:border-box;

    }

}

</style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <div class="welcome">
            Bienvenido, <?= htmlspecialchars($usuario) ?>
        </div>

        <!-- SOLO CAMBIO: el botón ahora activa logout -->
       

    </div>

    <h1>
        🎾 Panel Todo Tenis
    </h1>

    <div class="subtitle">
        Centro operativo de carga y administración de torneos,
        partidos y rankings.
    </div>

    <div class="grid">

        <a href="jugadores.php" class="card">

            <div>
                <div class="card-icon">👤</div>
                <div class="card-title">Jugadores</div>
                <div class="card-text">Gestión completa de jugadores, perfiles y actividad.</div>
            </div>

        </a>

        <a href="crear_partido.php" class="card">

            <div>
                <div class="card-icon">📅</div>
                <div class="card-title">Crear partido</div>
                <div class="card-text">Programación de partidos singles y dobles.</div>
            </div>

        </a>

        <a href="listar_partidos.php" class="card">

            <div>
                <div class="card-icon">📋</div>
                <div class="card-title">Ver partidos</div>
                <div class="card-text">Gestión, edición y carga de resultados.</div>
            </div>

        </a>
        <a
href="torneos.php"
class="card">

    <div class="card-title">
        Torneos
    </div>

    <div class="card-description">
        Crear, administrar y organizar torneos
    </div>


</a>


    </div>


    <div class="status-box"></div>
    <div>
        <a
            href="?logout=1"
            class="logout"
        >
            Cerrar sesión
        </a>
    </div>
 
</div>

</body>
</html>
