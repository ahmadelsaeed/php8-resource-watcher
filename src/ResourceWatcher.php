<?php

/*
 * This file is part of the Yo! Symfony Resource Watcher.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yosymfony\ResourceWatcher;

use Symfony\Component\Finder\Finder;

/**
 * A simple resource-watcher to discover changes in the filesystem.
 * This component uses Symfony Finder to set the file search criteria.
 *
 * @author Victor Puertas <vpgugr@gmail.com>
 */
class ResourceWatcher
{
    private $cache;
    private $finder;
    private $contentHash;
    private $fileHashesFromFinder = [];
    private $newFiles = [];
    private $deletedFiles = [];
    private $updatedFiles = [];

    /**
     * Constructor.
     *
     * @param ResourceCacheInterface $resourceCache The cache.
     * @param Finder $finder The Symfony Finder.
     * @param ContentHashInterface $contentHash The file hash strategy.
     */
    public function __construct(ResourceCacheInterface $resourceCache, Finder $finder, ContentHashInterface $contentHash)
    {
        $this->cache = $resourceCache;
        $this->finder = $finder;
        $this->contentHash = $contentHash;
    }

    /**
     * Initializes the resource watcher.
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->cache->isInitialized() == false) {
            $this->findChanges();
        }
    }

    /**
     * Finds all changes in the filesystem according to the finder criteria.
     *
     * @return ResourceWatcherResult
     */
    public function findChanges()
    {
        $this->reset();

        if ($this->cache->isInitialized() == false) {
            $this->warmUpCache();
        } else {
            $this->findChangesAgainstCache();
        }

        $this->cache->save();

        return new ResourceWatcherResult($this->newFiles, $this->deletedFiles, $this->updatedFiles);
    }

    /**
     * Rebuilds the resource cache
     *
     * @return void
     */
    public function rebuild()
    {
        $this->cache->erase();
        $this->reset();
        $this->warmUpCache();
        $this->cache->save();
    }

    /**
     * @return void
     */
    private function reset()
    {
        $this->newFiles = [];
        $this->deletedFiles = [];
        $this->updatedFiles = [];
    }

    /**
     * @return void
     */
    private function warmUpCache()
    {
        foreach ($this->finder as $file) {
            $filePath = $file->getPathname();
            $this->cache->write($filePath, $this->calculateHashOfFile($filePath));
        }
    }

    /**
     * @return void
     */
    private function findChangesAgainstCache()
    {
        $this->calculateHashOfFilesFromFinder();

        $finderFileHashes = $this->fileHashesFromFinder;
        $cacheFileHashes = $this->cache->getAll();

        if (count($finderFileHashes) > count($cacheFileHashes)) {
            foreach ($finderFileHashes as $file => $hash) {
                $this->processFileFromFilesystem($file, $hash);
            }
        } else {
            foreach ($cacheFileHashes as $file => $hash) {
                $this->processFileFromCache($file, $hash);
            }
        }
    }

    /**
     * @param string $file asdfasdf
     * @param string $hash
     *
     * @return void
     */
    private function processFileFromFilesystem($file, $hash)
    {
        $hashFromCache = $this->cache->read($file);

        if ($hashFromCache) {
            if ($hash != $hashFromCache) {
                $this->cache->write($file, $hash);
                $this->updatedFiles[] = $file;
            }
        } else {
            $this->cache->write($file, $hash);
            $this->newFiles[] = $file;
        }
    }

    /**
     * @return void
     */
    private function processFileFromCache($file, $hash)
    {
        $hashFromCache = isset($this->fileHashesFromFinder[$file]) ? $this->fileHashesFromFinder[$file] : null;

        if ($hashFromCache) {
            if ($hashFromCache != $hash) {
                $this->cache->write($file, $hashFromCache);
                $this->updatedFiles[] = $file;
            }
        } else {
            $this->cache->delete($file);
            $this->deletedFiles[] = $file;
        }
    }

    /**
     * @return void
     */
    private function calculateHashOfFilesFromFinder()
    {
        $pathsAndHashes = [];

        foreach ($this->finder as $file) {
            $filePath = $file->getPathname();
            $pathsAndHashes[$filePath] = $this->calculateHashOfFile($filePath);
        }

        $this->fileHashesFromFinder = $pathsAndHashes;
    }

    /**
     * @return string
     */
    private function calculateHashOfFile($filename)
    {
        $fileContent = file_get_contents($filename);

        return $this->contentHash->hash($fileContent);
    }
}
