<?php
use Firebase\JWT\JWT;

class Api extends Controller
{
    public $model;
    public function __construct()
    {
        $this->model = $this->model('ApiModel');
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

    public function organisations($orgid = "", $userid = "")
    {

        // Handling POST requests
        if (Helper::getMethod() == 'POST') {
            if (!empty($orgid) && !empty($userid)) {
                // Add users to an organization

                $requestData = json_decode(file_get_contents("php://input"), true);

                $res = $this->model->addUsersToOrganization($orgid, $requestData);
                if (!$res) {
                    http_response_code(400);
                    echo json_encode([
                        "code" => 400,
                        "data" => json_encode([
                            'statusCode' => 400,
                            'message' => 'Client error'
                        ])
                    ]);
                    die();
                }

                // Successful response for adding users
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Users added to organization"
                ]);
                die();
            }

            // Create a new organization
            $requestData = json_decode(file_get_contents("php://input"), true);
            $createOrgResult = $this->model->createOrganization($requestData);

            if (!$createOrgResult) {
                echo json_encode([
                    "code" => 400,
                    "data" => [
                        'statusCode' => 400,
                        'message' => 'Client error (here)'
                    ]
                ]);
                die();

            }

            // Successful response for creating organization
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Organization created successfully",
                "data" => (object) [
                    'orgId' => $createOrgResult->orgId,
                    'name' => $createOrgResult->name,
                    'description' => $createOrgResult->description,
                ]
            ]);
            exit();
        }

        // Handling GET requests

        // Retrieve specific organization by identifier
        if (!empty($orgid)) {
            $organization = $this->model->fetchOrganizationById($orgid);

            if (!$organization) {
                http_response_code(400);
                echo json_encode([
                    "code" => 400,
                    "data" =>[
                        'statusCode' => 400,
                        'message' => 'Client error '
                    ]
                ]);
                die();

            }

            // Successful response for fetching organization by ID
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Organization fetched successfully",
                "data" => (object) [
                    'orgId' => $organization->orgid,
                    'name' => $organization->name,
                    'description' => $organization->description,
                ]
            ]);
            exit();
        }

        // Retrieve all organizations associated with the current user
        $organizations = $this->model->fetchAllOrganizations();

        if (!$organizations) {
            echo json_encode([
                "code" => 400,
                "data" => json_encode([
                    'statusCode' => 400,
                    'message' => 'Client error()'
                ])
            ]);
            die();
        }

        // Format and output organizations data as JSON
        $responseData = [];
        foreach ($organizations as $org) {
            $responseData[] = [
                "orgId" => $org->orgid,
                "name" => $org->name,
                "description" => $org->description,
            ];
        }

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Organizations fetched successfully",
            "data" => $responseData
        ]);
        exit();
    }





}