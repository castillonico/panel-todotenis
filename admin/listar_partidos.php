<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

/* =========================
   ELIMINAR PARTIDO (NUEVO)
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_partido"])) {

    $id = (int)$_POST["eliminar_partido"];

    try {

        $pdo->beginTransaction();

        // borrar movimientos ranking asociados
        $stmt = $pdo->prepare("
            DELETE FROM ranking_movimientos
            WHERE partido_id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        // borrar jugadores asociados
        $stmt = $pdo->prepare("
            DELETE FROM partido_jugadores
            WHERE partido_id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        // borrar partido
        $stmt = $pdo->prepare("
            DELETE FROM partidos
            WHERE id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        $pdo->commit();

    } catch(Exception $e){

        $pdo->rollBack();

        die("Error al eliminar partido");

    }

    header("Location: listar_partidos.php");
    exit;
}

/* =========================
   FILTROS
========================= */

$filtroEstado = $_GET["estado"] ?? "";
$filtroCategoria = $_GET["categoria"] ?? "";

/* =========================
   QUERY PRINCIPAL
========================= */

$sql = "

SELECT
    p.id,
    p.fecha,
    p.hora,
    p.sede,
    p.estado,
    p.modalidad,
    c.nombre AS categoria,
    t.nombre AS torneo,
    p.sets_json

FROM partidos p

INNER JOIN categorias c
ON p.categoria_id = c.id

INNER JOIN torneos t
ON p.torneo_id = t.id

ORDER BY

    CASE p.estado
        WHEN 'En juego' THEN 1
        WHEN 'Programado' THEN 2
        WHEN 'Borrador' THEN 3
        WHEN 'Finalizado' THEN 4
        ELSE 5
    END,

    p.fecha ASC,
    p.hora ASC

";

$stmt = $pdo->query($sql);

$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$partidos = array_filter($partidos, function($p) use ($filtroEstado, $filtroCategoria){

    $okEstado = !$filtroEstado || $p["estado"] === $filtroEstado;
    $okCategoria = !$filtroCategoria || $p["categoria"] === $filtroCategoria;

    return $okEstado && $okCategoria;
});

function obtenerJugadores($pdo, $partido_id, $equipo){

    $sql = "

    SELECT j.nombre

    FROM partido_jugadores pj

    INNER JOIN jugadores j
    ON pj.jugador_id = j.id

    WHERE
        pj.partido_id = :partido_id
        AND pj.equipo = :equipo

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":partido_id" => $partido_id,
        ":equipo" => $equipo
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Partidos</title>

<style>

/* 🔒 TU CSS ORIGINAL INTÁCTO (NO MODIFICADO) */

body{

    background:#0f1223;
    color:#fefefe;
    font-family:Arial,sans-serif;
    margin:0;
    padding:20px;

}

.match-list{

    max-width:900px;
    margin:auto;

}

.match-card{

    background:#151935;
    border-radius:18px;
    padding:18px;
    margin-bottom:18px;
    border:1px solid #067ec9;

}

.match-top{

    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:16px;
    font-size:14px;
    opacity:.9;

}

.match-players{

    display:flex;
    flex-direction:column;
    gap:12px;

}

.player-row{

    display:flex;
    justify-content:space-between;
    align-items:center;
    padding-bottom:10px;
    border-bottom:1px solid rgba(255,255,255,.08);

}

.player-name{

    font-size:16px;
    font-weight:600;

}

.sets{

    display:flex;
    gap:10px;

}

.set{

    width:34px;
    height:34px;
    border-radius:10px;
    background:#0f1223;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;

}

.match-footer{

    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:16px;
    font-size:14px;

}

.status{

    padding:8px 12px;
    border-radius:999px;
    font-weight:bold;

}

.status-programado{

    background:#067ec9;

}

.status-finalizado{

    background:#2d3748;

}

.status-jugando{

    background:#00b894;

}

.status-borrador{

    background:#6c5ce7;

}

@media(max-width:768px){

    body{padding:10px;}

    .match-card{padding:18px;}

    .match-top{
        flex-direction:column;
        align-items:flex-start;
        gap:8px;
        font-size:15px;
    }

    .player-row{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }

    .player-name{
        font-size:16px;
        line-height:1.5;
    }

    .sets{
        width:100%;
        justify-content:flex-start;
    }

    .set{
        width:40px;
        height:40px;
        font-size:16px;
    }

    .match-footer{
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }

    a{font-size:15px !important;}

}

</style>

</head>

<body>

<?php

$categoriasFiltro = $pdo->query("
    SELECT nombre FROM categorias ORDER BY nombre
")->fetchAll(PDO::FETCH_COLUMN);

?>

<div class="match-list">

<!-- PANEL -->
<div style="margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap;">

    <a href="index.php"
       style="background:#067ec9;color:white;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:bold;">
        ← Panel
    </a>

</div>

<!-- FILTROS -->
<form method="GET"
      style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">

    <select name="estado">
        <option value="">Todos los estados</option>

        <?php foreach(["Borrador","Programado","En juego","Finalizado"] as $estado): ?>
            <option value="<?= $estado ?>" <?= $filtroEstado===$estado?"selected":"" ?>>
                <?= $estado ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="categoria">
        <option value="">Todas las categorías</option>

        <?php foreach($categoriasFiltro as $categoria): ?>
            <option value="<?= $categoria ?>" <?= $filtroCategoria===$categoria?"selected":"" ?>>
                <?= $categoria ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filtrar</button>

</form>

<?php foreach($partidos as $partido): ?>

<?php
$equipoA = obtenerJugadores($pdo, $partido["id"], "A");
$equipoB = obtenerJugadores($pdo, $partido["id"], "B");
$sets = json_decode($partido["sets_json"], true);

$statusClass = match($partido["estado"]) {
    "Programado" => "status-programado",
    "En juego" => "status-jugando",
    "Finalizado" => "status-finalizado",
    default => "status-borrador"
};
?>

<div class="match-card">

    <div class="match-top">
        <div><?= $partido["fecha"] ?> · <?= substr($partido["hora"],0,5) ?></div>
        <div><?= $partido["sede"] ?></div>
    </div>

    <div class="match-players">

        <div class="player-row">
            <div class="player-name"><?= implode(" / ", $equipoA) ?></div>
            <div class="sets">
                <?php foreach($sets as $set): ?>
                    <div class="set"><?= $set[0] ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="player-row">
            <div class="player-name"><?= implode(" / ", $equipoB) ?></div>
            <div class="sets">
                <?php foreach($sets as $set): ?>
                    <div class="set"><?= $set[1] ?></div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div class="match-footer">

        <div>
            <?= $partido["categoria"] ?> · <?= $partido["modalidad"] ?>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">

            <a href="editar_partido.php?id=<?= $partido["id"] ?>"
               style="background:#067ec9;color:white;text-decoration:none;padding:8px 14px;border-radius:10px;font-size:14px;font-weight:bold;">
                Editar
            </a>

            <!-- 🔴 BOTÓN ELIMINAR (NUEVO) -->
            <form method="POST"
                  onsubmit="return confirm('¿Eliminar este partido?');"
                  style="margin:0;">
                <input type="hidden" name="eliminar_partido" value="<?= $partido["id"] ?>">
                <button type="submit"
                        style="background:#e74c3c;color:white;border:none;padding:8px 14px;border-radius:10px;font-weight:bold;cursor:pointer;">
                    Eliminar
                </button>
            </form>

            <div class="status <?= $statusClass ?>">
                <?= $partido["estado"] ?>
            </div>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

</body>
</html>
