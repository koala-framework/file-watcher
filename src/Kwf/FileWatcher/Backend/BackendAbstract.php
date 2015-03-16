<?php
namespace Kwf\FileWatcher\Backend;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log;
abstract class BackendAbstract
{
    protected $_paths;
    protected $_excludePatterns = array();
    protected $_eventDispatcher;
    protected $_logger;

    public function __construct($paths)
    {
        if (is_string($paths)) $paths = array($paths);
        $this->_paths = $paths;

        $this->_logger = new Log\NullLogger();
        $this->_eventDispatcher = new EventDispatcher();
    }

    public function setEventDispatcher($v)
    {
        $this->_eventDispatcher = $v;
    }

    public function getEventDispatcher()
    {
        return $this->_eventDispatcher;
    }

    public function addListener($name, $callback, $priority = 0)
    {
        $this->_eventDispatcher->addListener($name, $callback, $priority);
        return $this;
    }

    public function setLogger(Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function setPaths($v)
    {
        $this->_paths = $v;
    }

    public function setPath($path)
    {
        $this->_paths = array((string)$path);
    }

    public function addPath($path)
    {
        $this->_paths[] = $path;
    }

    public function setExcludePatterns(array $excludePatterns)
    {
        $this->_excludePatterns = $excludePatterns;
    }

    abstract function start();
    abstract function stop();
    abstract public function isAvailable();
}
