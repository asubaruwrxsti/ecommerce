<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	throw new Exception("403 - Access Forbidden");
}

require_once 'vendor/autoload.php';
require_once 'database.php';
require_once './utils/growth.php';

/**
 * View class
 */
class View
{
	public static $instance = null;
	private $viewsDir;
	private $loader;
	private $twig;
	private $header;
	private $base;
	private $footer;
	private $db;

	public function __construct($viewsDir)
	{
		$this->viewsDir = $viewsDir;
		$this->loader = new \Twig\Loader\FilesystemLoader($this->viewsDir);
		$this->twig = new \Twig\Environment($this->loader);

		$this->header = $this->twig->load('header.twig');
		$this->base = $this->twig->load('base.twig');
		$this->footer = $this->twig->load('footer.twig');

		$this->db = DB::getInstance("database.sqlite3");
	}

	public static function getInstance($viewsDir)
	{
		if (self::$instance == null) {
			self::$instance = new View($viewsDir);
		}

		return self::$instance;
	}

	public function render($viewName, $data = [])
	{
		$viewFile = $this->viewsDir . '/' . $viewName . '/' . $viewName . '.twig';
		if (!file_exists($viewFile)) {
			throw new Exception("View file not found: $viewFile");
		}

		echo $this->header->render(array(
			'window_title' => strtoupper($viewName),
			'user_logged_in' => $_SESSION['is_loggedin'],
			'user_role' => $_SESSION['user_role'],
			'user_name' => strtoupper($_SESSION['username']),
			'nav_items' => $this->db->execute_query("SELECT * FROM navbar_items"),
			// Add more data as needed
		));

		echo $this->base->render([
			'window_title' => strtoupper($viewName),
			'content' => sprintf('%s/%s.twig', strtolower($viewName), strtolower($viewName)),
			'vars' => $data,
			// Add more data as needed
		]);

		echo $this->footer->render();
	}

	public function render_dashboard($handler, $params)
	{	
		// Fetch data for the dashboard
		$products = $params["db"]->execute_query("SELECT * FROM products");
		$data = [
			'user' => $params["session"]->get('user'),
			'products' => $products,
			'currency' => $_SESSION['currency'],
			'sales' => $params["db"]->execute_query("SELECT * FROM sales LIMIT 5"),
			// 'growth' => calculateGrowth($params["db"], 'sales'),
			'growth' => '10',
			// Add more data as needed
		];

		$this->render($handler, $data);
	}

	public function render_products($handler, $params)
	{
		$products = $params["db"]->execute_query("SELECT * FROM products");
		if (!empty($products)) {
			$products_header = array_keys($products[0]);
			foreach ($products as &$product) {
				$product['comments'] = json_decode($product['comments'], true);
			}
		} else {
			$products_header = [];
		}
	
		$data = [
			'user' => $params["session"]->get('user'),
			'products_header' => $products_header,
			'products_data' => $products,
			'currency' => $_SESSION['currency'],
		];
		
		$this->render($handler, $data);
	}

	public function render_sales($handler, $params)
	{
		$this->render($handler);
	}

	public function render_clients($handler, $params)
	{
		$this->render($handler);
	}

	public function render_messages($handler, $params)
	{
		$this->render($handler);
	}
}
