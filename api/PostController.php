<?php 

require_once "class/DBModel.php";

class PostController{

    private $dbconn;

    function __construct(){
        $this->dbconn = new DBModel();
    }

    public function processRequest($method, $httpMethod, $search = null){

        // print_r(func_get_args());

        switch ($httpMethod) {

            case 'GET':
                if(strtolower($method) === 'search'){

                    if ($search) {
                        $response = $this->search($search);
                    } else {
                        $response = $this->getAllPost();
                    }
                }else if(strtolower($method) === 'import'){
                    $response = $this->loadPost();
                }else {
                    $response = $this->notFoundResponse(); 
                }
                break;
        }

        header('Content-type: application/json');
        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function loadPost(){

        // Load posts from json file
        $data = file_get_contents("postdata.json");
        $data = json_decode($data);

        foreach($data as $key => $val){

            $postId = trim($val->_id);
            $msg = trim($val->body);
            $msgArr = array_filter(explode(' ', $msg));

            // Insert post to post table
            $query = "INSERT INTO post_data (post_id,post) VALUES (?, ?)";
            $paramType = "ss";
            $paramValue = array($postId,$msg);
            $insertId = $this->dbconn->insert($query, $paramType, $paramValue);

            // Checking each word of post message from post indexed table
            foreach($msgArr as $word){

                $sword = addslashes($word);

                // Find keyword and get result
                $query = "SELECT id,post_ids FROM post_keyword WHERE MATCH(keyword) AGAINST('{$sword}') OR keyword like '%{$sword}' limit 1";
                $isExist = $this->dbconn->runBaseQuery($query);
                if($isExist){

                    $recId = $isExist[0]['id'];
                    $postIds = "{$isExist[0]['post_ids']},{$postId}";

                    // Update post id of same keyword
                    $query = "UPDATE post_keyword SET post_ids = ? WHERE id = ? ";
                    $paramType = "si";
                    $paramValue = array($postIds,$recId);
                    $this->dbconn->update($query, $paramType, $paramValue);

                }else{

                    // Insert new keyword record in indexed table
                    $query = "INSERT INTO post_keyword (keyword,post_ids) VALUES (?, ?)";
                    $paramType = "ss";
                    $paramValue = array($word,$postId);
                    $insertId = $this->dbconn->insert($query, $paramType, $paramValue);
                }
            }
        }

        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(array('status'=>1,'message' => 'Data import successfully'));
        return $response;
    }

    private function search($search){

        $wordArr = array_filter(explode(' ', trim(urldecode($search))));
        $data = [];
        
        foreach($wordArr as $word){

            $sword = addslashes($word);

            // Find keyword and get result
            $query = "SELECT post_ids FROM post_keyword WHERE MATCH(keyword) AGAINST('{$sword}') OR keyword like '%{$sword}' limit 1";
            $result = $this->dbconn->runBaseQuery($query);
            // print_r($result);
            if($result){

                $postIds = "'".str_replace(',', "','", $result[0]['post_ids'])."'";

                $query = "SELECT id, post_id, post  FROM post_data WHERE post_id IN ($postIds) ";
                $result = $this->dbconn->runBaseQuery($query);
                foreach($result as $record){

                    array_push($data,$record);
                }
            }
        }

        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(array('status'=>1,'data' => $data));
        return $response;
    }

    private function getAllPost(){
        
        $query = "SELECT id,post_id,post,added_on FROM post_data WHERE delete_status = 0 limit 10";
        $result = $this->dbconn->runBaseQuery($query);

        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(array('status'=>1,'data' => $result));

        return $response;

    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = json_encode(array('status'=>1,'message' => 'Welcome to post controller'));
        return $response;
    }

}

?>