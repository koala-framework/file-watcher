<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Kwf\FileWatcher\Event\QueueFull as QueueFullEvent;

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

    protected function _dispatchEvent($name, $e)
    {
        $this->_logger->info("$name: ".(isset($e->filename) ? $e->filename : ''));
        $this->_eventDispatcher->dispatch($name, $e);
    }

    protected function _compressEvents($eventsQueue)
    {
        // compress multiple MODIFY events into one:
        // (happens eg. when creating new file (CREATE, ATTRIB, MODIFY)
        $eventsQueue = array_values($eventsQueue);
        foreach ($eventsQueue as $k=>$event) {
            if ($event instanceof ModifyEvent && $k >= 1) {
                if ($eventsQueue[$k-1] instanceof ModifyEvent && $event->filename == $eventsQueue[$k-1]->filename) {
                    unset($eventsQueue[$k-1]);
                }
            }
        }

        // compress the following into one event:
        // CREATE web.scssdx1493.new
        // MODIFY web.scssdx1493.new
        // MOVED  web.scssdx1493.new -> web.scss
        $eventsQueue = array_values($eventsQueue);
        foreach ($eventsQueue as $k=>$event) {
            if ($event instanceof MoveEvent && $k >= 2) {
                $f = $eventsQueue[$k]->destFilename;

                if ($eventsQueue[$k-1] instanceof ModifyEvent
                    && $eventsQueue[$k-2] instanceof CreateEvent
                    && substr($eventsQueue[$k-1]->filename, 0, strlen($f)) == $f
                    && substr($eventsQueue[$k-2]->filename, 0, strlen($f)) == $f
                ) {
                    unset($eventsQueue[$k-1]);
                    unset($eventsQueue[$k-2]);
                    $eventsQueue[$k] = new ModifyEvent($f);
                }
            }
        }

        // compress the following into into one event:
        // CREATE web.scssdx1493.new
        // MODIFY web.scssdx1493.new
        // (or in other order, which can happen
        $eventsQueue = array_values($eventsQueue);
        foreach ($eventsQueue as $k=>$event) {
            if (($event instanceof ModifyEvent || $event instanceof CreateEvent) && $k >= 1) {
                $f = $eventsQueue[$k]->filename;
                if (($eventsQueue[$k-1] instanceof CreateEvent || $eventsQueue[$k-1] instanceof ModifyEvent)
                    && substr($eventsQueue[$k-1]->filename, 0, strlen($f)) == $f
                ) {
                    $eventsQueue[$k] = new CreateEvent($eventsQueue[$k]->filename);
                    unset($eventsQueue[$k-1]);
                }
            }
        }

        // compress the following into into one event:
        // CREATE web.scssdx1493.new
        // MOVED web.scssdx1493.new web.scss
        $eventsQueue = array_values($eventsQueue);
        foreach ($eventsQueue as $k=>$event) {
            if ($event instanceof MoveEvent && $k >= 1) {
                $f = $eventsQueue[$k]->destFilename;
                if ($eventsQueue[$k-1] instanceof CreateEvent
                    && substr($eventsQueue[$k]->filename, 0, strlen($f)) == $f
                    && substr($eventsQueue[$k-1]->filename, 0, strlen($f)) == $f
                ) {
                    unset($eventsQueue[$k-1]);
                    $eventsQueue[$k] = new ModifyEvent($f);
                }
            }
        }

        $eventsQueue = array_values($eventsQueue);
        // CREATE Controller.php___jb_bak___
        // MOVED Controller.php Controller.php___jb_old___
        // MOVED Controller.php___jb_bak___ Controller.php
        // MODIFY Controller.php
        // DELETE Controller.php___jb_old___
        foreach ($eventsQueue as $k=>$event) {
            if ($event instanceof DeleteEvent && $k >= 4 && $eventsQueue[$k-1] instanceof ModifyEvent) {
                $f = $eventsQueue[$k-1]->filename;
                if ($eventsQueue[$k-2] instanceof MoveEvent
                    && $eventsQueue[$k-3] instanceof MoveEvent
                    && $eventsQueue[$k-4] instanceof CreateEvent
                    && substr($eventsQueue[$k]->filename, 0, strlen($f)) == $f
                    && substr($eventsQueue[$k-2]->filename, 0, strlen($f)) == $f
                    && substr($eventsQueue[$k-3]->filename, 0, strlen($f)) == $f
                    && substr($eventsQueue[$k-4]->filename, 0, strlen($f)) == $f
                ) {
                    unset($eventsQueue[$k-1]);
                    unset($eventsQueue[$k-2]);
                    unset($eventsQueue[$k-3]);
                    unset($eventsQueue[$k-4]);
                    $eventsQueue[$k] = new ModifyEvent($f);
                }
            }
        }

        return $eventsQueue;
    }

    abstract function start();
    abstract function stop();
    abstract public function isAvailable();
}
