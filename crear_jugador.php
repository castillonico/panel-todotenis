<?php

require_once "auth.php";

require_once "db.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"]);
    $club = $_POST["club"];

    if ($nombre !== "") {

        $sql = "INSERT INTO jugadores (nombre, club)
                VALUES (:nombre, :club)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":nombre" => $nombre,
            ":club" => $club
        ]);

        header("Location: index.php");
exit;

    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Crear Jugador</title>

<style>

body{

    background:#0f1223;
    color:#fefefe;
    font-family:Ottawa,sans-serif;
    padding:40px;

}

.form-container{

    max-width:500px;
    margin:auto;
    background:#151935;
    padding:30px;
    border-radius:16px;
    box-shadow:0 0 12px #067ec9;

}

h1{

    text-align:center;
    margin-bottom:30px;

}

input,
select,
button{

    width:100%;
    padding:12px;
    margin-bottom:16px;
    border:none;
    border-radius:10px;
    font-size:16px;

}

input,
select{

    background:#0f1223;
    color:#fefefe;

}

button{

    background:#067ec9;
    color:#fff;
    cursor:pointer;
    font-weight:bold;

}

.mensaje{

    text-align:center;
    margin-bottom:20px;
    color:#67ff9b;

}

</style>

</head>

<body>

<div class="form-container">

    <h1>Crear Jugador</h1>

    <?php if($mensaje): ?>

        <div class="mensaje">
            <?php echo $mensaje; ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="nombre"
            placeholder="Nombre del jugador"
            required
        >

        <select name="club">

            <option value="CTC">CTC</option>
            <option value="Olimpia">Olimpia</option>
            <option value="Independiente">Independiente</option>

        </select>

        <button type="submit">
            Guardar jugador
        </button>

    </form>

</div>

</body>
</html>
