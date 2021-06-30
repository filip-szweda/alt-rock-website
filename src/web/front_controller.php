<?php

require_once '../dispatcher.php';
require_once '../controllers.php';
require_once '../routing.php';
require '../../vendor/autoload.php';

session_start();

$action_url= $_GET['action'];

dispatch($routing, $action_url);