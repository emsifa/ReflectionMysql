<?php namespace Emsifa\ReflectionMysql;

use Exception;

class ConnectionFailedException extends Exception {

    protected $message = "Connection failed";

}