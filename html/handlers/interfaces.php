<?php
require_once 'session.php';
require_once './database.php';

interface Handler
{
	public function handle($vars);
}

class LogoutHandler implements Handler
{
    private $session;
    private $db;

    public function __construct(Session $session, DB $db) {
        $this->session = $session;
        $this->db = $db;
    }

    public function handle($vars)
    {	
        $this->session->destroy();
        $this->db->destroy_session_id($_SESSION['user_id']);
        $this->db->close_connection();
        header("Location: /login.php");
    }
}

class ApiHandler implements Handler
{
	private $purifier;
	private $api;

	public function __construct($purifier, $api) {
		$this->purifier = $purifier;
		$this->api = $api;
	}

	public function handle($vars)
	{
		$property = $this->purifier->purify($vars['property']);
		$id = isset($vars['id']) ? $this->purifier->purify($vars['id']) : null;

		$res = $this->api->fetch_data([$property, $id]);
		echo json_encode($res);
	}
}

class DashboardHandler implements Handler
{

	private $header;
	private $base;
	private $handler;

	public function __construct($header, $base, $handler)
	{
		$this->header = $header;
		$this->base = $base;
		$this->handler = $handler;
	}

	public function handle($vars)
	{
		echo $this->header->render(array(
			'window_title' => $this->handler,
			'user_name' => strtoupper($_SESSION['username']),
		));
		echo $this->base->render(array(
				'window_title' => $this->handler,
				'content' => sprintf('/%s/%s.twig', strtolower($this->handler), strtolower($this->handler)),
				'vars' => []
			)
		);
	}
}
