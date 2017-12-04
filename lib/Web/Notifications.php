<?php
namespace project5\Web;

use project5\Session;

class Notifications {

    const SESSION_NAMESPACE = 'notifications';

    private $session;
    private $id;

    public function __construct(Session $session, $id = null)
    {
        $this->session = $session;
        $this->id = $id ? (string)$id : '';
    }

    public function addError($error, $arguments = [])
    {
        $errors = $this->session->get(self::SESSION_NAMESPACE.$this->id, 'errors');

        $errors[] = [$error, $arguments];

        $this->session->set(self::SESSION_NAMESPACE.$this->id, 'errors', $errors);
    }

    public function hasErrors()
    {
        $errors = $this->session->get(self::SESSION_NAMESPACE.$this->id, 'errors');

        return !empty($errors);
    }

    public function getErrors()
    {
        $errors = $this->session->get(self::SESSION_NAMESPACE.$this->id, 'errors');

        $this->session->set(self::SESSION_NAMESPACE.$this->id, 'errors', []);

        return $errors;
    }

    public function addMessage($message, $arguments = [])
    {
        $messages = $this->session->get(self::SESSION_NAMESPACE.$this->id, 'messages');

        $messages[] = [$message, $arguments];

        $this->session->set(self::SESSION_NAMESPACE.$this->id, 'messages', $messages);
    }

    public function hasMessages()
    {
        $messages = $this->session->get(self::SESSION_NAMESPACE.$this->id, 'messages');

        return !empty($messages);
    }

    public function getMessages()
    {
        $messages = $this->session->get(self::SESSION_NAMESPACE.$this->id, 'messages');

        $this->session->set(self::SESSION_NAMESPACE.$this->id, 'messages', []);

        return $messages;
    }
}