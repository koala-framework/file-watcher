<?php
namespace Kwf\FileWatcher\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

abstract class AbstractEvent extends SymfonyEvent
{
    public $filename;
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public static function getEventName()
    {
        return static::NAME;
    }
}
