<?php
    /**
     * Main Routing file
     * @package admin_dashboard
     */

    session_start();
    if (!isset($_SESSION['is_loggedin'])) {
        header("Location: /login.php");
    }

    require_once 'vendor/autoload.php';
    require_once 'database.php';
    require_once 'api.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    $db = new DB($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $api = new API($db);
    $loader = new \Twig\Loader\FilesystemLoader('views');
    $twig = new \Twig\Environment($loader);

    $purifier_config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($purifier_config);

    $dompdf = new Dompdf\Dompdf();

    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
        $r->addGroup('/index.php', function ($r) {

            // Pages
            $r->addRoute('GET', '/', 'Dashboard');
        });
    });

    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }

    $uri = rawurldecode($uri);
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    
    $header = $twig->load('/assets/header.twig');
    $base = $twig->load('base.twig');

	$messages = "SELECT * FROM messages WHERE archieved != 1;";
	$messages = $db->execute_query($messages);
	$messages = mysqli_num_rows($messages);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            echo $twig->render('/assets/404.twig');
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            echo $twig->render('/assets/404.twig');
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];

            if ($handler == 'handler') {
                $handler = $vars['REQUEST_URI'];
            }

            switch ($handler) {
                case 'logout':
                    session_destroy();
                    $db->destroy_session_id($_SESSION['user_id']);
                    $db->close_connection();
                    header("Location: /login.php");
                    break;
                
                case 'api':
                    $property = $purifier->purify($vars['property']);
                    $id = isset($vars['id']) ? $purifier->purify($vars['id']) : null;

                    $res = $api->fetch_data([$property, $id]);
                    echo json_encode($res);
                    break;

                case 'Dashboard':
                    $recentOrders = "SELECT customers.firstname, customers.lastname, customers.phone_number, cars.name, cars.id, revenue.rental_date, revenue.rental_duration
                        FROM revenue 
                        JOIN customers 
                        ON customers.id = revenue.customer_id 
                        JOIN cars ON cars.id = revenue.car_id
                        ORDER BY revenue.rental_date DESC LIMIT 5;";
                    $recentOrders = $db->execute_query($recentOrders);
                    $recentOrders = $recentOrders->fetch_all(MYSQLI_ASSOC);

                    echo $header->render(array(
                        'window_title' => $handler,
                        'user_logged_in' => $_SESSION['is_loggedin'],
                        'user_role' => $_SESSION['user_role'],
                        'user_name' => strtoupper($_SESSION['username']),
						'message_count' => $messages
                    ));
                    echo $base->render(array(
                            'window_title' => $handler,
                            'content' => sprintf('/%s/%s.twig', strtolower($handler), strtolower($handler)),
                            'vars' => [
                                'currency' => $_SESSION['currency'],
                                'recent_orders' => $recentOrders
                            ]
                        )
                    );
                break;
            }
            break;
    }
?>