<?php
declare(strict_types=1);

namespace Yireo\BackendReindexer\Controller\Adminhtml\Indexer;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use Throwable;

/**
 * Class MassReindex
 *
 * @package Yireo\BackendReindexer\Controller\Adminhtml\Indexer
 */
class MassReindex extends \Magento\Backend\App\Action
{
    /**
     * ACL resource
     */
    const ADMIN_RESOURCE = 'Yireo_BackendIndexer::reindex';

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * MassReindex constructor.
     * @param IndexerRegistry $indexerRegistry
     * @param Context $context
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        Context $context
    ) {
        $this->indexerRegistry = $indexerRegistry;
        return parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $indexerIds = $this->getRequest()->getParam('indexer_ids');
        $this->reindexAll($indexerIds);

        $this->_redirect('indexer/indexer/list');
    }

    /**
     * @param integer[] $indexerIds
     *
     * @return bool
     */
    protected function reindexAll(array $indexerIds): bool
    {
        if (!is_array($indexerIds)) {
            $this->messageManager->addErrorMessage(__('Please select one or two indices.'));
            return false;
        }

        foreach ($indexerIds as $indexerId) {
            $this->reindex($indexerId);
        }

        return true;
    }

    /**
     * @param string $indexerId
     * @throws Throwable
     */
    protected function reindex(string $indexerId)
    {
        $startTime = microtime(true);

        try {
            /** @var Indexer $indexer */
            $indexer = $this->indexerRegistry->get($indexerId);
            $indexer->reindexAll();
            $totalTime = microtime(true) - $startTime;
            $totalTime = round($totalTime, 2);

            $message = sprintf(__('%s was reindexed in %s seconds'), $indexer->getTitle(), $totalTime);
            $this->messageManager->addSuccessMessage($message);

        } catch (LocalizedException $e) {
            $message = sprintf(__('%s indexer process exception'), $indexer->getTitle());
            $this->messageManager->addErrorMessage($message, $e);

        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
