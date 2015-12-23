<?php
namespace Kwf\FileWatcher\Helper;
use Symfony\Component\Finder\Finder;

class Links
{
    public static function followLinks(array $paths, $excludePatterns)
    {
        $finder = new Finder();
        $finder->directories();
        foreach ($excludePatterns as $excludePattern) {
            $finder->notPath($excludePattern);
        }
        foreach ($paths as $p) {
            $finder->in($p);
        }
        foreach ($finder as $i) {
            if ($i->isLink()) {
                $realPath = $i->getRealPath();
                foreach ($paths as $k=>$p2) {
                    if (substr($realPath, 0, strlen($p2)+1) == $p2.'/') {
                        continue 2;
                    }
                }
                $paths[] = $realPath;
            }
        }
        return $paths;
    }
}
