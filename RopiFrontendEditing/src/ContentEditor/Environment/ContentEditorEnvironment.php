<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Environment;

use Psr\Container\ContainerInterface;
use Ropi\ContentEditor\Environment\AbstractContentEditorEnvironment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class ContentEditorEnvironment extends AbstractContentEditorEnvironment
{

    /**
     * @var EntityRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var bool
     */
    protected $tokenIsValid;

    public function __construct(
        EntityRepositoryInterface $userRepository,
        ContainerInterface $container
    ) {
        $this->userRepository = $userRepository;
        $this->container = $container;
    }

    public function editorOpened(): bool
    {
        return $this->tokenIsValid();
    }

    protected function tokenIsValid(): bool
    {
        if (is_bool($this->tokenIsValid)) {
            return $this->tokenIsValid;
        }

        $token = $this->getToken();
        if ($token) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('customFields.ropi_frontend_editing_token', $token));
            $criteria->setLimit(1);

            $this->tokenIsValid = $this->userRepository->search($criteria, Context::createDefaultContext())->count() > 0;
        } else {
            $this->tokenIsValid = false;
        }

        return $this->tokenIsValid;
    }

    protected function getToken(): ?string
    {
        return $_COOKIE['ropi-frontend-editing-token'] ?? null;
    }
}
