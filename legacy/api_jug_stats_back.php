<?php

require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$jugador_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if(!$jugador_id){

    echo json_encode([
        "error" => "Falta id"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS DEL JUGADOR
|--------------------------------------------------------------------------
*/

$sql = "

SELECT
    id,
    nombre,
    club

FROM jugadores

WHERE id = :id

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $jugador_id
]);

$jugador = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$jugador){

    echo json_encode([
        "error" => "Jugador inexistente"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| PARTIDOS FINALIZADOS
|--------------------------------------------------------------------------
|
| Los ordenamos por ID DESC para obtener primero los más recientes
|
*/

$sql = "

SELECT
    p.id,
    p.sets_json,
    pj.equipo

FROM partidos p

INNER JOIN partido_jugadores pj
ON pj.partido_id = p.id

WHERE
    pj.jugador_id = :jugador_id
    AND p.estado = 'Finalizado'

ORDER BY p.id DESC

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":jugador_id" => $jugador_id
]);

$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gamesFavor = 0;
$gamesContra = 0;

$ganados = 0;
$perdidos = 0;

$racha = [];

foreach($partidos as $partido){

    $equipo = $partido["equipo"];

    $sets = json_decode(
        $partido["sets_json"],
        true
    ) ?: [];

    $setsFavor = 0;
    $setsContra = 0;

    foreach($sets as $set){

        $a = (int)($set[0] ?? 0);
        $b = (int)($set[1] ?? 0);

        if($equipo === "A"){

            $gamesFavor += $a;
            $gamesContra += $b;

            if($a > $b){
                $setsFavor++;
            }

            if($b > $a){
                $setsContra++;
            }

        }else{

            $gamesFavor += $b;
            $gamesContra += $a;

            if($b > $a){
                $setsFavor++;
            }

            if($a > $b){
                $setsContra++;
            }

        }

    }

    if($setsFavor > $setsContra){

        $ganados++;

        if(count($racha) < 5){
            $racha[] = "W";
        }

    }else{

        $perdidos++;

        if(count($racha) < 5){
            $racha[] = "L";
        }

    }

}

$partidosJugados = count($partidos);

$winrate = 0;

if($partidosJugados > 0){

    $winrate = round(
        ($ganados / $partidosJugados) * 100
    );

}

$resultado = [

    "id" => (int)$jugador["id"],

    "nombre" => $jugador["nombre"],

    "club" => $jugador["club"],

    "partidos_jugados" => $partidosJugados,

    "ganados" => $ganados,

    "perdidos" => $perdidos,

    "winrate" => $winrate,

    "games_favor" => $gamesFavor,

    "games_contra" => $gamesContra,

    "diferencia_games" => (
        $gamesFavor - $gamesContra
    ),

    "racha" => $racha

];

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
