<?php
include_once('Classes/LogParser.php');

$parser = new Classes\LogParser();
$parser->setPath('example/access_log');
echo $parser->getJson();
