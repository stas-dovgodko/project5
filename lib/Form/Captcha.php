<?php
    namespace project5\Form;

    use project5\Form;
    use project5\Form\Validator\Custom;
    use Gregwar\Captcha\CaptchaBuilder;
    use project5\Web\Response;
    use project5\Session;
    use project5\Stream\String;

    class Captcha extends Field
    {
        const SESSION_NAMESPACE = 'captcha';

        private $imgSrcUrl;

        /**
         * @var Session
         */
        private $session;

        /**
         * @var CaptchaBuilder
         */
        protected $builder;

        public function __construct($name, Session $session, $imgSrcUrl = null, $title = null)
        {
            parent::__construct($name, $title);

            $this->imgSrcUrl;
            $this->session;

            $this->builder = new CaptchaBuilder();
        }

        /**
         * @return IValidator[]
         */
        public function getValidators(Form $form)
        {
            $builder = $this->builder;
            $validator = new Custom(function($value) use($builder, $form) {

                $session = $form->getRequest()->getSession();

                if ($session && $test = $session->get(self::SESSION_NAMESPACE, $this->getName())) {
                    $builder->setPhrase($test);
                }

                return $builder->testPhrase($value);
            }, 'Wrong secure code');

            return [$validator];
        }

        public function getImgSrc()
        {
            if ($this->imgSrcUrl) {
                return $this->imgSrcUrl;
            } else {
                $this->builder->build();

                $this->session->set(self::SESSION_NAMESPACE, $this->getName(), $this->builder->getPhrase());

                return $this->builder->inline();
            }
        }

        public static function DrawImage(Response $response, $name, $width = 150, $height = 40)
        {
            $builder = new CaptchaBuilder;
            $builder->build($width, $height);

            $response->getSession()->set(self::SESSION_NAMESPACE, $name, $builder->getPhrase());

            return $response->withAddedHeader(Response::HEADER_CONTENT_TYPE, 'image/jpeg')->withBody(new String($builder->get()));
        }
    }