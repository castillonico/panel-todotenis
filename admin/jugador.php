<?php 

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

$id = (int)($_GET["id"] ?? 0);

$sqlJugador = "

SELECT *

FROM jugadores

WHERE id = :id

LIMIT 1

";

$stmtJugador = $pdo->prepare($sqlJugador);

$stmtJugador->execute([
    ":id" => $id
]);

$jugador = $stmtJugador->fetch(PDO::FETCH_ASSOC);

if(!$jugador){

    die("Jugador no encontrado");

}

$sqlStats = "

SELECT

    COUNT(DISTINCT p.id) as partidos,

    SUM(
        CASE
            WHEN p.ganador_equipo = pj.equipo
            THEN 1
            ELSE 0
        END
    ) as victorias,

    SUM(
        CASE
            WHEN p.ganador_equipo IS NOT NULL
            AND p.ganador_equipo != pj.equipo
            THEN 1
            ELSE 0
        END
    ) as derrotas

FROM partido_jugadores pj

INNER JOIN partidos p
    ON p.id = pj.partido_id

WHERE pj.jugador_id = :id

";

$stmtStats = $pdo->prepare($sqlStats);

$stmtStats->execute([
    ":id" => $id
]);

$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$sqlCategorias = "

SELECT

    c.nombre,
    COALESCE(SUM(rm.puntos),0) as puntos

FROM ranking_movimientos rm

INNER JOIN categorias c
    ON c.id = rm.categoria_id

WHERE rm.jugador_id = :id

GROUP BY c.id

ORDER BY puntos DESC

";

$stmtCategorias = $pdo->prepare($sqlCategorias);

$stmtCategorias->execute([
    ":id" => $id
]);

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$sqlPartidos = "

SELECT

    p.*,
    c.nombre as categoria

FROM partido_jugadores pj

INNER JOIN partidos p
    ON p.id = pj.partido_id

INNER JOIN categorias c
    ON c.id = p.categoria_id

WHERE pj.jugador_id = :id

ORDER BY p.id DESC

LIMIT 20

";

$stmtPartidos = $pdo->prepare($sqlPartidos);

$stmtPartidos->execute([
    ":id" => $id
]);

$partidos = $stmtPartidos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($jugador["nombre"]) ?></title>

<style>

body{

    margin:0;
    background:#0f1223;
    color:#fefefe;
    font-family:Arial,sans-serif;
    padding:14px;

}

.container{

    max-width:1000px;
    margin:auto;

}

.button{

    background:#067ec9;
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:12px;
    display:inline-block;
    font-weight:bold;
    margin-bottom:20px;

}

.profile-card{

    background:#151935;
    border-radius:22px;
    padding:24px;
    margin-bottom:24px;

}

.player-name{

    font-size:32px;
    font-weight:bold;
    margin-bottom:10px;

}

.player-club{

    opacity:.75;
    font-size:18px;

}

.stats-grid{

    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:16px;
    margin-top:24px;

}

.stat-box{

    background:#0f1223;
    border-radius:18px;
    padding:20px;
    text-align:center;

}

.stat-value{

    font-size:30px;
    font-weight:bold;
    margin-bottom:8px;

}

.stat-label{

    opacity:.7;

}

.section{

    background:#151935;
    border-radius:22px;
    padding:24px;
    margin-bottom:24px;

}

.section h2{

    margin-top:0;

}

.category-row{

    padding:14px 0;
    border-bottom:1px solid rgba(255,255,255,.08);

}

.match-card{

    background:#0f1223;
    border-radius:18px;
    padding:18px;
    margin-bottom:14px;

}

.match-category{

    font-weight:bold;
    margin-bottom:8px;

}

.match-info{

    opacity:.75;
    line-height:1.5;

}

.empty{

    opacity:.6;

}

@media(max-width:768px){

    .player-name{

        font-size:26px;

    }

}

</style>

</head>

<body>

<div class="container">

    <a
        href="jugadores.php"
        class="button"
    >
        ← Jugadores
    </a>

    <div class="profile-card">

        <div class="player-name">
            <?= htmlspecialchars($jugador["nombre"]) ?>
        </div>

        <div class="player-club">
            <?= htmlspecialchars($jugador["club"]) ?>
        </div>

        <div class="stats-grid">

            <div class="stat-box">

                <div class="stat-value">
                    <?= (int)$stats["partidos"] ?>
                </div>

                <div class="stat-label">
                    Partidos
                </div>

            </div>

            <div class="stat-box">

                <div class="stat-value">
                    <?= (int)$stats["victorias"] ?>
                </div>

                <div class="stat-label">
                    Victorias
                </div>

            </div>

            <div class="stat-box">

                <div class="stat-value">
                    <?= (int)$stats["derrotas"] ?>
                </div>

                <div class="stat-label">
                    Derrotas
                </div>

            </div>

        </div>

    </div>

    <div class="section">

        <h2>
            Categorías y puntos
        </h2>

        <?php if($categorias): ?>

            <?php foreach($categorias as $categoria): ?>

                <div class="category-row">

                    <strong>
                        <?= htmlspecialchars($categoria["nombre"]) ?>
                    </strong>

                    <br><br>

                    <?= $categoria["puntos"] ?> puntos

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                Sin puntos registrados
            </div>

        <?php endif; ?>

    </div>

    <div class="section">

        <h2>
            Últimos partidos
        </h2>

        <?php if($partidos): ?>

            <?php foreach($partidos as $partido): ?>

                <div class="match-card">

                    <div class="match-category">
                        <?= htmlspecialchars($partido["categoria"]) ?>
                    </div>

                    <div class="match-info">

                        Estado:
                        <?= htmlspecialchars($partido["estado"]) ?>

                        <br>

                        Fecha:
                        <?= $partido["fecha_partido"] ?: "Sin programar" ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                Sin partidos registrados
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
