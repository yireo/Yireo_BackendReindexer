<?php
declare(strict_types=1);

namespace Yireo\BackendReindexer\Plugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Indexer\Block\Backend\Grid\ItemsUpdater;

/**
 * Class MassindexAcl
 */
class MassindexAcl
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * @param ItemsUpdater $subject
     * @param $argument
     *
     * @return mixed
     */
    public function afterUpdate(
        ItemsUpdater $subject,
        $argument
    ) {
        if ($this->authorization->isAllowed('Yireo_BackendIndexer::reindex') === false) {
            unset($argument['reindex']);
        }

        return $argument;
    }
}
