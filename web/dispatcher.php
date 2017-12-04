<?php
/*$has_xhprof = ((isset($_GET['profile']) || (isset($_COOKIE['xhprof']) && $_COOKIE['xhprof'])) && extension_loaded('xhprof'));

if ($has_xhprof && isset($_GET['profile']) && $_GET['profile'] == 0) {
    $has_xhprof = 0;
    setcookie('xhprof', 0, null, '/');
}

$xhprof_callback = null;
if ($has_xhprof) {
    setcookie('xhprof', 1, null, '/');
    $xhprof_callback = include(__DIR__ . '/_xhprof.php');
}*/

$front = new \project5\Web\Front();

$app = new \project5\Web\Application();

$response = $front->dispatch($app);

$front->flushResponse($response);

