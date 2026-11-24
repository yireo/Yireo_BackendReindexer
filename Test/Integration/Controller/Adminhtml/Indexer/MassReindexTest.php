<?php
declare(strict_types=1);

namespace Yireo\BackendReindexer\Test\Integration\Controller\Adminhtml\Indexer;

use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Escaper;
use Magento\Framework\Indexer\ConfigInterface as IndexerConfig;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Message\MessageInterface;
use Magento\Indexer\Block\Backend\Grid\ItemsUpdater;
use Magento\TestFramework\TestCase\AbstractBackendController;
use RuntimeException;

/**
 * @magentoAppArea adminhtml
 */
class MassReindexTest extends AbstractBackendController
{
    private const URI = 'backend/reindexer/indexer/massReindex';

    protected $resource = 'Yireo_BackendReindexer::reindex';

    protected $uri = self::URI;

    protected $httpMethod = HttpRequest::METHOD_POST;

    protected function tearDown(): void
    {
        $this->_objectManager->removeSharedInstance(IndexerRegistry::class);
        $this->_objectManager->get(AclBuilder::class)->resetRuntimeAcl();
        parent::tearDown();
    }

    public function testGetRequestIsRejected(): void
    {
        $this->getHttpRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->getHttpRequest()->setParam('indexer_ids', ['design_config_grid']);
        $this->dispatch(self::URI);

        $this->assertSame('noroute', $this->getHttpRequest()->getControllerName());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
    }

    public function testPostWithInvalidFormKeyIsRejected(): void
    {
        $this->getHttpRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getHttpRequest()->setPostValue(['form_key' => 'invalid', 'indexer_ids' => ['design_config_grid']]);
        $this->dispatch(self::URI);

        $this->assertRedirect();
        $this->assertSessionMessages(
            $this->logicalNot($this->stringContains('was reindexed')),
            MessageInterface::TYPE_SUCCESS
        );
    }

    public function testAccessDeniedWithoutIndexManagementResource(): void
    {
        $acl = $this->_objectManager->get(AclBuilder::class)->getAcl();
        $roles = $this->getAdminRoles();
        $acl->deny($roles, 'Magento_Indexer::index');
        $acl->allow($roles, $this->resource);

        $this->postIndexerIds(['design_config_grid']);

        $this->assertSame(403, $this->getHttpResponse()->getHttpResponseCode());
    }

    /**
     * @dataProvider getInvalidIndexerIds
     */
    public function testInvalidIndexerIdsShowError(mixed $indexerIds): void
    {
        $this->postIndexerIds($indexerIds);

        $this->assertRedirect($this->stringContains('indexer/indexer/list'));
        $this->assertSessionMessages(
            $this->equalTo([(string)__('Please select one or more indexers.')]),
            MessageInterface::TYPE_ERROR
        );
    }

    public static function getInvalidIndexerIds(): array
    {
        return [
            'missing' => [null],
            'string' => ['design_config_grid'],
            'nested array' => [[['design_config_grid']]],
            'unknown indexer' => [['unknown_indexer']],
            'empty string' => [['']],
        ];
    }

    public function testTooManyIndexersAreRefused(): void
    {
        $indexerIds = array_map('strval', array_keys($this->_objectManager->get(IndexerConfig::class)->getIndexers()));
        if (count($indexerIds) < 6) {
            $this->markTestSkipped('At least 6 indexers are needed for this test');
        }

        $this->postIndexerIds(array_slice($indexerIds, 0, 6));

        $this->assertSessionMessages(
            $this->equalTo([(string)__('Please select at most %1 indexers at once.', 5)]),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
    }

    public function testValidIndexerIsReindexed(): void
    {
        $this->postIndexerIds(['design_config_grid', 'design_config_grid', 'unknown_indexer']);

        $this->assertRedirect($this->stringContains('indexer/indexer/list'));
        $this->assertSessionMessages(
            $this->callback(
                fn (array $messages) => count($messages) === 1
                    && preg_match('/^.+ was reindexed in [0-9.]+ seconds$/', (string)$messages[0]) === 1
            ),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
    }

    public function testFailingReindexShowsGenericError(): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->method('reindexAll')->willThrowException(new RuntimeException('Internal SQL details'));
        $indexerRegistry = $this->createMock(IndexerRegistry::class);
        $indexerRegistry->method('get')->willReturn($indexer);
        $this->_objectManager->addSharedInstance($indexerRegistry, IndexerRegistry::class);

        $this->postIndexerIds(['design_config_grid']);

        $this->assertSessionMessages(
            $this->equalTo([
                $this->_objectManager->get(Escaper::class)->escapeHtml(
                    (string)__('Reindexing "%1" failed. Check the logs for details.', 'design_config_grid')
                ),
            ]),
            MessageInterface::TYPE_ERROR
        );
    }

    public function testMassActionOptionIsShownWithAclResource(): void
    {
        $result = $this->_objectManager->get(ItemsUpdater::class)->update(['reindex' => ['label' => 'Reindex']]);

        $this->assertArrayHasKey('reindex', $result);
    }

    public function testMassActionOptionIsRemovedWithoutAclResource(): void
    {
        $acl = $this->_objectManager->get(AclBuilder::class)->getAcl();
        $acl->deny($this->getAdminRoles(), $this->resource);

        $result = $this->_objectManager->get(ItemsUpdater::class)->update(['reindex' => ['label' => 'Reindex']]);

        $this->assertArrayNotHasKey('reindex', $result);
    }

    private function postIndexerIds(mixed $indexerIds): void
    {
        $this->getHttpRequest()->setMethod(HttpRequest::METHOD_POST);
        if ($indexerIds !== null) {
            $this->getHttpRequest()->setPostValue('indexer_ids', $indexerIds);
        }

        $this->dispatch(self::URI);
    }

    private function getHttpRequest(): HttpRequest
    {
        $request = $this->getRequest();
        $this->assertInstanceOf(HttpRequest::class, $request);

        return $request;
    }

    private function getHttpResponse(): HttpResponse
    {
        $response = $this->getResponse();
        $this->assertInstanceOf(HttpResponse::class, $response);

        return $response;
    }

    private function getAdminRoles(): array
    {
        /** @var \Magento\User\Model\User $user */
        $user = $this->_auth->getUser();

        return $user->getRoles();
    }
}
