<?php
namespace project5\Debug\Decorator\Web;

use project5\Debug\Decorator\TimeCollector;
use project5\Web\Front as ConcreteFront;

class Front extends ConcreteFront {

    use TimeCollector;


}