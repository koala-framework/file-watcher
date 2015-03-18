## File Watcher Php Library [![Build Status](https://travis-ci.org/koala-framework/file-watcher.svg?branch=master)](https://travis-ci.org/koala-framework/file-watcher)

Php library for watching for file system changes.

Supports different backends for best cross platform usage.

### Backends

* [watchmedo](https://pythonhosted.org/watchdog/) (Cross platform pything shell utility)
* [inotifywait](http://linux.die.net/man/1/inotifywait) (Linux shell utility)
* [inotify](http://php.net/manual/en/book.inotify.php) (Php PECL extension)
* Polling fallback (Slow)

### Requirements

* Php 5.3+

### Installation
Install using composer:

    composer require koala-framework/file-watcher

### Example Usage

    $watcher = Kwf\FileWatcher\Watcher::create('.');
    $watcher->addListener(Kwf\FileWatcher\Events::MODIFY, function($e) {
        var_dump($e->filename);
    });
    $watcher->start();

### TODO

- [ ] error handling when child process fails
- [ ] new backend: some nodejs based implementation
