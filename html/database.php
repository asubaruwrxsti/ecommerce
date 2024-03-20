<?php
    require_once 'vendor/autoload.php';

    if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
        die("403 - Access Forbidden");
    }

    /**
     * Database class
     * @package admin_dashboard
     */
    class DB {
        private $conn;
        private $purifier_config;
        private $purifier;

        function __construct($hostname, $username, $password, $database) {
            $this->create_db_conn($hostname, $username, $password, $database);
            $this->purifier_config = HTMLPurifier_Config::createDefault();
            $this->purifier = new HTMLPurifier($this->purifier_config);
        }

        /**
         * Connect to the database
         * @return mysqli
         */
        function create_db_conn($hostname, $username, $password, $database) {
            $this->conn = mysqli_connect($hostname, $username, $password, $database);
            if (!$this->conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            return $this->conn;
        }

        /**
         * Execute a query
         * @param string $sql
         * @return mysqli_result
         */
        function execute_query($sql) {
            $sql = $this->purifier->purify($sql);
            $result = mysqli_query($this->conn, $sql);
            return $result;
        }

        /**
         * Close the database connection
         * @return void
         */
        function close_connection() {
            mysqli_close($this->conn);
        }

        /**
         * Create a session ID for the user
         * @return void
         */
        function create_session_id($uid) {
            $this->execute_query(sprintf("UPDATE users SET last_login = NOW() WHERE id = %d", $uid));
            $this->execute_query(sprintf("INSERT INTO `active_sessions` (uid, session_id) VALUES (%d, '%s')", $uid, session_id()));
        }

        /**
         * Destroy the session ID for the user
         * @return void
         */
        function destroy_session_id($uid) {
            $this->execute_query(sprintf("DELETE FROM `active_sessions` WHERE uid = %d", $uid));
        }

    }
?>