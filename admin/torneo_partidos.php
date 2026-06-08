<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

$torneo_id = (int)($_GET["torneo_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| TORNEO
|--------------------------------------------------------------------------
*/

$sqlTorneo = "

SELECT *

FROM torneos

WHERE id = :id

LIMIT 1

";

$stmtTorneo = $pdo->prepare($sqlTorneo);

$stmtTorneo->execute([
    ":id" => $torneo_id
]);

$torneo = $stmtTorneo->fetch(PDO::FETCH_ASSOC);

if(!$torneo){

    die("Torneo no encontrado");

}

/*
|--------------------------------------------------------------------------
| PARTIDOS
|--------------------------------------------------------------------------
*/

$sqlPartidos = "

SELECT

    p.id,
    p.ronda,
    p.fecha,
    p.hora,
    p.estado,
    c.nombre as categoria

FROM partidos p

INNER JOIN categorias c
    ON c.id = p.categoria_id

WHERE p.torneo_id = :torneo_id

ORDER BY

    p.fecha ASC,
    p.hora ASC,
    p.id DESC

";

$stmtPartidos = $pdo->prepare($sqlPartidos);

$stmtPartidos->execute([
    ":torneo_id" => $torneo_id
]);

$partidos = $stmtPartidos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Partidos torneo</title>

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

.topbar{

    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin-bottom:20px;

}

.title{

    font-size:28px;
    font-weight:bold;

}

.button{

    background:#067ec9;
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:14px;
    font-weight:bold;

}

.subtitle{

    opacity:.7;
    margin-top:8px;
    margin-bottom:24px;

}

.actions{

    display:flex;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:24px;

}

.card{

    background:#151935;
    border-radius:22px;
    padding:20px;
    margin-bottom:16px;

}

.partido-top{

    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;

}

.ronda{

    font-size:20px;
    font-weight:bold;

}

.categoria{

    opacity:.7;
    margin-top:6px;

}

.estado{

    background:#0f1223;
    padding:8px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:bold;
    white-space:nowrap;

}

.meta{

    display:grid;
    gap:10px;
    opacity:.8;
    margin-bottom:18px;

}

.link{

    background:#0f1223;
    color:white;
    text-decoration:none;
    padding:12px 14px;
    border-radius:12px;
    display:inline-block;
    font-weight:bold;

}

.empty{

    background:#151935;
    border-radius:22px;
    padding:24px;
    opacity:.7;

}

</style>

</head>

<body>

<div class="container">

<div class="topbar">

    <div class="title">
        <?= htmlspecialchars($torneo["nombre"]) ?>
    </div>

    <a
        href="torneos.php"
        class="button"
    >
        ← Torneos
    </a>

</div>

<div class="subtitle">

    <?= htmlspecialchars($torneo["tipo"]) ?>

</div>

<div class="actions">

    <a
        href="crear_partido.php?torneo_id=<?= $torneo_id ?>"
        class="button"
    >
        + Crear partido
    </a>

</div>

<?php if($partidos): ?>

    <?php foreach($partidos as $partido): ?>

        <div class="card">

            <div class="partido-top">

                <div>

                    <div class="ronda">

                        <?= htmlspecialchars(
                            $partido["ronda"] ?: "Sin ronda"
                        ) ?>

                    </div>

                    <div class="categoria">

                        <?= htmlspecialchars($partido["categoria"]) ?>

                    </div>

                </div>

                <div class="estado">

                    <?= htmlspecialchars($partido["estado"]) ?>

                </div>

            </div>

            <div class="meta">

                <div>

                    Fecha:
                    <?= $partido["fecha"] ?: "-" ?>

                </div>

                <div>

                    Hora:
                    <?= $partido["hora"]
                        ? substr($partido["hora"],0,5)
                        : "-" ?>

                </div>

            </div>

            <a
                href="editar_partido.php?id=<?= $partido["id"] ?>"
                class="link"
            >
                Editar partido
            </a>

        </div>

    <?php endforeach; ?>

<?php else: ?>

    <div class="empty">

        Este torneo todavía no tiene partidos.

    </div>

<?php endif; ?>

</div>

</body>
</html>
