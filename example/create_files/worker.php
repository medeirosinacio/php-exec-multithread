<?php

use medeirosinacio\ExecMultithread;

file_put_contents(__DIR__ . "/tmp/" . md5(date('H.i.scu')) . ".txt", ExecMultithread::convertData($argv[1]), '777');