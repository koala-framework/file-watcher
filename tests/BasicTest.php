<?php
use Kwf\FileWatcher\Watcher;
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Backend\Poll as PollBackend;
use Kwf\FileWatcher\Backend\Watchmedo as WatchmedoBackend;
use Kwf\FileWatcher\Backend\Inotifywait as InotifywaitBackend;
use Symfony\Component\Process\PhpProcess;

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

    /**
    * @medium
    */
    public function testPoll()
    {
        $this->_testBackend(new PollBackend());
    }

    /**
    * @medium
    */
    public function testWatchmedo()
    {
        $this->_testBackend(new WatchmedoBackend());
    }

    /**
    * @medium
    */
    public function testInotifywait()
    {
        $this->_testBackend(new InotifywaitBackend());
    }

    private function _testBackend($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        sleep(1);
        $f = __DIR__.'/test/foo.txt';
        $php = "<?php sleep(2); file_put_contents('$f', 'x');";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $watcher = new Watcher(__DIR__.'/test', $backend);
        $watcher->addListener(ModifyEvent::NAME, function(ModifyEvent $e) use (&$gotEvents, $watcher) {
            $gotEvents[] = $e->filename;
            $watcher->stop();
        });
        $watcher->start();
        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo.txt'));
    }
}
