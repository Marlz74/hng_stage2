<?php

class Controller {
    /**
     * Load a model
     *
     * This method requires the specified model file and returns an instance of the model class.
     *
     * @param string $model The name of the model to load
     * @return object An instance of the requested model
     */
    public function model($model) {
        // Require the model file
        require_once './model/' . $model . '.php';
        // Return an instance of the model
        return new (ucwords($model))();
    }
}
