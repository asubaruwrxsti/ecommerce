<?php
    require_once 'vendor/autoload.php';
    require_once 'database.php';

    /**
     * API class
     */
    class API {
        private $db;

        function __construct (DB $db) {
            $this->db = $db;
        }

        /**
         * Check if the user has a valid session ID
         * @return bool
         */
        function checkSid() {
            $sid = session_id();
            $sql = "SELECT * FROM active_sessions WHERE session_id = '$sid'";
            $result = $this->db->execute_query($sql);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            return count($data) > 0 ? true : false;
        }

        /**
         * Fetch data from the database
         * @param array $property
         * @return array
         */
        function fetch_data($property) {

            if (!$this->checkSid()) {
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Not permitted'
                ));
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $property_name = $property[0];
                $id = isset($property[1]) ? $property[1] : null;
    
                $sql = "SELECT * FROM $property_name";
                if ($id) {
                    $sql .= " WHERE id = $id";
                }
    
                $result = $this->db->execute_query($sql);
                $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
                return $data;
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
                    return json_encode(array(
                        'status' => true,
                        'message' => 'Data updated successfully'
                    ));
                } else {
                    return json_encode(array(
                        'status' => false,
                        'message' => 'Error updating data'
                    ));
                }
            }

            if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $property_name = $property[0];
                $id = isset($property[1]) ? $property[1] : null;
                $query = "DELETE FROM $property_name WHERE id = $id";
                $result = $this->db->execute_query($query);

                if ($result) {
                    return json_encode(array(
                        'status' => true,
                        'message' => 'Data deleted successfully'
                    ));
                } else {
                    return json_encode(array(
                        'status' => false,
                        'message' => 'Error deleting data'
                    ));
                }
            }
        }
    }