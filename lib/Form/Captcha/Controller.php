<?php
namespace project5\Form\Captcha;

use project5\Web\Request;
use project5\Web\Response;
use project5\Form\Captcha;

class Controller
{
    public function image(Request $request, Response $response)
    {
        return Captcha::DrawImage(
            $response,
            $request->getParam('name', $request->getAttribute('name')),
            $request->getParam('width', $request->getAttribute('width')),
            $request->getParam('height', $request->getAttribute('height'))
        );
    }
}