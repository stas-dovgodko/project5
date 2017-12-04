<?php
define('XHPROF_ENABLED', 1);
//ob_start();
xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

return function($output, &$headers) {

    $xhprof_data = xhprof_disable();



    $xhprof_runs = new XHProfRuns_Default();
    $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_test");


    if (!is_array($headers)) {
        $headers = [];
    }

    $headers[] = 'X-XHPROF: '.($url = "/?run=$run_id&source=xhprof_test&profile=1");
    setcookie('xhprof_url', $url, null, '/');

    $parts = parse_url($_SERVER['REQUEST_URI']);
    $parts['profile'] = '0';
    $uri = (($p = strpos($_SERVER['REQUEST_URI'], '?')) ? substr($_SERVER['REQUEST_URI'],0,$p) : $_SERVER['REQUEST_URI']).'?'.http_build_query($parts);

    if (stripos($output, '<html') !== false) return $output . '<div style="position:fixed;background: #ff0000; top:0px; height: 20px; width: 110px; z-index:10002 "><a href="'."/xhprof_html/index.php?run=$run_id&source=xhprof_test&profile=1".'" target="_blank">PROFILE</a>&#160;|&#160;<a href="'.$uri.'">[X]</a></div>';
    else return $output;

};