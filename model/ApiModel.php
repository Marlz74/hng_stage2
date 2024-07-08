<?php
class ApiModel
{
    private $db;
    public function __construct()
    {
        $this->db = new Db();
    }
    public function status()
    {
        $this->db->query("SELECT * FROM users");
        $this->db->execute();
        return $this->db->resultSet();
    }

    /**
     * Validates input fields for registration.
     *
     * @param array $arr Associative array of input fields and values.
     * @return array Array of validation errors.
     */
    private function inputValidate($arr)
    {
        $errors = [];
        foreach ($arr as $key => $e) {
            if (empty($e) || !isset($e)) {
                array_push($errors, ["field" => "$key", "message" => "$key is invalid"]);
            }
        }
        return $errors;
    }

    /**
     * Registers a new user.
     *
     * @param object $data An object containing user registration data.
     * @return bool True if registration is successful, false otherwise.
     */
    public function register($data)
    {
        // print_r($data); die;
        extract((array) $data); // Extracts data object into variables

        $errors = [];

        // Validate email field
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            array_push($errors, ["field" => "email", "message" => "email is invalid"]);
        }

        // Validate phone field
        if (empty($phone) || !isset($phone)) {
            array_push($errors, ["field" => "phone", "message" => "phone number is invalid"]);
        }

        // Validate other input fields using inputValidate method
        $errors = array_merge($errors, $this->inputValidate(["firstName" => @$firstName, "lastName" => @$lastName, "password" => @$password]));

        // If there are validation errors, return false
        if (count($errors) > 0) {
            http_response_code(422);
            return json_encode([
                "errors" => $errors
            ]);
            // return false;
        }

        // Check if user already registered
        $this->db->query("SELECT firstname from users where email=:email")
            ->bind(":email", $email)
            ->execute();

        // If email already exists, return error response
        if ($this->db->rowCount() > 0) {
            http_response_code(422);
            return json_encode([
                "errors" => ["field" => "email", "message" => "Email already exists"]
            ]);
            // return false;
        }

        // Register new user
        $this->db->query("INSERT INTO users(firstname,lastname,email,password,phone) values(:firstname,:lastname,:email,:password,:phone)")
            ->bind(":firstname", Helper::sanitize($firstName))
            ->bind(":lastname", Helper::sanitize($lastName))
            ->bind(":email", Helper::sanitize($email))
            ->bind(":phone", Helper::sanitize($phone))
            ->bind(":password", Helper::encryptPassword($password)) // Hash password before storing
            ->execute();

        // If registration query fails, return error response
        if ($this->db->rowCount() == 0) {
            http_response_code(400);
            return json_encode([
                "status" => "Bad request",
                "message" => "Registration unsuccessful",
                "statusCode" => 400
            ]);
            // return false;
        }

        // Retrieve newly registered user's ID
        $userId = $this->db->lastInsertId();

        // Generate JWT token for authentication

        $jwt = Helper::generateJWT([
            'userId' => $userId
        ]);

        // Create organization for the user
        $this->db->query("INSERT INTO organisations(name,description,owner) values(:name,:description,:owner)")
            ->bind(":name", "$firstName's Organisation")
            ->bind(":owner", $userId)
            ->bind(":description", "description about this organisation")
            ->execute();

        // Retrieve newly created organization's ID
        $orgId = $this->db->lastInsertId();



        // Add user to the organization
        $this->db->query("INSERT INTO user_organisations(orgid,userid) values(:id,:user)")
            ->bind(":id", $userId)
            ->bind(":user", $userId)
            ->execute();

        // Return success response with JWT token and user data
        http_response_code(201);
        return json_encode([
            "status" => "success",
            "message" => "Registration successful",
            "data" => [
                "accessToken" => $jwt,
                "user" => [
                    "userId" => $userId,
                    "firstName" => $firstName,
                    "lastName" => $lastName,
                    "email" => $email,
                    "phone" => $phone,
                ]
            ]
        ]);

        // return true;
    }


    public function login($data)
    {
        // Extract email and password from input data
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        // Validate the input data

        if (!$this->validateLoginData($email, $password)) {
            return [
                "code" => 401,
                "data" =>
                    json_encode(Helper::createResponse(401, "Authentication failed", "Bad request"))
            ];
        }

        // Attempt to find user with the provided email and password
        $user = $this->getUserByEmailAndPassword($email, $password);

        // If user exists, generate JWT and send success response

        if ($user) {
            // Generate JWT token for the authenticated user

            $jwt = Helper::generateJWT(['userId' => $user->userid]);

            // Prepare success response data
            return [
                "code" => 200,
                "data" =>
                    json_encode([
                        "status" => "success",
                        "message" => "Login successful",
                        "data" => [
                            "accessToken" => $jwt,
                            "user" => [
                                "userId" => $user->userid,
                                "firstName" => $user->firstname,
                                "lastName" => $user->lastname,
                                "email" => $user->email,
                                "phone" => $user->phone,
                            ]
                        ]
                    ])
            ];

        }

        // If user not found, send error response
        return [
            "code" => 400,
            "data" =>
                json_encode(Helper::createResponse(400, "Login unsuccessful", "Bad request"))
        ];
    }

    /**
     * Validate the email and password fields.
     *
     * @param string|null $email
     * @param string|null $password
     * @return bool
     */
    private function validateLoginData($email, $password)
    {

        return !(empty($email) || empty($password));
    }

    /**
     * Retrieve user by email and hashed password.
     *
     * @param string $email
     * @param string $password
     * @return object|null
     */
    private function getUserByEmailAndPassword($email, $password)
    {
        // Query to find user by email and hashed password
        $userDetails = $this->db->query("SELECT * from users where email=:email ")->bind(':email', $email)->single();

        if ($this->db->rowCount() > 0) {
            if (Helper::decryptPassword($password, $userDetails->password)) {
                unset($userDetails->password);
                return $userDetails;
            }
        }
        return null;
        // Helper::decryptPassword
    }

    /**
     * Create a response array with the given status code, message, and status.
     *
     * @param int $statusCode
     * @param string $message
     * @param string $status
     * @return array
     */


    /**
     * Create a success response with the JWT token and user data.
     *
     * @param object $user
     * @return array
     */


    public function getUser($id)
    {
        // Retrieve the user record from the database
        $user = $this->db->query("SELECT *, NULL as password from users where userid=:id")
            ->bind(':id', $id)
            ->single();

        // Return the user record (without the password field)
        unset($user->password);


        return $user;
    }



    public function access($state)
    {
        try {
            if ($state) {
                @$token = Helper::getBearerToken();
                if (!$token) {
                    return [
                        "code" => 400,
                        "data" => json_encode([
                            'statusCode' => 400,
                            'message' => 'Invalid or JWT token  not found'
                        ])
                    ];
                    // No JWT token found
                }

                @$jwtData = Helper::validateJWT($token); // Validate JWT token
                if ($jwtData['state'] === false) {
                    return [
                        "code" => 401,
                        "data" => json_encode(([
                            'statusCode' => 401,
                            'message' => $jwtData['data']
                        ]))
                    ];

                }
                $jwtData['data']->iat = (new DateTime())->setTimestamp($jwtData['data']->iat)->format('Y-m-d H:i:s');
                $jwtData['data']->exp = (new DateTime())->setTimestamp($jwtData['data']->exp)->format('Y-m-d H:i:s');

                return ["code" => 200, "data" => $jwtData['data'], "userData" => $this->getUser($jwtData['data']->userId)];
            }
            //code...
        } catch (\Throwable $th) {
            //throw $th;
            return [
                "code" => 400,
                "data" => json_encode([
                    'statusCode' => 400,
                    'message' => 'Invalid or JWT token not found'
                ])
            ];
        }
        // No JWT token found Deny access

    }
    public function isLoggedIn($state)
    {
        try {
            if ($state) {
                @$token = Helper::getBearerToken();
                if (!$token) {
                    return null;
                    // No JWT token found
                }

                @$jwtData = Helper::validateJWT($token); // Validate JWT token
                if ($jwtData['state'] === false) {
                    return null;

                }
                @$jwtData = Helper::validateJWT($token); // Validate JWT token
                
                return $jwtData['data']->userId;
            }
            //code...
        } catch (\Throwable $th) {
            //throw $th;
            return null;
        }
        // No JWT token found Deny access

    }

    public function addUsersToOrganization($orgId, $requestData)
    {
        if (!$this->isLoggedIn(true)) {
            return false;
        }
        // Validate inputs
        if (empty($orgId) || empty($requestData['userId'])) {
            return false; // Return false if required parameters are missing
        }
    // Check if user is already added to the organization
        
        $isUserAdded = $this->db->query("SELECT COUNT(*) as count FROM user_organisations WHERE orgid = :orgId AND userid = :userId")
            ->bind(":orgId", $orgId)
            ->bind(":userId", $requestData['userId'])
            ->single();
            
        // If user is already added to the organization, return false
        if ($isUserAdded && $isUserAdded->count > 0) {
            
            return false;
        }

        // Add user to organization

        $this->db->query("INSERT INTO user_organisations (orgid, userid) VALUES (:orgId, :userId)")
            ->bind(":orgId", $orgId)
            ->bind(":userId", $requestData['userId'])
            ->execute();

        // Return true if user was added successfully
        return $this->db->rowCount() > 0;


    }

    /**
     * Creates a new organization.
     *
     * @param string $userId The ID of the user creating the organization.
     * @param object $data An object containing 'name' and 'description' of the organization.
     * @return object|bool An object with 'orgId', 'name', and 'description' if successful, otherwise false.
     */
    public function createOrganization( $data)
    {
        $userId=$this->isLoggedIn(true);
        
        if(!$userId) return false;

        // Extract 'name' and 'description' from $data object
        $name = isset($data['name']) ? Helper::sanitize($data['name']) : '';
        $description = isset($data['description']) ? Helper::sanitize($data['description']) : '';

        // Validate 'name'
        if (empty($name)) {
            return false;
        }
        try {
            // Check if organization with the same name already exists
            $existingOrg = $this->db->query("SELECT orgid FROM organisations WHERE name = :name")
                ->bind(":name", $name)
                ->single();

            if ($existingOrg) {
                return false; // Organization with the same name already exists
            }
            

            // Begin transaction
            $this->db->beginTransaction();

            // Insert organization
            
            $this->db->query("INSERT INTO organisations(name, description,owner) VALUES (:name, :description,:owner)")
                ->bind(":name", $name)
                ->bind(":description", $description)
                ->bind(":owner", $userId)
                ->execute();

            $orgId = $this->db->lastInsertId(); // Get the last inserted ID (orgId)
            

            // Insert user into organization
            
            $this->db->query("INSERT INTO user_organisations (orgid, userid) VALUES (:orgId, :userId)")
                ->bind(":orgId", $orgId)
                ->bind(":userId", $userId)
                ->execute();

            // Commit transaction
            $this->db->commit();

            // Return an object with organization details
            return (object) ['orgId' => $orgId, 'name' => $name, 'description' => $description];

        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            return false;
        }
    }


    public function fetchOrganizationById($id){
        $userid=$this->isLoggedIn(true);
        if (!$userid) {
            return false;
        }
        return $this->db->query("SELECT * FROM organisations WHERE orgid = :id AND owner=:userid LIMIT 1")
            ->bind(":id", $id)
            ->bind(":userid", $userid)
            ->single();

    }

    public function fetchAllOrganizations(){
        $userid=$this->isLoggedIn(true);
        if (!$userid) {
            return false;
        }
        return $this->db->query("SELECT o.*
                FROM user_organisations us
                    LEFT JOIN organisations o ON o.orgid = us.orgid WHERE us.userid=:userid;")
            ->bind(":userid", $userid)
            ->resultSet();
    }








}