<?php
namespace Yireo\BackendReindexer\Plugin;

/**
 * Class MassindexAcl
 */
class MassindexAcl
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(\Magento\Framework\AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * @param \Magento\Indexer\Block\Backend\Grid\ItemsUpdater $subject
     * @param $argument
     *
     * @return mixed
     */
    public function afterUpdate(
        \Magento\Indexer\Block\Backend\Grid\ItemsUpdater $subject,
        $argument
    )
    {
        if ($this->authorization->isAllowed('Yireo_BackendIndexer::reindex') === false) {
            unset($argument['reindex']);
        }

        return $argument;
    }
}