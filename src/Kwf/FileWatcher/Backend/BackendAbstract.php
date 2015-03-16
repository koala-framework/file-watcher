<?php
namespace Kwf\FileWatcher\Backend;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log;
abstract class BackendAbstract
{
    protected $_paths;
    protected $_eventDispatcher;
    protected $_logger;

    public function __construct()
    {
        $this->_logger = new Log\NullLogger();
    }

    public function setEventDispatcher($v)
    {
        $this->_eventDispatcher = $v;
    }

    public function setPaths($v)
    {
        $this->_paths = $v;
    }

    public function setLogger(Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    abstract function start();
    abstract function stop();
    abstract public function isAvailable();
}
