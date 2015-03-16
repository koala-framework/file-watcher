<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Symfony\Component\Process\Process;

abstract class ChildProcessAbstract extends BackendAbstract
{
    protected $_proc;

    abstract protected function _getCmd();
    abstract protected function _getEventFromLine($line);

    public function start()
    {
        $cmd = $this->_getCmd();
        $this->_proc = new Process($cmd, null, null, null, null, array());
        $this->_proc->start();

        $bufferUsecs = 200*1000;

        $eventsQueue = array();
        $lastChange = false;
        while ($this->_proc->isRunning()) {
            if ($lastChange && $lastChange+($bufferUsecs/1000000) < microtime(true)) {
                $eventsQueue = array_unique($eventsQueue);
                $events = array();
                foreach ($eventsQueue as $k=>$line) {
                    $e = $this->_getEventFromLine($line);
                    if ($e) {
                        $events[]= $e;
                    }
                }

                $events = $this->_compressEvents($events);

                foreach ($events as $event) {
                    $name = call_user_func(array(get_class($event), 'getEventName'));
                    $this->_eventDispatcher->dispatch($name, $event);
                }

                $eventsQueue = array();
            }

            $event = trim($this->_proc->getIncrementalOutput());
            echo $this->_proc->getIncrementalErrorOutput();

            if (!$event) {
                usleep($bufferUsecs/2);
                continue;
            }
            $eventsQueue = array_merge($eventsQueue, explode("\n", $event));

            $lastChange = microtime(true);
        }
        echo $this->_proc->getIncrementalErrorOutput();
    }

    private function _compressEvents($eventsQueue)
    {
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
        // MODIFY web.scssdx1493.new
        // MOVED web.scssdx1493.new web.scss
        $eventsQueue = array_values($eventsQueue);
        foreach ($eventsQueue as $k=>$event) {
            if ($event instanceof MoveEvent && $k >= 1) {
                $f = $eventsQueue[$k]->destFilename;
                if ($eventsQueue[$k-1] instanceof ModifyEvent
                    && substr($eventsQueue[$k]->filename, 0, strlen($f)) == $f
                    && substr($eventsQueue[$k-1]->filename, 0, strlen($f)) == $f
                ) {
                    unset($eventsQueue[$k-1]);
                    $eventsQueue[$k] = new ModifyEvent($f);
                }
            }
        }
        return $eventsQueue;
    }

    public function stop()
    {
        $this->_proc->stop();
    }
}
