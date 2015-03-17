<?php
namespace Kwf\FileWatcher\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class QueueFull extends SymfonyEvent
{
    const NAME = 'filewatcher.queue_full';

    public $eventsQueue;

    public function __construct($events)
    {
        $this->eventsQueue = $events;
    }
}
