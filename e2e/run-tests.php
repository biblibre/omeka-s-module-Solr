<?php

$originalDir = __DIR__;
require dirname(__DIR__, 3) . '/bootstrap.php';

$opts = getopt('', ['gui']);

// Install a fresh database.
\Omeka\Test\DbTestCase::dropSchema();
\Omeka\Test\DbTestCase::installSchema();

$application = \Omeka\Test\DbTestCase::getApplication();
$serviceLocator = $application->getServiceManager();
$auth = $serviceLocator->get('Omeka\AuthenticationService');
$adapter = $auth->getAdapter();
$adapter->setIdentity('admin@example.com');
$adapter->setCredential('root');
$auth->authenticate();

$moduleManager = $serviceLocator->get('Omeka\ModuleManager');
$module = $moduleManager->getModule('Search');
$moduleManager->install($module);
$module = $moduleManager->getModule('Solr');
$moduleManager->install($module);

$connection = $serviceLocator->get('Omeka\Connection');
$connectionParams = $connection->getParams();

$pid = pcntl_fork();
if ($pid == -1) {
     die('could not fork');
} else if ($pid) {
    sleep(1);
    chdir($originalDir);
    if (isset($opts['gui'])) {
        system('CYPRESS_BASE_URL=http://localhost:8001/ npx cypress open', $result_code);
    } else {
        system('CYPRESS_BASE_URL=http://localhost:8001/ npx cypress run -q', $result_code);
    }
    posix_kill($pid, SIGTERM);
    pcntl_wait($status);
} else {
    $env = getenv();
    $env['OMEKA_DB_CONNECTION_URL'] = sprintf(
        'pdo-mysql://%s:%s@%s:%s/%s',
        $connectionParams['user'],
        $connectionParams['password'],
        $connectionParams['host'] ?? 'localhost',
        $connectionParams['port'] ?? 3306,
        $connection->getDatabase(),
    );
    pcntl_exec('/usr/bin/php', ['-q', '-S', '0.0.0.0:8001'], $env);
    die('Failed to start php built-in server');
}

exit($result_code);
