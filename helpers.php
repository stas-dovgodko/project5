<?php
    namespace project5;

    use Monolog\Handler\ErrorLogHandler;
    use Monolog\Logger;
    use project5\Web\Front;

    function cli(\project5\Application $app)
    {
        $logger = new Logger('cli');

        $env = null;
        if (getenv("ENV")) {
            $env = getenv("ENV");
        }

        //$app->setLogger($logger);

        $app->configure($env)->get('cli.front')->handle($app);
    }

    function web(\project5\Application $app, callable $configurator = null)
    {
        $env = null;
        if (isset($_SERVER['ENV'])) {
            $env = $_SERVER['ENV'];
        }

        $front = $app->configure($env)->get('web.front');

        if ($front instanceof Front) {
            if ($configurator !== null) {
                call_user_func($configurator, $front);
            }
            $front->handle($app);
        } else {
            throw new \Exception('web.front should be project5\Web\Front instance');
        }


    }