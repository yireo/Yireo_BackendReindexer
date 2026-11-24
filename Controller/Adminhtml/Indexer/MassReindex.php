<?php
declare(strict_types=1);

namespace Yireo\BackendReindexer\Controller\Adminhtml\Indexer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Indexer\ConfigInterface as IndexerConfig;
use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

class MassReindex extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Yireo_BackendReindexer::reindex';

    public function __construct(
        Context $context,
        private readonly IndexerRegistry $indexerRegistry,
        private readonly IndexerConfig $indexerConfig,
        private readonly LoggerInterface $logger,
        private readonly int $maxIndexersPerRequest = 5
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $indexerIds = $this->getValidIndexerIds($this->getRequest()->getParam('indexer_ids'));

        if (empty($indexerIds)) {
            $this->messageManager->addErrorMessage((string)__('Please select one or more indexers.'));
        } elseif (count($indexerIds) > $this->maxIndexersPerRequest) {
            $this->messageManager->addErrorMessage(
                (string)__('Please select at most %1 indexers at once.', $this->maxIndexersPerRequest)
            );
        } else {
            foreach ($indexerIds as $indexerId) {
                $this->reindex($indexerId);
            }
        }

        return $this->resultRedirectFactory->create()->setPath('indexer/indexer/list');
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function getValidIndexerIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $knownIndexerIds = array_map('strval', array_keys($this->indexerConfig->getIndexers()));
        $indexerIds = array_filter(
            $value,
            fn ($indexerId) => is_string($indexerId) && in_array($indexerId, $knownIndexerIds, true)
        );

        return array_values(array_unique($indexerIds));
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Indexer::index')
            && $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    private function reindex(string $indexerId): void
    {
        $startTime = microtime(true);

        try {
            $indexer = $this->indexerRegistry->get($indexerId);
            $indexer->reindexAll();
            $totalTime = round(microtime(true) - $startTime, 2);
            $this->messageManager->addSuccessMessage(
                (string)__('%1 was reindexed in %2 seconds', $indexer->getTitle(), $totalTime)
            );
        } catch (Throwable $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
            $this->messageManager->addErrorMessage(
                (string)__('Reindexing "%1" failed. Check the logs for details.', $indexerId)
            );
        }
    }
}
