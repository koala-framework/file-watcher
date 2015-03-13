<?php
namespace Kwf\FileWatcher;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class Event extends SymfonyEvent
{
    public $filename;
}
