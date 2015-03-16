<?php
namespace Kwf\FileWatcher;
use Kwf\FileWatcher\Events;
use Kwf\FileWatcher\Event;
use Kwf\FileWatcher\Backend\BackendAbstract;
use Kwf\FileWatcher\Backend as Backend;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log;
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
            $backends = array(
                new Backend\Inotifywait(),
                new Backend\Watchmedo(),
                new Backend\Poll(),
            );
            foreach ($backends as $b) {
                if ($b->isAvailable()) {
                    $backend = $b;
                    break;
                }
            }
        }
        $this->_backend = $backend;
    }

    public function addListener($name, $callback, $priority = 0)
    {
        return $this->_eventDispatcher->addListener($name, $callback, $priority);
    }

    public function getBackend()
    {
        return $this->_backend;
    }

    public function setLogger(Log\LoggerInterface $logger)
    {
        $this->_backend->setLogger($logger);
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
