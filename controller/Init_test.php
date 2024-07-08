<?php

class Init_test extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = $this->model("test_");
        
        $this->index();
        
    }

    public function index()
    {
     
        
        $users = $this->model->refresh("users", [
            'userId' => "SERIAL PRIMARY KEY",
            'firstName' => "VARCHAR(255)",
            'lastName' => "VARCHAR(255)",
            'email' => "VARCHAR(255) UNIQUE",
            'password' => "VARCHAR(255)",
            'phone' => "VARCHAR(255)",
        ]);

        $organisation = $this->model->refresh("organisations", [
            "orgId" => "SERIAL PRIMARY KEY",
            "name" => "VARCHAR(255)",
            "description" => "TEXT",
            "owner"=> "INTEGER"
        ]);

        $organisation_owner = $this->model->refresh("user_organisations", [
            "orgId" => "INTEGER",
            "userId" => "INTEGER",
        ]);

        

        if ($users && $organisation && $organisation_owner ) {
            Response::set([
                'statusCode' => 200,
                'message' => "Refreshed successfully"
            ]);
        } else {
            Response::set([
                'statusCode' => 500,
                'message' => "Refresh failed"
            ]);
        }
        exit();
    }

    public function drop($tableName = "")
    {
        if (empty($tableName)) {
            Response::set([
                'statusCode' => 400,
                'message' => "Unknown model to drop."
            ]);
            exit();
        }

        $res = $this->model->dropRefresh($tableName);

        if ($res) {
            Response::set([
                'statusCode' => 200,
                'message' => "Refresh dropped successfully for $tableName"
            ]);
        } else {
            Response::set([
                'statusCode' => 500,
                'message' => "Failed to drop refresh for $tableName"
            ]);
        }
        exit();
    }
}
?>