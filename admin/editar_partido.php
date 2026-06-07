<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

if (!isset($_GET["id"])) die("Partido no encontrado");

$id = (int)$_GET["id"];

$stmt = $pdo->prepare("SELECT * FROM partidos WHERE id=:id");
$stmt->execute([":id"=>$id]);
$partido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partido) die("Partido inexistente");

$stmt = $pdo->prepare("
SELECT pj.equipo, j.id, j.nombre
FROM partido_jugadores pj
INNER JOIN jugadores j ON j.id = pj.jugador_id
WHERE pj.partido_id = :id
ORDER BY pj.equipo
");

$stmt->execute([":id"=>$id]);
$jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$A = array_values(array_filter($jugadores, fn($j)=>$j["equipo"]==="A"));
$B = array_values(array_filter($jugadores, fn($j)=>$j["equipo"]==="B"));

$sets = json_decode($partido["sets_json"], true) ?: [];

function s($sets,$i,$j){ return $sets[$i][$j] ?? ""; }

if ($_SERVER["REQUEST_METHOD"]==="POST") {

    if (
    $_POST["estado"] === "Finalizado"
    && empty(array_filter($_POST["puntos"] ?? []))
) {

    echo "
    <script>
        alert('Debés asignar puntos antes de finalizar el partido.');
        window.history.back();
    </script>
    ";

    exit;
}

    $sets_json = json_encode([
        [$_POST["set1_a"] ?? 0, $_POST["set1_b"] ?? 0],
        [$_POST["set2_a"] ?? 0, $_POST["set2_b"] ?? 0],
        [$_POST["set3_a"] ?? 0, $_POST["set3_b"] ?? 0],
    ]);

    $stmt = $pdo->prepare("
        UPDATE partidos SET
        fecha=:fecha,
        hora=:hora,
        sede=:sede,
        estado=:estado,
        sets_json=:sets
        WHERE id=:id
    ");

    $stmt->execute([
        ":fecha"=>$_POST["fecha"],
        ":hora"=>$_POST["hora"],
        ":sede"=>$_POST["sede"],
        ":estado"=>$_POST["estado"],
        ":sets"=>$sets_json,
        ":id"=>$id
    ]);

    // evitar duplicados ranking
    $stmt = $pdo->prepare("
        DELETE FROM ranking_movimientos
        WHERE partido_id = :id
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    // insertar nuevos puntos
    if (!empty($_POST["puntos"])) {

        foreach($_POST["puntos"] as $jid=>$pts){

            if ($pts==="") continue;

            $stmt = $pdo->prepare("
                INSERT INTO ranking_movimientos
                (jugador_id,torneo_id,categoria_id,partido_id,puntos,detalle)
                VALUES
                (:j,:t,:c,:p,:pt,'Carga manual partido')
            ");

            $stmt->execute([
                ":j"=>$jid,
                ":t"=>$partido["torneo_id"],
                ":c"=>$partido["categoria_id"],
                ":p"=>$id,
                ":pt"=>(int)$pts
            ]);
        }
    }

    header("Location: listar_partidos.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Partido</title>

<style>

/* BASE */
body{
    margin:0;
    background:#0b1020;
    color:#fff;
    font-family:Arial;
}

/* HEADER */
.header{
    position:sticky;
    top:0;
    background:#0b1020ee;
    padding:14px;
    border-bottom:1px solid rgba(255,255,255,.06);
}

.back{
    background:#1e2242;
    padding:10px 14px;
    border-radius:999px;
    text-decoration:none;
    color:white;
    font-weight:bold;
}

/* WRAP */
.container{
    max-width:900px;
    margin:auto;
    padding:16px;
}

/* CARD */
.card{
    background:#151935;
    padding:18px;
    border-radius:16px;
    margin-bottom:14px;
    border:1px solid rgba(255,255,255,.06);
}

/* TITLES */
h3{
    margin:0 0 14px 0;
}

/* INPUTS */
input,select,button{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:none;
    margin-bottom:10px;
    box-sizing:border-box;
}

input,select{
    background:#0b1020;
    color:#fff;
}

/* SCOREBOARD */
.scoreboard{
    width:100%;
}

/* HEADER ROW */
.score-header{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    margin-bottom:10px;
    text-align:center;
    font-weight:bold;
    opacity:.8;
}

.score-header div:first-child{
    text-align:left;
}

/* ROW */
.score-row{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:10px;
    align-items:center;
    margin-bottom:10px;
}

.score-row .label{
    font-weight:bold;
    opacity:.8;
}

.score-row input{
    text-align:center;
    font-size:22px;
    font-weight:bold;
    height:58px;
}

/* PLAYERS (NO DUPLICADOS) */
.teams{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.team{
    background:#0b1020;
    padding:10px;
    border-radius:12px;
}

.player{
    padding:6px 0;
    border-bottom:1px solid rgba(255,255,255,.05);
}

/* POINTS */
.point{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px;
    background:#0b1020;
    border-radius:12px;
    margin-bottom:8px;
}

.point input{
    width:110px;
    height:52px;
    font-size:20px;
    font-weight:bold;

/* MOBILE FIX REAL */
@media(max-width:768px){

    .teams{
        grid-template-columns:1fr;
    }

    .score-header,
    .score-row{
        grid-template-columns:1fr 1fr 1fr;
    }
}

</style>

</head>

<body>

<div class="header">
    <a class="back" href="listar_partidos.php">← Partidos</a>
</div>

<div class="container">

<form method="POST">

<!-- INFO -->
<div class="card">
    <h3>Información del partido</h3>

    <input type="date" name="fecha" value="<?= $partido["fecha"] ?>">
    <input type="time" name="hora" value="<?= substr($partido["hora"],0,5) ?>">

    <select name="sede">
        <?php foreach(["CTC","Olimpia","Independiente"] as $s): ?>
            <option <?= $partido["sede"]==$s?"selected":"" ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>

    <select name="estado">
        <?php foreach(["Borrador","Programado","En juego","Finalizado"] as $e): ?>
            <option <?= $partido["estado"]==$e?"selected":"" ?>><?= $e ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- SCOREBOARD REAL -->
<div class="card">
    <h3>Marcador</h3>

    <div class="score-header">
        <div></div>
        <div>Equipo A</div>
        <div>Equipo B</div>
    </div>

    <div class="score-row">
        <div class="label">Set 1</div>
        <input name="set1_a" value="<?= s($sets,0,0) ?>">
        <input name="set1_b" value="<?= s($sets,0,1) ?>">
    </div>

    <div class="score-row">
        <div class="label">Set 2</div>
        <input name="set2_a" value="<?= s($sets,1,0) ?>">
        <input name="set2_b" value="<?= s($sets,1,1) ?>">
    </div>

    <div class="score-row">
        <div class="label">Set 3</div>
        <input name="set3_a" value="<?= s($sets,2,0) ?>">
        <input name="set3_b" value="<?= s($sets,2,1) ?>">
    </div>

</div>

<!-- EQUIPOS (SIN DUPLICAR MÁS ABAJO) -->
<div class="card">
    <h3>Jugadores</h3>

    <div class="teams">

        <div class="team">
            <strong>Equipo A</strong>
            <?php foreach($A as $p): ?>
                <div class="player"><?= $p["nombre"] ?></div>
            <?php endforeach; ?>
        </div>

        <div class="team">
            <strong>Equipo B</strong>
            <?php foreach($B as $p): ?>
                <div class="player"><?= $p["nombre"] ?></div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- PUNTOS -->
<div class="card">
    <h3>Puntos</h3>
        <p>Al momento de dar por finalizdo el partido se asignan los puntos obtenidos</p>
    <?php foreach($jugadores as $j): ?>
        <div class="point">
            <span><?= $j["nombre"] ?></span>
            <input type="number" name="puntos[<?= $j["id"] ?>]">
        </div>
    <?php endforeach; ?>

</div>

<button
    type="submit"
    onclick="return confirm('¿Guardar cambios del partido?');"
    style="
        background:#067ec9;
        color:white;
        font-size:18px;
        font-weight:bold;
        padding:16px;
        border:none;
        border-radius:14px;
        cursor:pointer;
    "
>
    Guardar cambios
</button>

</form>

</div>

</body>
</html>
