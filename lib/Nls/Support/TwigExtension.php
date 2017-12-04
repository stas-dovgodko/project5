<?php
namespace project5\Nls\Support;

use project5\Nls\LocaleManager;
use project5\Template\Templater\Twig\IExtension;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;
use Twig_Environment;
use project5\Nls\I18n\Translator;

class TwigExtension implements IExtension
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var LocaleManager
     */
    private $manager;

    public function __construct(Translator $translator, LocaleManager $manager)
    {
        $this->translator = $translator;
        $this->manager = $manager;
    }

    public function extendTwig(Twig_Environment $twig, array $options = [])
    {
        $locale = $this->manager->getLocale();

        $twig->addFilter('_', new \Twig_SimpleFilter('_', function () use ($locale) {
            $args = func_get_args();

            $key = array_shift($args);

            $translation = $this->translator->getTranslation($locale, $key);
            $lexem = ($translation !== null) ? $translation : $key;

            try {
                if (sizeof($args) == 1 && is_array($args[0])) $args = $args[0];
                $sprintf_args = array_merge([$lexem], $args);

                return call_user_func_array('sprintf', $sprintf_args);
            } catch (\Exception $e) {
                throw new \DomainException("Can\\'t map \"$lexem\" with \"".print_r($args, true)."\" arguments");
            }
        }));



        $twig->addFilter('money', new \Twig_SimpleFilter('money', function () use ($locale) {
            $args = func_get_args();

            $money = array_shift($args);
            if (sizeof($args) > 0) {
                $currency = array_shift($args);

                if (class_exists('\NumberFormatter')) {
                    $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
                } else {
                    $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
                }
                return $formatter->formatCurrency($money, $currency);
            }
            else {
                throw new \DomainException("Missed required currency param");
            }



        }));

        $twig->addFilter(new \Twig_SimpleFilter('trans', 'gettext')); // ?

        $twig->addTokenParser(new \Twig_Extensions_TokenParser_Trans());
    }
}
