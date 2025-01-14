<?php

namespace Bow\Mail;

use Bow\Mail\Driver\Native;
use Bow\Mail\Driver\Smtp;
use Bow\Mail\Exception\MailException;
use Bow\View\View;

class Mail
{
    /**
     * The mail driver instance
     *
     * @var SimpleMail|Smtp
     */
    private static $instance;

    /**
     * The mail configuration
     *
     * @var array
     */
    private static $config;

    /**
     * Maxi singleton
     */
    private function __clone()
    {
    }

    /**
     * Mail constructor
     *
     * @param array $config
     * @throws MailException
     */
    public function __construct(array $config = [])
    {
        static::configure($config);
    }

    /**
     * Configure la classe Mail
     *
     * @param  array $config
     * @throws MailException
     * @return SimpleMail|Smtp
     */
    public static function configure($config = [])
    {
        if (empty(static::$config)) {
            static::$config = $config;
        }

        if (!in_array($config['driver'], ["smtp", "mail"])) {
            throw new MailException("The type is not known.", E_USER_ERROR);
        }

        if ($config['driver'] == "mail") {
            if (!static::$instance instanceof Native) {
                static::$instance = new Native($config['mail']);
            }
        } else {
            if (!static::$instance instanceof Smtp) {
                static::$instance = new Smtp($config['smtp']);
            }
        }

        return static::$instance;
    }

    /**
     * Get mail instance
     *
     * @return Smtp|SimpleMail
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * @inheritdoc
     */
    public static function send($view, $bind, \Closure $cb)
    {
        if (is_callable($bind)) {
            $cb = $bind;
            $bind = [];
        }

        $message = new Message();

        $data = View::parse($view, $bind);

        $message->setMessage($data);

        call_user_func_array($cb, [$message]);

        return static::$instance->send($message);
    }

    /**
     * Send mail similar to the PHP mail function
     *
     * @param  string|array $to
     * @param  string       $subject
     * @param  string       $data
     * @param  array        $headers
     * @return mixed
     */
    public static function raw($to, $subject, $data, array $headers = [])
    {
        if (!is_array($to)) {
            $to = [$to];
        }

        $message = new Message();

        $message->toList($to)->subject($subject)->setMessage($data);

        foreach ($headers as $key => $value) {
            $message->addHeader($key, $value);
        }

        return static::$instance->send($message);
    }

    /**
     * Modify the smtp|mail driver
     *
     * @param string $driver
     * @return SimpleMail|Smtp
     * @throws MailException
     */
    public static function setDriver($driver)
    {
        if (static::$config == null) {
            throw new MailException('Mail non configurer.');
        }

        static::$config['driver'] = $driver;
        return static::configure(static::$config);
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     *
     * @throws \ErrorException
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new \ErrorException('This function does not exist. [' . $name . ']', E_ERROR);
    }
}
