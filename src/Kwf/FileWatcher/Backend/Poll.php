<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Events;
use Kwf\FileWatcher\Event;
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
            foreach ($files as $file=>$mtime) {
                if (!isset($this->_files[$file])) {
                    $this->_dispatchNewEvent(Events::CREATE, $file);
                } elseif ($this->_files[$file] != $mtime) {
                    $this->_files[$file] = $mtime;
                    $this->_dispatchNewEvent(Events::MODIFY, $file);
                }
            }
            foreach ($this->_files as $file=>$mtime) {
                if (!isset($files[$file])) {
                    unset($this->_files[$file]);
                    $this->_dispatchNewEvent(Events::DELETE, $file);
                }
            }
        }
    }

    private function _dispatchNewEvent($eventName, $filename)
    {
        $event = new Event();
        $event->filename = $filename;
        $this->_eventDispatcher->dispatch($eventName, $event);
    }

    private function _findFiles()
    {
        $finder = new Finder();
        $finder->files();
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
}
