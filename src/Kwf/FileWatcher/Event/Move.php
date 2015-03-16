<?php
namespace Kwf\FileWatcher\Event;
class Move extends AbstractEvent
{
    const NAME = 'filewatcher.move';

    public $destFilename;

    public function __construct($filename, $destFilename)
    {
        parent::__construct($filename);
        $this->destFilename = $destFilename;
    }
}
