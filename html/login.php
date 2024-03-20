<?php
    /**
     * Login page
     * @package admin_dashboard
     */

    session_start();
    require_once 'vendor/autoload.php';
    include_once 'database.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    $purifier_config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($purifier_config);

    // $db = new DB($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $loader = new \Twig\Loader\FilesystemLoader('views');
        $twig = new \Twig\Environment($loader);

        if (isset($_SESSION['is_loggedin'])) {
            header("Location: /index.php/");
            die();
        }

        $header = $twig->load('/assets/header.twig');
        $template = $twig->load('/login/login.twig');

        echo $header->render(array(
            'window_title' => 'Login',
            'user_logged_in' => false
        ));
        echo $template->render(array(
            'title' => 'Login',
            'content' => 'Login'
        ));
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $username = $purifier->purify($_POST['username']);
        $password = $purifier->purify($_POST['password']);
        $password = md5($password);

        $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $result = $db->execute_query($sql);
        if (mysqli_num_rows($result) > 0) {
            try {
                $row = mysqli_fetch_assoc($result);

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_role'] = $row['is_admin'] == 1 ? 'admin' : 'user';
                $_SESSION['username'] = $username;
                $_SESSION['is_loggedin'] = true;
                $_SESSION['currency'] = $row['currency'];
                $db->create_session_id($row['id']);
                
                setcookie('username', $username, time() + 3600, '/');
                echo json_encode(array(
                    'status' => 'success',
                    'message' => 'Login successful'
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Something went wrong'
                ));
            }
        } else {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Wrong Credentials'
            ));
        }
    }

?>