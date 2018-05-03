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

/**
 * Resource cache implementation using memory.
 *
 * @author Victor Puertas <vpgugr@gmail.com>
 */
class ResourceCacheMemory implements ResourceCacheInterface
{
    protected $isInitialized = false;
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function isInitialized()
    {
        return $this->isInitialized;
    }

    /**
     * {@inheritdoc}
     */
    public function read($filename)
    {
        return isset($this->data[$filename]) ? $this->data[$filename] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($filename, $hash)
    {
        $this->data[$filename] = $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($filename)
    {
        unset($this->data[$filename]);
    }

    /**
     * {@inheritdoc}
     */
    public function erase()
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $this->isInitialized = true;
    }
}
