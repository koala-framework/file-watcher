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

    public function __construct($paths, BackendAbstract $backend = null)
    {
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
        $event = new Event();
        $event->filename = 'foo';
        $this->_eventDispatcher->dispatch(Events::MODIFY, $event);
        //$this->_backend->start();
    }

    public function stop()
    {
        //$this->_backend->stop();
    }
}
