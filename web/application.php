<?php
$logger = new \Monolog\Logger('www');

$level = error_reporting();
if ($level & E_NOTICE) {
    $logger->pushHandler(new \Monolog\Handler\SyslogHandler(!empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:$_SERVER['SERVER_ADDR'], LOG_USER, \Monolog\Logger::NOTICE));
} elseif ($level & E_WARNING) {
    $logger->pushHandler(new \Monolog\Handler\SyslogHandler(!empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:$_SERVER['SERVER_ADDR'], LOG_USER, \Monolog\Logger::WARNING));
} elseif ($level & E_ERROR) {
    $logger->pushHandler(new \Monolog\Handler\SyslogHandler(!empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:$_SERVER['SERVER_ADDR'], LOG_USER, \Monolog\Logger::ERROR));
}

$app = new \project5\Application();
$app->setLogger($logger);

$app->setProperty('app.dir', __DIR__ . '/');
$app->setProperty('compile.dir', __DIR__ . '/../tmp/');
$app->setProperty('web.dir', __DIR__ . '/public/');
$app->addConfig(__DIR__ . '/config/front.yml');

if (isset($_SERVER['ENV'])) {
    $local_config = __DIR__ . '/config/env/' . $_SERVER['ENV'] . '.yml';
    if (is_file($local_config)) {
        $app->addConfig($local_config);
    }
}

$local_config = __DIR__ . '/../local.json';
if (is_file($local_config)) {
    $app->addConfig($local_config);
}

$app->configure();
$front = $app->getContainer()->get('web.front');

//$front = new \project5\Web\Front($app);
//$front->attachOutputCallback($handler);
$front->handle($app);

