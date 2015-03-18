<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Kwf\FileWatcher\Event\QueueFull as QueueFullEvent;
use Symfony\Component\Process\Process;

abstract class ChildProcessAbstract extends BackendAbstract
{
    protected $_proc;

    abstract protected function _getCmd();
    abstract protected function _getEventFromLine($line);

    public function start()
    {
        $cmd = $this->_getCmd();
        $this->_logger->debug("$cmd");
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
                    $this->_logger->debug($line);
                    $e = $this->_getEventFromLine($line);
                    if ($e) {
                        $events[] = $e;
                    }
                }

                $events = $this->_compressEvents($events);
                if ($this->_queueSizeLimit && count($events) > $this->_queueSizeLimit) {
                    $this->_dispatchEvent(QueueFullEvent::NAME, new QueueFullEvent($events));
                    $events = array();
                }

                foreach ($events as $event) {
                    $name = call_user_func(array(get_class($event), 'getEventName'));
                    $this->_dispatchEvent($name, $event);
                }

                $eventsQueue = array();
            }

            $event = trim($this->_proc->getIncrementalOutput());
            $errOut = $this->_proc->getIncrementalErrorOutput();
            if ($errOut) $this->_logger->notice(rtrim($errOut));;

            if (!$event) {
                usleep($bufferUsecs/2);
                continue;
            }
            $eventsQueue = array_merge($eventsQueue, explode("\n", $event));

            $lastChange = microtime(true);
        }
        $errOut = $this->_proc->getIncrementalErrorOutput();
        if ($errOut) $this->_logger->notice(rtrim($errOut));;
    }

    public function stop()
    {
        $this->_proc->stop();
    }
}
