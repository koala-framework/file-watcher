<?php
namespace Kwf\FileWatcher;
use Kwf\FileWatcher\Events;
use Kwf\FileWatcher\Event;
use Kwf\FileWatcher\Backend\BackendAbstract;
use Kwf\FileWatcher\Backend\Poll as PollBackend;
use Symfony\Component\EventDispatcher\EventDispatcher;
class Watcher
{
    private $_eventDispatcher;
    private $_backend;
    private $_paths;

    public function __construct($paths, BackendAbstract $backend = null)
    {
        if (is_string($paths)) $paths = array($paths);
        $this->_paths = $paths;
        $this->_eventDispatcher = new EventDispatcher();

        if (!$backend) {
            $backend = new PollBackend();
        }
        $this->_backend = $backend;
    }

    public function addListener($name, $callback, $priority = 0)
    {
        return $this->_eventDispatcher->addListener($name, $callback, $priority);
    }

    public function start()
    {
        $this->_backend->setPaths($this->_paths);
        $this->_backend->setEventDispatcher($this->_eventDispatcher);
        $this->_backend->start();
    }

    public function stop()
    {
        $this->_backend->stop();
    }
}
