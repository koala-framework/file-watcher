<?php
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
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

    public function backends()
    {
        return array(
            array(new PollBackend(array())),
            array(new WatchmedoBackend(array())),
            array(new InotifywaitBackend(array())),
        );
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testModify($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        sleep(1);
        $f = __DIR__.'/test/foo.txt';
        $php = "<?php sleep(2); file_put_contents('$f', 'x');";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->addListener(ModifyEvent::NAME, function(ModifyEvent $e) use (&$gotEvents, $backend) {
            $gotEvents[] = $e->filename;
            $backend->stop();
        });
        $backend->start();
        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo.txt'));
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testCreate($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        sleep(1);
        $f = __DIR__.'/test/foo2.txt';
        $php = "<?php sleep(2); file_put_contents('$f', 'x');";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->addListener(CreateEvent::NAME, function(CreateEvent $e) use (&$gotEvents, $backend) {
            $gotEvents[] = $e->filename;
            $backend->stop();
        });
        $backend->start();
        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo2.txt'));
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testDelete($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        sleep(1);
        $f = __DIR__.'/test/foo.txt';
        $php = "<?php sleep(2); unlink('$f');";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->addListener(DeleteEvent::NAME, function(DeleteEvent $e) use (&$gotEvents, $backend) {
            $gotEvents[] = $e->filename;
            $backend->stop();
        });
        $backend->start();
        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo.txt'));
    }
}
