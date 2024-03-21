<?php
require_once 'vendor/autoload.php';
require_once 'database.php';
require_once 'session.php';
require_once 'utils/response.php';

class API
{
	private static $instance = null;
	private $db;

	function __construct(DB $db)
	{
		$this->db = $db;
	}

	public static function getInstance(DB $db)
	{
		if (self::$instance == null) {
			self::$instance = new API($db);
		}

		return self::$instance;
	}

	function checkSid()
	{
		$sid = session_id();
		$stmt = $this->db->prepare("SELECT * FROM active_sessions WHERE session_id = ?");
		$stmt->bind_param("s", $sid);
		$stmt->execute();
		$result = $stmt->get_result();
		$data = $result->fetch_all(MYSQLI_ASSOC);
		return count($data) > 0 ? true : false;
	}

	function handleRequest($property)
	{
		if (!$this->checkSid()) {
			http_response_code(403);
			echo json_encode(array(
				'status' => false,
				'message' => 'Not permitted'
			));
			return;
		}

		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				$this->handleGet($property);
				break;
			case 'POST':
				$this->handlePost($property);
				break;
			case 'DELETE':
				$this->handleDelete($property);
				break;
			default:
				http_response_code(405);
				respondWithJson(false, 'Method not allowed');
				break;
		}
	}

	private function handleGet($property)
	{
		$property_name = $property[0];
		$id = isset($property[1]) ? $property[1] : null;

		$sql = "SELECT * FROM $property_name";
		if ($id) {
			$sql .= " WHERE id = $id";
		}

		$result = $this->db->execute_query($sql);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		return $data;
	}

	private function handlePost($property)
	{
		$property_name = $property[0];
		$id = isset($property[1]) ? $property[1] : null;
		$query_type = ($id == null) ? 'INSERT INTO %s (%s) VALUES (%s)' : 'UPDATE %s SET %s WHERE id = %s';
		$data = json_decode(file_get_contents('php://input'), true);

		$update_pairs = [];
		foreach ($data as $key => $value) {
			$update_pairs[] = "$key = '$value'";
		}

		$query = sprintf(
			$query_type,
			$property_name,
			implode(', ', array_keys($data)),
			implode(', ', array_map(function ($value) {
				return "'$value'";
			}, array_values($data))),
			$id
		);

		if ($id != null) {
			$query = sprintf($query_type, $property_name, implode(', ', $update_pairs), $id);
		}

		$result = $this->db->execute_query($query);
		if ($result) {
			return respondWithJson(true, 'Data updated successfully');
		} else {
			return respondWithJson(false, 'Error updating data');
		}
	}

	private function handleDelete($property)
	{
		$property_name = $property[0];
		$id = isset($property[1]) ? $property[1] : null;
		$query = "DELETE FROM $property_name WHERE id = $id";
		$result = $this->db->execute_query($query);

		if ($result) {
			return respondWithJson(true, 'Data deleted successfully');
		} else {
			return respondWithJson(false, 'Error deleting data');
		}
	}
}
