<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$core = new Core();
$core->dispatch();
