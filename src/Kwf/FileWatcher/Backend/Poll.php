<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
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
                    $this->_eventDispatcher->dispatch(CreateEvent::NAME, new CreateEvent($file));
                } elseif ($this->_files[$file] != $mtime) {
                    $this->_files[$file] = $mtime;
                    $this->_eventDispatcher->dispatch(ModifyEvent::NAME, new ModifyEvent($file));
                }
            }
            foreach ($this->_files as $file=>$mtime) {
                if (!isset($files[$file])) {
                    unset($this->_files[$file]);
                    $this->_eventDispatcher->dispatch(DeleteEvent::NAME, new DeleteEvent($file));
                }
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
