<?php 

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

$busqueda = trim($_GET["q"] ?? "");
$selectedId = isset($_GET["id"]) ? (int)$_GET["id"] : null;

/* =========================
   POST
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    /* =========================
       CREAR
    ========================= */

    if ($accion === "crear") {

        $nombre = trim($_POST["nombre"] ?? "");
        $club = trim($_POST["club"] ?? "");

        if ($nombre && $club) {

            $sql = "
                SELECT id
                FROM jugadores
                WHERE nombre = :nombre
                AND club = :club
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":nombre" => $nombre,
                ":club" => $club
            ]);

            if (!$stmt->fetch()) {

                $sql = "
                    INSERT INTO jugadores
                    (nombre, club)
                    VALUES
                    (:nombre, :club)
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":nombre" => $nombre,
                    ":club" => $club
                ]);
            }
        }

        header("Location: jugadores.php");
        exit;
    }

    /* =========================
       EDITAR
    ========================= */

    if ($accion === "editar") {

        $sql = "
            UPDATE jugadores
            SET
                nombre = :nombre,
                club = :club,
                activo = :activo
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":nombre" => trim($_POST["nombre"]),
            ":club" => $_POST["club"],
            ":activo" => isset($_POST["activo"]) ? 1 : 0,
            ":id" => (int)$_POST["id"]
        ]);

        header("Location: jugadores.php?id=" . (int)$_POST["id"]);
        exit;
    }

    /* =========================
       MOVIMIENTO MANUAL
    ========================= */

    if ($accion === "movimiento") {

    $puntos = (int)$_POST["puntos"];
    $detalle = trim($_POST["detalle"]);
    $categoria_id = (int)$_POST["categoria_id"];
    $torneo_id = (int)$_POST["torneo_id"];
    $jugador_id = (int)$_POST["jugador_id"];

    if (
        $puntos != 0
        && $categoria_id > 0
        && $torneo_id > 0
    ) {

        $stmt = $pdo->prepare("
            INSERT INTO ranking_movimientos
            (
                jugador_id,
                categoria_id,
                torneo_id,
                partido_id,
                puntos,
                detalle
            )
            VALUES
            (
                :jugador,
                :categoria,
                :torneo,
                NULL,
                :puntos,
                :detalle
            )
        ");

        $stmt->execute([
            ":jugador" => $jugador_id,
            ":categoria" => $categoria_id,
            ":torneo" => $torneo_id,
            ":puntos" => $puntos,
            ":detalle" => $detalle ?: "Movimiento manual"
        ]);
    }

    header("Location: jugadores.php?id=" . $jugador_id);
    exit;
}
}

/* =========================
   LISTA
========================= */

$sql = "
    SELECT id, nombre, club
    FROM jugadores
";

if ($busqueda) {
    $sql .= "
        WHERE nombre LIKE :busqueda
        OR club LIKE :busqueda
    ";
}

$sql .= "
    ORDER BY nombre ASC
    LIMIT 30
";

$stmt = $pdo->prepare($sql);

if ($busqueda) {

    $stmt->execute([
        ":busqueda" => "%$busqueda%"
    ]);

} else {

    $stmt->execute();
}

$jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   JUGADOR
========================= */

$jugador = null;
$puntosCategorias = [];

if ($selectedId) {

    $sql = "
        SELECT *
        FROM jugadores
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":id" => $selectedId
    ]);

    $jugador = $stmt->fetch(PDO::FETCH_ASSOC);

    /* CATEGORÍAS */

    $sql = "
        SELECT DISTINCT c.nombre
        FROM partido_jugadores pj
        INNER JOIN partidos p
            ON p.id = pj.partido_id
        INNER JOIN categorias c
            ON c.id = p.categoria_id
        WHERE pj.jugador_id = :id
        ORDER BY c.nombre
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":id" => $selectedId
    ]);

    $categoriasJugador = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $jugador["categorias"] = implode(", ", $categoriasJugador);

    /* PUNTOS POR CATEGORÍA */

    $sql = "
        SELECT
            c.nombre,
            SUM(rm.puntos) as puntos
        FROM ranking_movimientos rm
        INNER JOIN categorias c
            ON c.id = rm.categoria_id
        WHERE rm.jugador_id = :id
        GROUP BY c.id
        ORDER BY c.nombre
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":id" => $selectedId
    ]);

    $puntosCategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   CATEGORÍAS SELECT
========================= */

$categorias = $pdo->query("
    SELECT id, nombre
    FROM categorias
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Jugadores</title>

<style>

body{
    margin:0;
    background:#0f1223;
    color:#fff;
    font-family:Arial;
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

.create-box{
    background:#151935;
    padding:16px;
    border-radius:16px;
    margin-bottom:16px;
}

.create-box input,
.create-box select{
    width:100%;
    padding:12px;
    margin-bottom:10px;
    border:none;
    border-radius:10px;
    box-sizing:border-box;
}

.chips{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:16px;
}

.chip{
    background:#1e2242;
    padding:8px 12px;
    border-radius:999px;
    font-size:13px;
    text-decoration:none;
    color:white;
    white-space:nowrap;
}

.layout{
    display:flex;
    flex-direction:column;
    gap:16px;
}

.player-card{
    background:#151935;
    padding:16px;
    border-radius:16px;
}

.player-name{
    font-size:22px;
    font-weight:bold;
    margin-bottom:10px;
}

.edit-box input,
.edit-box select{
    width:100%;
    padding:12px;
    margin-bottom:10px;
    border-radius:10px;
    border:none;
    box-sizing:border-box;
}

.badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    margin-bottom:14px;
    font-size:12px;
}

.active{ background:#1f8f4d; }
.inactive{ background:#555; }

.button{
    background:#067ec9;
    color:#fff;
    padding:12px;
    border-radius:12px;
    border:none;
    width:100%;
    font-weight:bold;
    cursor:pointer;
    box-sizing:border-box;
}

.main{
    margin-top:16px;
}

.rank-line{
    padding:10px;
    background:#0f1223;
    border-radius:10px;
    margin-top:8px;
    font-size:14px;
}

@media(min-width:768px){

    .layout{
        flex-direction:row;
        align-items:flex-start;
    }

    .side{
        flex:1;
        max-height:70vh;
        overflow:auto;
        background:#11142a;
        padding:12px;
        border-radius:14px;
    }

    .main{
        flex:2;
        margin-top:0;
    }
}

</style>

</head>

<body>

<div class="container">

    <div class="topbar">
    <div class="title"> Jugadores </div>
    <a href="index.php" class="button"> ← Panel </a>
</div>

    <!-- CREAR -->

    <div class="create-box">

        <form method="POST">

            <input type="hidden" name="accion" value="crear">

            <input
                type="text"
                name="nombre"
                placeholder="Nombre"
                required
            >

            <select name="club" required>
                <option value="">Club</option>
                <option>Independiente</option>
                <option>Olimpia</option>
                <option>CTC</option>
            </select>

            <button class="button">
                Crear jugador
            </button>

        </form>

    </div>

    <!-- BUSCADOR -->

    <div class="player-card" style="margin-bottom:16px;">

        <h3 style="margin-top:0;">
            Buscar jugadores
        </h3>

        <form method="GET">

            <input
                type="text"
                name="q"
                placeholder="Buscar jugador o club..."
                value="<?= htmlspecialchars($busqueda) ?>"
                style="
                    width:100%;
                    padding:14px;
                    border:none;
                    border-radius:12px;
                    box-sizing:border-box;
                "
            >

        </form>

    </div>

    <div class="layout">

        <!-- LISTA -->

        <div class="side">

            <h3 style="margin-top:0;margin-bottom:14px;">
                Jugadores
            </h3>

            <div class="chips">

                <?php foreach ($jugadores as $j): ?>

                    <a
                        class="chip"
                        href="jugadores.php?id=<?= $j["id"] ?>"
                    >
                        <?= htmlspecialchars($j["nombre"]) ?>
                    </a>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- DETALLE -->

        <div class="main">

            <?php if ($jugador): ?>

                <div class="player-card">

                    <div class="player-name">
                        <?= htmlspecialchars($jugador["nombre"]) ?>
                    </div>

                    <div class="badge <?= $jugador["activo"] ? "active" : "inactive" ?>">
                        <?= $jugador["activo"] ? "Activo" : "Inactivo" ?>
                    </div>

                    <p>
                        Club:
                        <?= htmlspecialchars($jugador["club"]) ?>
                    </p>

                    <p>
                        Categorías:
                        <?= $jugador["categorias"] ?: "Sin actividad" ?>
                    </p>

                    <h3>
                        Ranking
                    </h3>

                    <?php if ($puntosCategorias): ?>

                        <?php foreach($puntosCategorias as $r): ?>

                            <div class="rank-line">

                                <strong>
                                    <?= htmlspecialchars($r["nombre"]) ?>
                                </strong>

                                ·

                                <?= (int)$r["puntos"] ?> pts

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="rank-line">
                            Sin puntos registrados
                        </div>

                    <?php endif; ?>

                    <form method="POST" class="edit-box" style="margin-top:18px;">

                        <input type="hidden" name="accion" value="editar">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $jugador["id"] ?>"
                        >

                        <input
                            type="text"
                            name="nombre"
                            value="<?= htmlspecialchars($jugador["nombre"]) ?>"
                        >

                        <select name="club">

                            <option <?= $jugador["club"]=="Independiente"?"selected":"" ?>>
                                Independiente
                            </option>

                            <option <?= $jugador["club"]=="Olimpia"?"selected":"" ?>>
                                Olimpia
                            </option>

                            <option <?= $jugador["club"]=="CTC"?"selected":"" ?>>
                                CTC
                            </option>

                        </select>

                        <label>

                            <input
                                type="checkbox"
                                name="activo"
                                <?= $jugador["activo"] ? "checked" : "" ?>
                            >

                            Activo

                        </label>

                        <button class="button">
                            Guardar cambios
                        </button>

                    </form>

                </div>

                <!-- MOVIMIENTO -->

                <div class="player-card" style="margin-top:16px;">

                    <h3 style="margin-top:0;">
                        Movimiento manual
                    </h3>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="accion"
                            value="movimiento"
                        >

                        <input
                            type="hidden"
                            name="jugador_id"
                            value="<?= $jugador["id"] ?>"
                        >
<select name="torneo_id" required>

    <option value="">
        Ranking / Torneo
    </option>

    <?php

    $torneos = $pdo->query("
        SELECT id, nombre
        FROM torneos
        ORDER BY nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach($torneos as $torneo):

    ?>

        <option value="<?= $torneo["id"] ?>">
            <?= htmlspecialchars($torneo["nombre"]) ?>
        </option>

    <?php endforeach; ?>

</select>
                        <select name="categoria_id" required>

                            <option value="">
                                Categoría
                            </option>

                            <?php foreach($categorias as $cat): ?>

                                <option value="<?= $cat["id"] ?>">

                                    <?= htmlspecialchars($cat["nombre"]) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <input
                            type="number"
                            name="puntos"
                            placeholder="+100 o -50"
                            required
                        >

                        <input
                            type="text"
                            name="detalle"
                            placeholder="Detalle del movimiento"
                        >

                        <button class="button">
                            Guardar movimiento
                        </button>

                    </form>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>
