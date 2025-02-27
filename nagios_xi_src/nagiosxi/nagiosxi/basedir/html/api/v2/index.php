<?php
//
//  Nagios XI 5 API v2
//  Copyright (c) 2015-2020 Nagios Enterprises, LLC. All rights reserved.
//

namespace api\v2;
use \Exception as Exception;
use \ReflectionException as ReflectionException;
use \ReflectionClass as ReflectionClass;

require_once(dirname(__FILE__) . '/../../includes/common.inc.php');
require_once(dirname(__FILE__) . '/endpoints/endpoints.php');

pre_init();
init_session(true);

grab_request_vars();
check_prereqs();
check_authentication(false, false, false, true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT');

// Process API request
try {
    $api_base = get_base_url(false);
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace($api_base, "", $uri);

    $class = str_replace("/", '\\', $uri);
    $request_method = strtolower($_SERVER['REQUEST_METHOD']);
    $auth_method = "authorized_for_".$request_method;

    // Make sure endpoint is not abstract and extends Base
    $reflection = new ReflectionClass($class);
    if ($reflection->isAbstract() || !$reflection->isSubclassOf("api\\v2\\Base")) {
        throw new Exception("Invalid Request", 404);
    }

    // In case someone submits a request that is GET POST PUT DELETE
    if(!$reflection->hasMethod($request_method) || !$reflection->hasMethod($auth_method)) {
        throw new Exception('Invalid Request Method', 405);
    }

    $api = new $class;
    if(!$api->$auth_method()) {
        throw new Exception('Not Authorized For Request Method', 403);
    }
    $response = $api->$request_method();

} catch (ReflectionException $e) {
    http_response_code(404);
    $response = ['message' => "Invalid Request"];
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code != 0) {
        http_response_code($e->getCode());
    } else {
        http_response_code(500);
    }
    $response = ['message' => $e->getMessage()];
}

$response = json_encode($response);
echo $response;

abstract class Base {
    public function get() {
        throw new Exception('Invalid Request Method', 405);
    }
    public function authorized_for_get() {
        return false;
    }
    public function post() {
        throw new Exception('Invalid Request Method', 405);
    }
    public function authorized_for_post() {
        return false;
    }
    public function put() {
        throw new Exception('Invalid Request Method', 405);
    }
    public function authorized_for_put() {
        return false;
    }
    public function delete() {
        throw new Exception('Invalid Request Method', 405);
    }
    public function authorized_for_delete() {
        return false;
    }
}