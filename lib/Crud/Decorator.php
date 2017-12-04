<?php
namespace project5\Crud;

use project5\Form\Checkbox;
use project5\Provider\Field\Boolean;
use project5\Provider\Field\Date;
use project5\Provider\IField;
use project5\Provider\IEntity;

use project5\Form\Field as FormField;
use project5\Form\Date as FormDataField;
use project5\Template\ITemplater;

class Decorator implements IField
{
    const FLAG_HIDE_LIST = 1;
    const FLAG_IMMUTABLE = 2;
    const FLAG_HIDE_FILTER = 4;

    protected $flags = [];

    /**
     * @var IField
     */
    protected $field;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var callable|null
     */
    protected $urlCallback = null;

    /**
     * @var callable|null
     */
    protected $htmlCallback = null;

    protected $setCallback = null;

    protected $getCallback = null;

    protected $formfieldCallback = null;

    private $formField = null;

    public function __construct(IField $field)
    {
        $this->field = $field;
        $this->title = $field->getTitle();
    }

    public function isFlagEnabled($option, $default = false)
    {
        return isset($this->flags[$option]) ? $this->flags[$option] :  $default;
    }

    public function setFlag($option, $state = true)
    {
        $this->flags[$option] = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function hasUrl()
    {
        return is_callable($this->urlCallback);
    }

    public function getUrl(IEntity $data)
    {
        if (is_callable($this->urlCallback)) {
            return call_user_func($this->urlCallback, $data);
        }
        return null;
    }

    public function hasHtml()
    {
        return is_callable($this->htmlCallback);
    }

    public function getHtml(IEntity $data)
    {
        if (is_callable($this->htmlCallback)) {
            return call_user_func($this->htmlCallback, $data);
        }
        return null;
    }

    public function setHtmlTemplate($template, ITemplater $templater, $params = [])
    {
        $this->setHtmlCallback(function(IEntity $entity) use($template, $templater, $params) {
            return $templater->render($template, array_merge($params, ['entity' => $entity, 'decorator' => $this]));
        });
    }

    public function setHtmlCallback(callable $callback)
    {
        $this->htmlCallback = $callback;

        return $this;
    }

    public function setValueCallback(callable $set = null, callable $get = null)
    {
        $this->setCallback = $set;
        $this->getCallback = $get;

        return $this;
    }

    public function setFormFieldCallback(callable $callback)
    {
        $this->formfieldCallback = $callback;
        $this->formField = null;

        return $this;
    }

    public function setUrlCallback(callable $callback)
    {
        $this->urlCallback = $callback;

        return $this;
    }

    /**
     * @return FormField
     */
    protected function buildFormField()
    {
        if ($this->field instanceof FormField) { // relations ?
            $form_field = clone $this->field;
        } elseif ($this->field instanceof Boolean) {
            $form_field = new Checkbox($this->field->getName(), $this->getTitle());
        } elseif ($this->field instanceof Date) {
            $form_field = new FormDataField($this->field->getName(), $this->getTitle());
        } else {
            $form_field = new FormField($this->field->getName(), $this->getTitle());
        }

        return $form_field;
    }

    final public function getFormField()
    {
        if (!$this->formField) {
            $this->formField = $this->buildFormField();

            $this->formField->canBeEmpty($this->field->canBeEmpty());

            if ($this->formfieldCallback) {
                call_user_func($this->formfieldCallback, $this->formField);
            }
        }

        return $this->formField;
    }

    public function getField()
    {
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->field->getName();
    }

    /**
     * Field can be empty
     *
     * @return bool
     */
    public function canBeEmpty()
    {
        return $this->field->canBeEmpty();
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->field->getDescription();
    }

    /**
     * Format value
     *
     * @param IEntity $object
     * @return string
     */
    public function get(IEntity $object)
    {
        $value = $this->field->get($object);

        if ($this->getCallback) {
            $value = call_user_func($this->getCallback, $value);
        }

        return $value;
    }

    /**
     * @param IEntity $object
     * @param $value
     * @return mixed
     */
    public function set(IEntity $object, $value)
    {
        if ($this->setCallback) {
            $old_value = $this->get($object);
            $value = call_user_func($this->setCallback, $value, $old_value, $object);
        }
        return $this->field->set($object, $value);
    }

    /**
     * @return bool
     */
    public function isSurrogate()
    {
        return $this->field->isSurrogate();
    }

    /**
     * @param mixed $value
     * @param string $error Error message if value does not OK
     * @return mixed
     */
    public function validate($value, &$error = null)
    {
        return $this->field->validate($value, $error);
    }
}