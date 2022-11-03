<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Storefront\Cache;

use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class CacheStoreDecorator implements StoreInterface
{
    /**
     * @var StoreInterface
     */
    private $innerCacheStore;

    /**
     * @var ContentEditorEnvironmentInterface
     */
    private $contentEditorEnvironment;

    public function __construct(
        StoreInterface $innerCacheStore,
        ContentEditorEnvironmentInterface $contentEditorEnvironment
    ) {
        $this->innerCacheStore = $innerCacheStore;
        $this->contentEditorEnvironment = $contentEditorEnvironment;
    }

    public function lookup(Request $request)
    {
        if ($this->contentEditorEnvironment->editorOpened()) {
            return null;
        }

        return $this->innerCacheStore->lookup($request);
    }

    /**
     * @throws \Exception
     */
    public function write(Request $request, Response $response)
    {
        if ($this->contentEditorEnvironment->editorOpened()) {
            return 'ropi_frontend_editing_editor_opened_' . bin2hex(random_bytes(32));
        }

        return $this->innerCacheStore->write($request, $response);
    }

    public function invalidate(Request $request)
    {
        return $this->innerCacheStore->invalidate($request);
    }

    public function lock(Request $request)
    {
        return $this->innerCacheStore->lock($request);
    }

    public function unlock(Request $request)
    {
        return $this->innerCacheStore->unlock($request);
    }

    public function isLocked(Request $request)
    {
        return $this->innerCacheStore->isLocked($request);
    }

    public function purge($url)
    {
        return $this->innerCacheStore->purge($url);
    }

    public function cleanup()
    {
        return $this->innerCacheStore->cleanup();
    }
}
