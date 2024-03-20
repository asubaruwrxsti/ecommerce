<?php
class View {
    private $viewsDir;

    public function __construct($viewsDir) {
        $this->viewsDir = $viewsDir;
    }

    public function render($viewName, $data = []) {
        $viewFile = $this->viewsDir . '/' . $viewName . '.php';
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