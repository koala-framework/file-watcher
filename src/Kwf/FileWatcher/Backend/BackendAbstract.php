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
    protected $_queueSizeLimit = false;
    protected $_followLinks = false;

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
        return $this;
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
        return $this;
    }

    public function setPaths($v)
    {
        $this->_paths = $v;
        return $this;
    }

    public function setPath($path)
    {
        $this->_paths = array((string)$path);
        return $this;
    }

    public function addPath($path)
    {
        $this->_paths[] = $path;
        return $this;
    }

    public function setExcludePatterns(array $excludePatterns)
    {
        $this->_excludePatterns = $excludePatterns;
        return $this;
    }

    public function setQueueSizeLimit($limit)
    {
        $this->_queueSizeLimit = $limit;
        return $this;
    }

    public function setFollowLinks($v)
    {
        $this->_followLinks = $v;
        return $this;
    }

    abstract function start();
    abstract function stop();
    abstract public function isAvailable();
}
