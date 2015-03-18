<?php
namespace Kwf\FileWatcher\Backend;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Kwf\FileWatcher\Helper\Links as LinksHelper;

class Inotifywait extends ChildProcessAbstract
{
    private $_previousMoveFromFile;

    public function isAvailable()
    {
        $str = exec("inotifywait --help 2>&1", $out, $ret);
        if ($ret > 1 || substr($out[0], 0, 12) != 'inotifywait ') {
            return false;
        }
        return true;
    }

    protected function _getCmd()
    {
        $excludeRegEx = array();
        foreach ($this->_excludePatterns as $e) {
            if (substr($e, -1) == '*') $e = substr($e, 0, -1); //not needed
            $excludeRegEx[] = str_replace(
                array(
                    '.',
                    '*',
                ),
                array(
                    '\\.',
                    '.*'
                ),
                $e);
        }
        $excludeRegEx = implode('|', $excludeRegEx);
        $cmd = "inotifywait -e modify -e create -e delete -e move -e moved_to -e moved_from -e attrib -r --monitor ";
        if ($excludeRegEx) $cmd .= "--exclude '$excludeRegEx' ";

        $paths = $this->_paths;
        if ($this->_followLinks) {
            //inotifywait doesn't recurse into symlinks
            //so we add all symlinks to $paths
            $paths = LinksHelper::followLinks($paths, $this->_excludePatterns);
        }

        $cmd .= implode(' ', $paths);
        return $cmd;
    }

    protected function _getEventFromLine($line)
    {
        if (!preg_match('#^([^ ]+) ([A-Z,_]+) ?([^ ]+)?$#', trim($line), $m)) {
            $this->_logger->error("unknown event: $line");
            return null;
        }
        $ev = $m[2];
        $file = $m[1].(isset($m[3]) ? $m[3] : '');

        $prevMoveFile = $this->_previousMoveFromFile;
        $this->_previousMoveFromFile = null;

        if ($ev == 'MODIFY' || $ev == 'ATTRIB' || $ev == 'ATTRIB,ISDIR') {
            return new ModifyEvent($file);
        } else if ($ev == 'CREATE' || $ev == 'CREATE,ISDIR') {
            return new CreateEvent($file);
        } else if ($ev == 'DELETE' || $ev == 'DELETE,ISDIR') {
            return new DeleteEvent($file);
        } else if ($ev == 'MOVED_FROM' || $ev == 'MOVED_FROM,ISDIR') {
            $this->_previousMoveFromFile = $file;
            return null;
        } else if ($ev == 'MOVED_TO' || $ev == 'MOVED_TO,ISDIR') {
            if (!$prevMoveFile) {
                throw new \Exception('MOVED_FROM event is not followed by a MOVED_TO');
            }
            return new MoveEvent($prevMoveFile, $file);
        }
    }
}
