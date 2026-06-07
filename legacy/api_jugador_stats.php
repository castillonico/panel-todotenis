<?php

require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$jugador_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

$categoria_id = isset($_GET["categoria_id"])
    ? (int)$_GET["categoria_id"]
    : 0;

if(!$jugador_id){

    echo json_encode([
        "error" => "Falta id"
    ]);

    exit;
}

if(!$categoria_id){

    echo json_encode([
        "error" => "Falta categoria_id"
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
    AND p.categoria_id = :categoria_id

ORDER BY p.id DESC

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":jugador_id" => $jugador_id,
    ":categoria_id" => $categoria_id
]);

$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gamesFavor = 0;
$gamesContra = 0;

$setsGanados = 0;
$setsPerdidos = 0;

$ganados = 0;
$perdidos = 0;

$racha = [];

foreach($partidos as $partido){

    $equipo = $partido["equipo"];

    $sets = json_decode(
        $partido["sets_json"],
        true
    ) ?: [];

    $setsFavorPartido = 0;
    $setsContraPartido = 0;

    foreach($sets as $set){

        $a = (int)($set[0] ?? 0);
        $b = (int)($set[1] ?? 0);

        if($a === 0 && $b === 0){
            continue;
        }

        if($equipo === "A"){

            $gamesFavor += $a;
            $gamesContra += $b;

            if($a > $b){

                $setsFavorPartido++;
                $setsGanados++;

            }elseif($b > $a){

                $setsContraPartido++;
                $setsPerdidos++;

            }

        }else{

            $gamesFavor += $b;
            $gamesContra += $a;

            if($b > $a){

                $setsFavorPartido++;
                $setsGanados++;

            }elseif($a > $b){

                $setsContraPartido++;
                $setsPerdidos++;

            }

        }

    }

    if($setsFavorPartido > $setsContraPartido){

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

/*
|--------------------------------------------------------------------------
| PROXIMO PARTIDO
|--------------------------------------------------------------------------
*/

$sql = "

SELECT
    p.id,
    p.fecha,
    p.hora,
    p.sede,
    c.nombre AS categoria,

    GROUP_CONCAT(
        rival.nombre
        ORDER BY rival.nombre
        SEPARATOR ' / '
    ) AS rival

FROM partidos p

INNER JOIN partido_jugadores pj
ON pj.partido_id = p.id

INNER JOIN categorias c
ON c.id = p.categoria_id

LEFT JOIN partido_jugadores pj_rival
ON pj_rival.partido_id = p.id
AND pj_rival.equipo <> pj.equipo

LEFT JOIN jugadores rival
ON rival.id = pj_rival.jugador_id

WHERE
    pj.jugador_id = :jugador_id
    AND p.estado = 'Programado'
    AND p.categoria_id = :categoria_id

GROUP BY p.id

ORDER BY
    p.fecha ASC,
    p.hora ASC

LIMIT 1

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":jugador_id" => $jugador_id,
    ":categoria_id" => $categoria_id
]);

$proximoPartido = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

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

    "sets_ganados" => $setsGanados,

    "sets_perdidos" => $setsPerdidos,

    "diferencia_sets" => (
        $setsGanados - $setsPerdidos
    ),

    "racha" => $racha,

    "proximo_partido" => $proximoPartido

];

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
