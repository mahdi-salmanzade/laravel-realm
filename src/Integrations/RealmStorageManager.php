<?php

namespace Realm\Integrations;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Realm\Context\RealmContext;

/**
 * @mixin FilesystemAdapter
 */
class RealmStorageManager implements Filesystem
{
    public function __construct(
        private readonly Filesystem $inner,
        private readonly RealmContext $context,
    ) {}

    // -------------------------------------------------------
    // Path resolution
    // -------------------------------------------------------

    private function prefix(string $path): string
    {
        if ($this->context->isTenancyDisabled() || $this->context->id() === null) {
            return $path;
        }

        $root = config('realm.storage.root', 'tenants');

        return $root.'/'.$this->context->key().'/'.ltrim($path, '/');
    }

    /**
     * @param  string|array<string>  $paths
     * @return string|array<string>
     */
    private function prefixPaths(string|array $paths): string|array
    {
        if (is_array($paths)) {
            return array_map(fn (string $p) => $this->prefix($p), $paths);
        }

        return $this->prefix($paths);
    }

    private function prefixDirectory(?string $directory): ?string
    {
        if ($directory === null) {
            if ($this->context->isTenancyDisabled() || $this->context->id() === null) {
                return null;
            }

            $root = config('realm.storage.root', 'tenants');

            return $root.'/'.$this->context->key();
        }

        return $this->prefix($directory);
    }

    // -------------------------------------------------------
    // Filesystem interface
    // -------------------------------------------------------

    public function path($path)
    {
        return $this->inner->path($this->prefix($path));
    }

    public function exists($path)
    {
        return $this->inner->exists($this->prefix($path));
    }

    public function get($path)
    {
        return $this->inner->get($this->prefix($path));
    }

    public function readStream($path)
    {
        return $this->inner->readStream($this->prefix($path));
    }

    public function put($path, $contents, $options = [])
    {
        return $this->inner->put($this->prefix($path), $contents, $options);
    }

    /** @param File|UploadedFile|string|array<mixed>|null $file */
    public function putFile($path, $file = null, $options = [])
    {
        return $this->inner->putFile($this->prefix($path), $file, $options);
    }

    /**
     * @param  File|UploadedFile|string|array<mixed>|null  $file
     * @param  string|array<mixed>|null  $name
     */
    public function putFileAs($path, $file, $name = null, $options = [])
    {
        return $this->inner->putFileAs($this->prefix($path), $file, $name, $options);
    }

    /** @param array<string, mixed> $options */
    public function writeStream($path, $resource, array $options = [])
    {
        return $this->inner->writeStream($this->prefix($path), $resource, $options);
    }

    public function getVisibility($path)
    {
        return $this->inner->getVisibility($this->prefix($path));
    }

    public function setVisibility($path, $visibility)
    {
        return $this->inner->setVisibility($this->prefix($path), $visibility);
    }

    public function prepend($path, $data)
    {
        return $this->inner->prepend($this->prefix($path), $data);
    }

    public function append($path, $data)
    {
        return $this->inner->append($this->prefix($path), $data);
    }

    /** @param string|array<string> $paths */
    public function delete($paths)
    {
        return $this->inner->delete($this->prefixPaths($paths));
    }

    public function copy($from, $to)
    {
        return $this->inner->copy($this->prefix($from), $this->prefix($to));
    }

    public function move($from, $to)
    {
        return $this->inner->move($this->prefix($from), $this->prefix($to));
    }

    public function size($path)
    {
        return $this->inner->size($this->prefix($path));
    }

    public function lastModified($path)
    {
        return $this->inner->lastModified($this->prefix($path));
    }

    public function files($directory = null, $recursive = false)
    {
        return $this->inner->files($this->prefixDirectory($directory), $recursive);
    }

    public function allFiles($directory = null)
    {
        return $this->inner->allFiles($this->prefixDirectory($directory));
    }

    public function directories($directory = null, $recursive = false)
    {
        return $this->inner->directories($this->prefixDirectory($directory), $recursive);
    }

    public function allDirectories($directory = null)
    {
        return $this->inner->allDirectories($this->prefixDirectory($directory));
    }

    public function makeDirectory($path)
    {
        return $this->inner->makeDirectory($this->prefix($path));
    }

    public function deleteDirectory($directory)
    {
        return $this->inner->deleteDirectory($this->prefix($directory));
    }

    // -------------------------------------------------------
    // Non-interface methods (url, temporaryUrl, etc.)
    // -------------------------------------------------------

    /**
     * @param  array<mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Methods that take a path/file as first argument — prefix it
        $pathMethods = [
            'url', 'temporaryUrl', 'temporaryUploadUrl',
            'checksum', 'mimeType', 'download', 'response',
        ];

        if (in_array($method, $pathMethods, true) && isset($parameters[0]) && is_string($parameters[0])) {
            $parameters[0] = $this->prefix($parameters[0]);
        }

        return $this->inner->$method(...$parameters);
    }
}
