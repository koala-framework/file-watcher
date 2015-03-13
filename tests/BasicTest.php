<?php
use Kwf\FileWatcher\Watcher;
use Kwf\FileWatcher\Events;
use Kwf\FileWatcher\Event;
use Kwf\FileWatcher\Backend\Poll as PollBackend;
class BasicTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_rmTestFiles();
        mkdir(__DIR__.'/test');
        file_put_contents(__DIR__.'/test/foo.txt', 'a');
    }

    public function tearDown()
    {
        $this->_rmTestFiles();
    }

    private function _rmTestFiles()
    {
        $dirname = __DIR__.'/test';
        if (!file_exists($dirname)) {
            return false;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path ) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getRealPath());
        }
        return rmdir($dirname);
    }

    public function testIt()
    {
        $f = __DIR__.'/test/foo.txt';
        $php = "sleep(1); file_put_contents('$f', 'x');";
        system("php -r ".escapeshellarg($php)." &> /dev/null &");

        $gotEvents = array();
        $watcher = new Watcher(__DIR__.'/test', new PollBackend());
        $watcher->addListener(Events::MODIFY, function(Event $e) use (&$gotEvents, $watcher) {
            $gotEvents[] = $e->filename;
            $watcher->stop();
        });
        $watcher->start();
        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo.txt'));
    }
}
