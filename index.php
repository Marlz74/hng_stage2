<?php
require_once ('./libraries/firebase/JWT.php');
require_once ('./libraries/firebase/Key.php');



header('Content-Type: application/json');
spl_autoload_register(function($className){
    require_once 'libraries/'.$className.'.php';
});

 EnvLoader::load('./.env');

new Core();






