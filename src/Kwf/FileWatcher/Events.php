<?php
namespace Kwf\FileWatcher;
final class Events
{
    const MODIFY = 'filewatcher.modify';
    const DELETE = 'filewatcher.delete';
    const CREATE = 'filewatcher.create';
    const RENAME = 'filewatcher.rename';
}
