<?php
namespace Kwf\FileWatcher;
use Kwf\FileWatcher\Backend as Backend;

class Watcher
{
    /**
     * Creates instance of best watcher backend for your system.
     */
    public static function create($paths)
    {
        $backends = array(
            new Backend\Inotifywait($paths),
            new Backend\Watchmedo($paths),
            new Backend\Inotify($paths),
            new Backend\Poll($paths),
        );
        foreach ($backends as $b) {
            if ($b->isAvailable()) {
                $backend = $b;
                break;
            }
        }
        return $backend;
    }
}
