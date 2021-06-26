<?php
class DBModel {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "postsearch";
    private $conn;
    
    function __construct() {
        return $this->conn = $this->connectDB();
    }   
    
    // Function to connect database
    function connectDB() {
        
        $conn = new mysqli($this->host, $this->user,$this->password,$this->database);
        if(mysqli_connect_error()) {
            trigger_error("Failed to connect to MySQL: " . mysqli_connect_error());
        }else{
            return $conn;
        }
    }
    
    // Method to get results from table using query
    function runBaseQuery($query) {
        $resultset = [];
        $result = $this->conn->query($query);   
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $resultset[] = $row;
            }
        }
        return $resultset;
    }
    
    // Method to get results from the table using prepare query
    function runQuery($query, $param_type, $param_value_array) {
        $sql = $this->conn->prepare($query);
        $this->bindQueryParams($sql, $param_type, $param_value_array);
        $sql->execute();
        $result = $sql->get_result();
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $resultset[] = $row;
            }
        }
        
        if(!empty($resultset)) {
            return $resultset;
        }
    }
    
    // Method to bing prepare query parameters
    function bindQueryParams($sql, $param_type, $param_value_array) {
        $param_value_reference[] = & $param_type;
        for($i=0; $i<count($param_value_array); $i++) {
            $param_value_reference[] = & $param_value_array[$i];
        }
        call_user_func_array(array(
            $sql,
            'bind_param'
        ), $param_value_reference);
    }
    
    // Insert records in the table
    function insert($query, $param_type, $param_value_array) {
        $sql = $this->conn->prepare($query);
        $this->bindQueryParams($sql, $param_type, $param_value_array);
        $sql->execute();
        $insertId = $sql->insert_id;
        return $insertId;
    }
    
    // Update records in the table
    function update($query, $param_type, $param_value_array) {
        // print_r(func_get_args());
        $sql = $this->conn->prepare($query);
        $this->bindQueryParams($sql, $param_type, $param_value_array);
        $sql->execute();
    }
}
?>