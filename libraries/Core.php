<?php

class Core
{
    protected $currentController = 'api';
    protected $currentMethod ;
    protected $params = [];

    public function __construct()
    {
        $url = $this->getUrl();

        // get the first value of the url and check if the class exists in the controller
        if (file_exists('./controller/' . ucwords($url[0]) . '.php')) {
            
            // if it exists, set it as the current controller 
            $this->currentController = $url[0];
            unset($url[0]);
        } else {
            // controller does not exist
            Response::set([
                'statusCode' => 404,
                'message' => 'Endpoint not found'
            ]);
            die();
        }

        require_once './controller/' . ucwords($this->currentController) . '.php';
        $this->currentController = new $this->currentController;
        if (isset($url[1])) {
            
            
            if (method_exists(($this->currentController), $url[1])) {
                $this->currentMethod = $url[1];
                unset($url[1]);
            }
        } else {
            // endpoint does not exist
            
            
            Response::set([
                'statusCode' => 404,
                'message' => 'Endpoint not found'
            ]);
            die();
        }

        $this->params = $url ? array_values($url) : [];
        
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    public function getUrl()
    {

        if (!empty($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            
            $url = filter_var($url, FILTER_SANITIZE_URL);

            return explode('/', $url);
        } else {

            Response::set([
                "statusCode"=>200,
                "message" => "Welcome to the User Authentication and Organisation API",
                "endpoints" => array(
                    "/auth/register" => "POST - Register a new user",
                    "/auth/login" => "POST - Log in a user",
                    "/api/users/:id" => "GET - Get user details [PROTECTED]",
                    "/api/organisations" => "GET - Get all organisations the user belongs to [PROTECTED]",
                    "/api/organisations/:orgId" => "GET - Get a single organisation record [PROTECTED]",
                    "/api/organisations" => "POST - Create a new organisation [PROTECTED]",
                    "/api/organisations/:orgId/users" => "POST - Add a user to an organisation [PROTECTED]"
                )
               
            ]);
            die();
        }
    }
}
