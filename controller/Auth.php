<?php
use Firebase\JWT\JWT;

class Auth extends Controller
{
    public $model;
    public function __construct()
    {
        $this->model = $this->model('ApiModel');
    }

    public function register()
    {

        if (Helper::getMethod() == 'POST') {
            $rawData = json_decode(file_get_contents("php://input"), true);
            echo $this->model->register($rawData);
            die();
        }
        Response::set([
            'statusCode' => 400,
            'status' => "Bad Request",
            'message' => 'Client error'
        ]);
        die();
    }
    public function login()
    {

        if (Helper::getMethod() == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $response = $this->model->login($data);
            http_response_code($response['code']);
            echo $response['data'];
            die();
        }
        Response::set([
            'statusCode' => 400,
            'status' => "Bad Request",
            'message' => 'Client error'
        ]);
        die();
    }

    public function users($id = "")
    {
        if (Helper::getMethod() == 'GET') {

            if (empty($id) || !isset($id)) {
                http_response_code(400);
                echo json_encode([
                    'statusCode' => 400,
                    'status' => "Bad Request",
                    'message' => 'Client error'
                ]);
                die();
            }

            // Get user record based on user ID and payload user ID
            $response = $this->model->access(true);
            if ($response['code'] !== 200) {
                http_response_code($response['code']);
                echo json_encode($response['data']);
                die;
            }




            // Format and output user data as JSON
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "User fetched successfully",
                "data" => [
                    "userId" => $response['userData']->userid,
                    "firstName" => $response['userData']->firstname,
                    "lastName" => $response['userData']->lastname,
                    "email" => $response['userData']->email,
                    "phone" => $response['userData']->phone,
                ]
            ]);
            die();
        }
        Response::set([
            'statusCode' => 400,
            'status' => "Bad Request",
            'message' => 'Client error, Invalid request method(Make a GET request) '
        ]);
        die();
    }






}