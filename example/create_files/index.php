<?php

use medeirosinacio\ExecMultithread;

$thread = new  ExecMultithread();
$thread->setProjectRoot('./');

for ($i = 0; $i < 5; $i++) {
	$data = "Bla Bla Bla... ID " . md5(rand(0, 5000));
	$thread->startProcess('worker', $data);
}

var_dump($thread->getProcessesInfo());