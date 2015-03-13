<?php
use Kwf\FileWatcher\Watcher;
use Kwf\FileWatcher\Events;
use Kwf\FileWatcher\Event;
class BasicTest extends PHPUnit_Framework_TestCase
{
    public function testIt()
    {
        $gotEvents = array();
        $watcher = new Watcher(__DIR__);
        $watcher->addListener(Events::MODIFY, function(Event $e) use (&$gotEvents) {
            $gotEvents[] = $e->filename;
        });
        $watcher->start();
        $this->assertEquals($gotEvents, array('foo'));
    }
}
