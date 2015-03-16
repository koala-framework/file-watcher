<?php
namespace Kwf\FileWatcher\Backend;
use Symfony\Component\EventDispatcher\EventDispatcher;
abstract class BackendAbstract
{
    protected $_paths;
    protected $_eventDispatcher;

    public function setEventDispatcher($v)
    {
        $this->_eventDispatcher = $v;
    }

    public function setPaths($v)
    {
        $this->_paths = $v;
    }

    abstract function start();
    abstract function stop();
    abstract public function isAvailable();
}
