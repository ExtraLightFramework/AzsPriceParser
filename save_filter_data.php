<?php

require_once 'header.php';
require_once 'functions.php';

$_SESSION[$_POST['name']] = $_POST['val'];
