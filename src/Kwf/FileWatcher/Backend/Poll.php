<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Kwf\FileWatcher\Event\QueueFull as QueueFullEvent;
use Symfony\Component\Finder\Finder;

class Poll extends BackendAbstract
{
    private $_stopped = false;
    private $_files;

    public function start()
    {
        $rounds = 0;
        $this->_files = $this->_findFiles();
        while (!$this->_stopped) {
            sleep(1);
            $files = $this->_findFiles();
            $events = array();
            foreach ($files as $file=>$mtime) {
                if (!isset($this->_files[$file])) {
                    $events[] = new CreateEvent($file);
                } elseif ($this->_files[$file] != $mtime) {
                    $this->_files[$file] = $mtime;
                    $events[] = new ModifyEvent($file);
                }
            }
            foreach ($this->_files as $file=>$mtime) {
                if (!isset($files[$file])) {
                    unset($this->_files[$file]);
                    $events[] = new DeleteEvent($file);
                }
            }

            if ($this->_queueSizeLimit && count($events) > $this->_queueSizeLimit) {
                $this->_eventDispatcher->dispatch(QueueFullEvent::NAME, new QueueFullEvent());
                $events = array();
            }

            foreach ($events as $event) {
                $name = call_user_func(array(get_class($event), 'getEventName'));
                $this->_eventDispatcher->dispatch($name, $event);
            }
        }
    }

    private function _findFiles()
    {
        $finder = new Finder();
        $finder->files();
        foreach ($this->_excludePatterns as $excludePattern) {
            $finder->notName($excludePattern);
        }
        foreach ($this->_paths as $p) {
            $finder->in($p);
        }
        $files = array();
        foreach ($finder as $f) {
            $files[$f->getRealpath()] = $f->getMTime();
        }
        return $files;
    }

    public function stop()
    {
        $this->_stopped = true;
    }

    public function isAvailable()
    {
        return true;
    }
}
