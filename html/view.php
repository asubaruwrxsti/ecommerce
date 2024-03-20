<?php

require_once 'vendor/autoload.php';

/**
 * View class
 */
class View {
    private $viewsDir;
	private $loader;
	private $twig;
	private $header;
	private $base;
	private $footer;

    public function __construct($viewsDir) {
        $this->viewsDir = $viewsDir;
		$this->loader = new \Twig\Loader\FilesystemLoader($this->viewsDir);
		$this->twig = new \Twig\Environment($this->loader);

		$this->header = $this->twig->load('header.twig');
		$this->base = $this->twig->load('base.twig');
		$this->footer = $this->twig->load('footer.twig');
    }

    public function render($viewName, $data = []) {
        $viewFile = $this->viewsDir . '/' . $viewName . '/' . $viewName . '.twig';
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: $viewFile");
        }

        extract($data);
        include $viewFile;
    }

    public function renderDashboard($handler, $session, $db) {
        // Fetch data for the dashboard
        $data = [
            'user' => $session->get('user'),
            // Add more data as needed
        ];

        $this->render($handler, $data);
    }
}
?>