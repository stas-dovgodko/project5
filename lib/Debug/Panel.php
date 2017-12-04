<?php
namespace project5\Debug;

use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use project5\Web\IOutputHandler;
use project5\Web\Response;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Panel extends DebugBar implements IOutputHandler
{
    protected $bar;

    protected $includeVendors;

    /**
     * @var TimeDataCollector
     */
    protected $timeCollector;

    /**
     * @return TimeDataCollector
     */
    protected function getTimeCollector()
    {
        return $this->timeCollector;
    }

    public function __construct(TimeDataCollector $timeDataCollector, $includeVendors = true)
    {
        $this->includeVendors = $includeVendors;

        $this->addCollector(new RequestDataCollector());
        $this->addCollector($this->timeCollector = $timeDataCollector);
        $this->addCollector(new MemoryCollector());

    }

    /**
     * @param string $html
     * @return Response|null
     */
    public function handleHtml(&$html, Response $response)
    {
        $pos_head = stripos($html, '</head');

        if ($pos_head !== false) {
            $render = $this->getJavascriptRenderer();
            $render->setIncludeVendors($this->includeVendors);

            $head = [];
            foreach($render->getAssets('js') as $file) {
                $head[] = '<script>'.file_get_contents($file) . "</script>";
            }
            foreach($render->getAssets('css') as $file) {
                $head[] = '<style>'.file_get_contents($file) . "</style>";
            }

            $html =
                substr($html, 0, $pos_head) .
                "".
                substr($html, $pos_head);

            $pos_html = stripos($html, '</html');

            if ($pos_html !== false) {

                $head_html = implode("\n", $head);
                $html = substr($html, 0, $pos_html) . '' . $head_html . $render->render() . '' . substr($html, $pos_html);
            }
        }
    }

    /**
     * @param mixed $json
     * @return Response|null
     */
    public function handleJson(&$json, Response $response)
    {
        try {
            $this->stackData();
        } catch (DebugBarException $e) {

        }

        foreach($this->getDataAsHeaders('phpdebugbar', 4096) as $header_name => $header_value) {
            $response = $response->withHeader($header_name, $header_value);
        }

        return $response;
    }

    /**
     * @param StreamInterface $stream
     * @return Response|null
     */
    public function handleBinary(StreamInterface $stream, Response $response)
    {
        try {
            $this->stackData();
        } catch (DebugBarException $e) {

        }

        foreach($this->getDataAsHeaders('phpdebugbar', 4096) as $header_name => $header_value) {
            $response = $response->withAddedHeader($header_name, $header_value);
        }



        return $response;
    }


}