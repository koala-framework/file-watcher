## File Watcher Php Library [![Build Status](https://travis-ci.org/koala-framework/file-watcher.svg?branch=master)](https://travis-ci.org/koala-framework/file-watcher)

Php library for watching for file system changes.

Supports different backends for best cross platform usage.

### Backends

* [watchmedo](https://pythonhosted.org/watchdog/) (Cross platform pything shell utility)
* [inotifywait](http://linux.die.net/man/1/inotifywait) (Linux shell utility)
* Polling fallback (Slow)

### Requirements

* Php 5.3+

### Installation
Install using composer:

    composer require koala-framework/file-watcher

### Example Usage

    $watcher = new Kwf\FileWatcher\Watcher('.');
    $watcher->addListener(Kwf\FileWatcher\Events::MODIFY, function($e) {
        var_dump($e->filename);
    });
    $watcher->start();

### TODO

-  [ ] unit tests for:
    - [ ] create
    - [ ] delete
    - [ ] move
- [ ] auto create best backend
- [ ] no output (no echo, no childprocess output (inotify))
- [ ] error handling when child process fails
- [ ] new backend: inotify php module implementation
- [ ] new backend: some nodejs based implementation
