# PHP Exec Multithread
###### php-exec-multithread

A thread implementation in PHP using the exec function.

Send job to worker with data:

```

    $thread = new  ExecMultithread();
    $thread->setProjectRoot('./');
    
    for ($i = 0; $i < 5; $i++) {
    	$data = "Bla Bla Bla... ID " . md5(rand(0, 5000));
    	$thread->startProcess('worker', $data);
    }
    
    $thread->getProcessesInfo();


```

get data worker:

```

   $data = ExecMultithread::convertData($argv[1])


```