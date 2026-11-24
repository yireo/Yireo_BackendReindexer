<?php
declare(strict_types=1);

namespace Yireo\BackendReindexer\Plugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Indexer\Block\Backend\Grid\ItemsUpdater;
use Yireo\BackendReindexer\Controller\Adminhtml\Indexer\MassReindex;

class MassindexAcl
{
    public function __construct(
        private readonly AuthorizationInterface $authorization
    ) {
    }

    public function afterUpdate(ItemsUpdater $subject, array $argument): array
    {
        if ($this->authorization->isAllowed(MassReindex::ADMIN_RESOURCE) === false) {
            unset($argument['reindex']);
        }

        return $argument;
    }
}
