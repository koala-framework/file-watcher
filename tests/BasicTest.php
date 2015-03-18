<?php
use Kwf\FileWatcher\Event\Modify as ModifyEvent;
use Kwf\FileWatcher\Event\Create as CreateEvent;
use Kwf\FileWatcher\Event\Delete as DeleteEvent;
use Kwf\FileWatcher\Event\Move as MoveEvent;
use Kwf\FileWatcher\Event\QueueFull as QueueFullEvent;
use Kwf\FileWatcher\Backend\Poll as PollBackend;
use Kwf\FileWatcher\Backend\Watchmedo as WatchmedoBackend;
use Kwf\FileWatcher\Backend\Inotifywait as InotifywaitBackend;
use Kwf\FileWatcher\Backend\Inotify as InotifyBackend;

use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Filesystem\Filesystem;

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
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/test');
        $fs->remove(__DIR__.'/test2');
    }

    public function backends()
    {
        return array(
            array(new PollBackend(array())),
            array(new WatchmedoBackend(array())),
            array(new InotifywaitBackend(array())),
            array(new InotifyBackend(array())),
        );
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testModifyContents($backend)
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

        $process->wait();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo.txt'));
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testModifyAttribute($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        sleep(1);
        $f = __DIR__.'/test/foo.txt';
        $php = "<?php sleep(2); chmod('$f', 0755);";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->addListener(ModifyEvent::NAME, function(ModifyEvent $e) use (&$gotEvents, $backend) {
            $gotEvents[] = $e->filename;
            $backend->stop();
        });
        $backend->start();

        $process->wait();
        $this->assertTrue($process->isSuccessful());

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

        $process->wait();
        $this->assertTrue($process->isSuccessful());

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

        $process->wait();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals($gotEvents, array(__DIR__.'/test/foo.txt'));
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testQueueFull($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        sleep(1);
        $f = __DIR__.'/test/';
        $php = "<?php sleep(2); for (\$i=0;\$i<100;\$i++) { file_put_contents('{$f}test'.\$i.'.txt', 'x'); }";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->setQueueSizeLimit(80);
        $backend->addListener(QueueFullEvent::NAME, function(QueueFullEvent $e) use (&$gotEvents, $backend) {
            $gotEvents[] = $e;
            $backend->stop();
        });
        $backend->start();

        $process->wait();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals(count($gotEvents), 1);
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testLinks($backend)
    {
        if (!$backend->isAvailable()) $this->markTestSkipped();
        $f2 = __DIR__.'/test2/';
        mkdir($f2);
        $f = __DIR__.'/test/';
        symlink('../test2/', $f.'l');

        mkdir($f.'d');
        symlink('d', $f.'l2');

        sleep(1);
        $php = "<?php sleep(2); file_put_contents('{$f2}bar.txt', 'x');";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->setFollowLinks(true);
        $backend->addListener(CreateEvent::NAME, function(CreateEvent $e) use (&$gotEvents, $backend) {
            $gotEvents[] = $e->filename;
            $backend->stop();
        });
        $backend->start();

        $process->wait();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals($gotEvents, array(__DIR__.'/test2/bar.txt'));
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testCompressEventsJetbrains($backend)
    {
        // CREATE Controller.php___jb_bak___
        // MODIFY Controller.php___jb_bak___
        // MOVED_FROM Controller.php
        // MOVED_TO Controller.php___jb_old___
        // MOVED_FROM Controller.php___jb_bak___
        // MOVED_TO Controller.php
        // ATTRIB Controller.php
        // DELETE Controller.php___jb_old___

        if (!$backend->isAvailable()) $this->markTestSkipped();
        $f = __DIR__.'/test/';
        sleep(1);
        $php = "<?php sleep(2);
        file_put_contents('{$f}foo.txt___jb_bak___', 'x');
        rename('{$f}foo.txt', '{$f}foo.txt___jb_old___');
        rename('{$f}foo.txt___jb_bak___', '{$f}foo.txt');
        chmod('{$f}foo.txt', 0644);
        unlink('{$f}foo.txt___jb_old___');

        file_put_contents('{$f}stop.txt', 'x');
        ";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->addListener(CreateEvent::NAME, function($e) use (&$gotEvents, $backend) {
            if ($e->filename == __DIR__.'/test/stop.txt') {
                $backend->stop();
            } else {
                $gotEvents[] = 'create';
            }
        });
        $backend->addListener(ModifyEvent::NAME, function($e) use (&$gotEvents, $backend) {
            $gotEvents[] = 'modify';
        });
        $backend->addListener(DeleteEvent::NAME, function($e) use (&$gotEvents, $backend) {
            $gotEvents[] = 'delete';
        });
        $backend->addListener(MoveEvent::NAME, function($e) use (&$gotEvents, $backend) {
            $gotEvents[] = 'move';
        });
        $backend->start();

        $process->wait();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals($gotEvents, array('modify'));
    }

    /**
    * @medium
    * @dataProvider backends
    */
    public function testCompressEventsKate($backend)
    {
        // CREATE web.scssdx1493.new
        // MODIFY web.scssdx1493.new
        // MOVED_FROM web.scssdx1493.new
        // MOVED_TO web.scss

        if (!$backend->isAvailable()) $this->markTestSkipped();
        $f = __DIR__.'/test/';
        sleep(1);
        $php = "<?php sleep(2);
        file_put_contents('{$f}foo.txtdx1493.new', 'x');
        rename('{$f}foo.txtdx1493.new', '{$f}foo.txt');

        file_put_contents('{$f}stop.txt', 'x');
        ";
        $process = new PhpProcess($php);
        $process->start();

        $gotEvents = array();
        $backend->setPath(__DIR__.'/test');
        $backend->addListener(ModifyEvent::NAME, function($e) use (&$gotEvents, $backend) {
            $gotEvents[] = 'modify';
        });
        $backend->addListener(CreateEvent::NAME, function($e) use (&$gotEvents, $backend) {
            if ($e->filename == __DIR__.'/test/stop.txt') {
                $backend->stop();
            } else {
                $gotEvents[] = 'create';
            }
        });
        $backend->addListener(DeleteEvent::NAME, function($e) use (&$gotEvents, $backend) {
            $gotEvents[] = 'delete';
        });
        $backend->addListener(MoveEvent::NAME, function($e) use (&$gotEvents, $backend) {
            $gotEvents[] = 'move';
        });

        $backend->start();

        $process->wait();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals($gotEvents, array('modify'));
    }
}
