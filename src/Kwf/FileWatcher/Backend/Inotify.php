<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Kwf\FileWatcher\Event\QueueFull as QueueFullEvent;
use Kwf\FileWatcher\Helper\Links as LinksHelper;

use Symfony\Component\Finder\Finder;

class Inotify extends BackendAbstract
{
    private $_stopped = false;
    private $_watches;
    private $_gd;
    private $_previousMoveFromFile;

    private function _addWatch($path)
    {
        $wd = inotify_add_watch($this->_fd, $path, IN_MODIFY | IN_ATTRIB | IN_MOVE | IN_CREATE | IN_DELETE | IN_DONT_FOLLOW);
        $this->_watches[$wd] = $path;
    }

    public function start()
    {
        $paths = $this->_paths;
        if ($this->_followLinks) {
            $paths = LinksHelper::followLinks($paths, $this->_excludePatterns);
        }


        $this->_fd = inotify_init();

        $finder = new Finder();
        $finder->directories();
        foreach ($this->_excludePatterns as $excludePattern) {
            $finder->notName($excludePattern);
        }

        foreach ($paths as $p) {
            $finder->in($p);
        }

        $this->_watches = array();
        foreach ($paths as $p) {
            $this->_addWatch($p);
        }
        foreach ($finder as $f) {
            $this->_addWatch($f->__toString());
        }

        $this->_logger->info("Watches set up...");


        $read = array($this->_fd);
        $write = null;
        $except = null;
        stream_select($read, $write, $except, 0);
        stream_set_blocking($this->_fd, 0);
        $events = array();
        while (!$this->_stopped) {
            while ($inotifyEvents = inotify_read($this->_fd)) {
                foreach ($inotifyEvents as $details) {
                    $file = $this->_watches[$details['wd']];
                    if ($details['name']) $file .= '/'.$details['name'];
                    if ($details['mask'] & IN_MODIFY || $details['mask'] & IN_ATTRIB) {
                        $events[] = new ModifyEvent($file);
                    }
                    if ($details['mask'] & IN_CREATE) {
                        $events[] = new CreateEvent($file);
                    }
                    if ($details['mask'] & IN_DELETE) {
                        $events[] = new DeleteEvent($file);
                    }
                    if ($details['mask'] & IN_MOVED_FROM) {
                        $this->_previousMoveFromFile = $file;
                    }
                    if ($details['mask'] & IN_MOVED_TO) {
                        if (!isset($this->_previousMoveFromFile)) {
                            $this->_logger->error('MOVED_FROM event is not followed by a MOVED_TO');
                        } else {
                            $events[] = new MoveEvent($this->_previousMoveFromFile, $file);
                            unset($this->_previousMoveFromFile);
                        }
                    }
                    if ($details['mask'] & IN_DELETE_SELF) {
                        unset($this->_watches[$details['wd']]);
                    }
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
            usleep(100*1000);
        }
        foreach ($this->_watches as $wd=>$path) {
            inotify_rm_watch($this->_fd, (int)$wd);
        }
        fclose($this->_fd);
        return;
    }

    public function stop()
    {
        $this->_stopped = true;
    }

    public function isAvailable()
    {
        return function_exists('inotify_init');
    }

}
