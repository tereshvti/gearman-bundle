<?php

$loader = require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/sf2app/app/AppKernel.php';

$kernel = new AppKernel('test', true);
$kernel->boot();
$gmc = $kernel->getContainer()->get('supertag_gearman.client');


//$gmc->doBackground('failing.gearman.job', 'workload');
//exit(0);
for ($i = 0; $i < 100; $i++) {
    $gmc->doBackground('failing.gearman.job', 'workload' . $i);
    if ($i % 10 === 0) {
        $gmc->doLowBackground('sleepy.gearman.job', 'workload' . $i);
    }
    if ($i === 50 || $i === 80) {
        $gmc->doHighBackground('high.gearman.job', 'workload'.$i);
    }
    $gmc->doBackground('normal.gearman.job', 'workload' . $i);
}
$kernel->shutdown();
