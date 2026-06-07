<?php

require_once __DIR__ . "/../core/db.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "

SELECT
    j.id,
    j.nombre,
    j.club,
    c.id as categoria_id,
    c.nombre as categoria,
    COALESCE(SUM(rm.puntos),0) as puntos

FROM jugadores j

INNER JOIN ranking_movimientos rm
ON rm.jugador_id = j.id

INNER JOIN categorias c
ON c.id = rm.categoria_id

GROUP BY
    j.id,
    c.id

";

$stmt = $pdo->query($sql);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = [];

function slugCategoria($nombre){

    $slug = mb_strtolower($nombre);

    $slug = str_replace(
        [" ", "ª", "°"],
        ["_", "", ""],
        $slug
    );

    return $slug;
}

foreach($rows as $r){

    $jugadorId = (int)$r["id"];
    $categoriaId = (int)$r["categoria_id"];

    $sqlPartidos = "

    SELECT
        p.sets_json,
        pj.equipo

    FROM partidos p

    INNER JOIN partido_jugadores pj
    ON pj.partido_id = p.id

    WHERE
        pj.jugador_id = :jugador_id
        AND p.categoria_id = :categoria_id
        AND p.estado = 'Finalizado'

    ";

    $stmtPartidos = $pdo->prepare($sqlPartidos);

    $stmtPartidos->execute([
        ":jugador_id" => $jugadorId,
        ":categoria_id" => $categoriaId
    ]);

    $partidos = $stmtPartidos->fetchAll(PDO::FETCH_ASSOC);

    $setsGanados = 0;
    $setsPerdidos = 0;

    $gamesFavor = 0;
    $gamesContra = 0;

    foreach($partidos as $partido){

        $equipo = $partido["equipo"];

        $sets = json_decode(
            $partido["sets_json"],
            true
        ) ?: [];

        foreach($sets as $set){

            $a = (int)($set[0] ?? 0);
            $b = (int)($set[1] ?? 0);

            if(
                $a === 0 &&
                $b === 0
            ){
                continue;
            }

            if($equipo === "A"){

                $gamesFavor += $a;
                $gamesContra += $b;

                if($a > $b){
                    $setsGanados++;
                }

                if($b > $a){
                    $setsPerdidos++;
                }

            }else{

                $gamesFavor += $b;
                $gamesContra += $a;

                if($b > $a){
                    $setsGanados++;
                }

                if($a > $b){
                    $setsPerdidos++;
                }

            }

        }

    }

    $slug = slugCategoria(
        $r["categoria"]
    );

    if(!isset($resultado[$slug])){
        $resultado[$slug] = [];
    }

    $resultado[$slug][] = [

        "id" => (int)$r["id"],

"categoria_id" => (int)$r["categoria_id"],

"nombre" => $r["nombre"],

"club" => $r["club"],

"puntos" => (int)$r["puntos"],

        "diferencia_sets" => (
            $setsGanados - $setsPerdidos
        ),

        "diferencia_games" => (
            $gamesFavor - $gamesContra
        )

    ];

}

foreach($resultado as $slug => $jugadores){

    usort(
        $jugadores,
        function($a,$b){

            if(
                $a["puntos"] !==
                $b["puntos"]
            ){
                return (
                    $b["puntos"] <=>
                    $a["puntos"]
                );
            }

            if(
                $a["diferencia_sets"] !==
                $b["diferencia_sets"]
            ){
                return (
                    $b["diferencia_sets"] <=>
                    $a["diferencia_sets"]
                );
            }

            return (
                $b["diferencia_games"] <=>
                $a["diferencia_games"]
            );

        }
    );

    $resultado[$slug] = $jugadores;

}

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE |
    JSON_PRETTY_PRINT
);
