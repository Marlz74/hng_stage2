<?php

use PHPUnit\Framework\TestCase;

/**
 * Class AuthTest
 *
 * PHPUnit test class for testing authentication and registration endpoints of an API.
 */
class Auth extends TestCase
{
    /**
     * @var string $apiBaseUrl Base URL of the API being tested.
     */
    protected $apiBaseUrl;

    /**
     * Sets up the test environment before each test method runs.
     * Initializes $apiBaseUrl and executes migration endpoint.
     */
    protected function setUp(): void
    {
        $this->apiBaseUrl = 'http://localhost/hng/stage_22/'; // Adjust this URL as needed for your environment
        $this->sendRequest('GET', '/init_test');
    }

    /**
     * Tests registering a user successfully with default organisation creation.
     * Sends a POST request to /auth/register and verifies the response.
     */
    public function testRegisterUserSuccessfullyWithDefaultOrganisation()
    {
        $response = $this->sendRequest('POST', '/auth/register', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'johndoe@gmail.com',
            'password' => 'password',
            'phone' => '028348234'
        ]);
        

        $organisation = $this->sendRequest('GET', '/api/organisations', [], $response['body']['data']['accessToken'])['body']['data'][0];

        $this->assertEquals(201, $response['status']); // Assuming 201 Created for a successful registration
        $this->assertEquals('success', $response['body']['status']);
        $this->assertEquals('Registration successful', $response['body']['message']);
        $this->assertEquals("John's Organisation", $organisation['name']);
        $this->assertArrayHasKey('accessToken', $response['body']['data']);
        $this->assertArrayHasKey('user', $response['body']['data']);
        $this->assertIsString($response['body']['data']['user']['userId']);
        $this->assertIsString($response['body']['data']['user']['firstName']);
        $this->assertIsString($response['body']['data']['user']['lastName']);
        $this->assertIsString($response['body']['data']['user']['email']);
        $this->assertIsString($response['body']['data']['user']['phone']);
    }

    /**
     * Tests registration failure scenarios when required fields are missing.
     * Iterates over required fields and verifies error responses.
     */
    public function testRegisterFailsIfRequiredFieldsAreMissing()
    {
        $requiredFields = ['firstName', 'lastName', 'email', 'password'];

        foreach ($requiredFields as $field) {
            $requestData = [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'phone' => '1122',
            ];
            unset($requestData[$field]);

            $response = $this->sendRequest('POST', '/auth/register', $requestData);

            $this->assertEquals(422, $response['status']);
            $this->assertArrayHasKey('errors', $response['body']);
            $this->assertContains(["field" => $field, "message" => "$field is invalid"], $response['body']['errors']);
        }
    }

    /**
     * Tests logging in a registered user successfully.
     * Registers a user and then logs in with valid credentials.
     */
    public function testLoginUserSuccessfully()
    {
        $this->sendRequest('POST', '/auth/register', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'johndoe@gmail.com',
            'password' => 'password',
            'phone' => '028348234'
        ]);
        $response = $this->sendRequest('POST', '/auth/login', [
            'email' => 'johndoe@gmail.com',
            'password' => 'password'
        ]);

        $this->assertEquals(200, $response['status']); // Assuming 200 OK for a successful login
        $this->assertEquals('success', $response['body']['status']);
        $this->assertEquals('Login successful', $response['body']['message']);
        $this->assertArrayHasKey('accessToken', $response['body']['data']);
        $this->assertArrayHasKey('user', $response['body']['data']);
        $this->assertIsInt($response['body']['data']['user']['userId']);
        $this->assertIsString($response['body']['data']['user']['firstName']);
        $this->assertIsString($response['body']['data']['user']['lastName']);
        $this->assertIsString($response['body']['data']['user']['email']);
        $this->assertIsString($response['body']['data']['user']['phone']);
    }

    /**
     * Tests registration failure when attempting to register with a duplicate email address.
     * Registers a user with a specific email address and attempts to register another user with the same email.
     */
    public function testRegisterFailsIfDuplicateEmail()
    {
        $this->sendRequest('POST', '/auth/register', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'phone' => '1928348234'
        ]);

        $response = $this->sendRequest('POST', '/auth/register', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'phone' => '345324'
        ]);
        $this->assertEquals(422, $response['status']);
        $this->assertArrayHasKey('errors', $response['body']);
        $this->assertEquals(["field" => 'email', "message" => 'Email already exists'], $response['body']['errors']);
    }

    /**
     * Executes an HTTP request to the API endpoint using cURL.
     *
     * @param string $method HTTP method (POST, GET, etc.).
     * @param string $uri Relative URI of the API endpoint.
     * @param array $payload Optional. Data to send with the request as JSON payload.
     * @param string|null $authToken Optional. JWT token for authentication.
     * @return array An associative array containing 'status' (HTTP status code) and 'body' (decoded JSON response body).
     */
    private function sendRequest($method, $uri, $payload = [], $authToken = null)
    {
        $endpoint = $this->apiBaseUrl . ltrim($uri, '/');
        $headers = [
            'Content-Type: application/json'
        ];

        if ($authToken) {
            $headers[] = 'Authorization: Bearer ' . $authToken;
        }

        $curlOptions = [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($payload);
        } elseif ($method === 'GET') {
            $endpoint .= '?' . http_build_query($payload);
            $curlOptions[CURLOPT_URL] = $endpoint;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpStatus,
            'body' => json_decode($response, true)
        ];
    }
}
