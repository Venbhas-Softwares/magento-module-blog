<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Article;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Blog\Model\Adminhtml\Form\DataNormalizer;
use Venbhas\Blog\Model\Article\CategoryIdsResolver;
use Venbhas\Blog\Model\Article\Source\Status as ArticleStatus;
use Venbhas\Blog\Model\ArticleFactory;
use Venbhas\Blog\Model\ResourceModel\Article as ArticleResource;
use Venbhas\Blog\Model\ResourceModel\Article\CategoryRelation;
use Venbhas\Blog\Model\ResourceModel\Article\RelatedProducts;
use Venbhas\Blog\Ui\DataProvider\Form\Modifier\RelatedProducts as RelatedProductsModifier;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::article_save';

    /** @var ArticleFactory */
    private $articleFactory;

    /** @var ArticleResource */
    private $articleResource;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var CategoryRelation */
    private $categoryRelation;

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var AuthSession */
    private $authSession;

    /** @var CategoryIdsResolver */
    private $categoryIdsResolver;

    /** @var DataNormalizer */
    private $dataNormalizer;

    /** @var string[] Allowed article table columns for setData (author is set from logged-in admin) */
    private const ALLOWED_FIELDS = [
        'article_id', 'title', 'url_key', 'meta_title', 'meta_description', 'meta_keywords',
        'meta_robots', 'description', 'short_description', 'featured_image', 'status',
    ];

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ArticleFactory $articleFactory
     * @param ArticleResource $articleResource
     * @param RelatedProducts $relatedProducts
     * @param CategoryRelation $categoryRelation
     * @param DataPersistorInterface $dataPersistor
     * @param AuthSession $authSession
     * @param CategoryIdsResolver $categoryIdsResolver
     * @param DataNormalizer $dataNormalizer
     */
    public function __construct(
        Context $context,
        ArticleFactory $articleFactory,
        ArticleResource $articleResource,
        RelatedProducts $relatedProducts,
        CategoryRelation $categoryRelation,
        DataPersistorInterface $dataPersistor,
        AuthSession $authSession,
        CategoryIdsResolver $categoryIdsResolver,
        DataNormalizer $dataNormalizer
    ) {
        parent::__construct($context);
        $this->articleFactory = $articleFactory;
        $this->articleResource = $articleResource;
        $this->relatedProducts = $relatedProducts;
        $this->categoryRelation = $categoryRelation;
        $this->dataPersistor = $dataPersistor;
        $this->authSession = $authSession;
        $this->categoryIdsResolver = $categoryIdsResolver;
        $this->dataNormalizer = $dataNormalizer;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequestData();
        if (!$data || !is_array($data)) {
            $this->messageManager->addErrorMessage(__('Invalid request data.'));
            return $resultRedirect->setPath('*/*/');
        }
        $categoryIds = $this->categoryIdsResolver->resolve($data);

        // UI component form may submit with fields nested under 'data'
        if (!empty($data['data']) && is_array($data['data'])) {
            $data = array_merge($data, $data['data']);
            unset($data['data']);
        }

        $data = $this->dataNormalizer->resolveMetaRobots($this->dataNormalizer->flattenGroupedFields($data));
        $id = (int) ($data['article_id'] ?? 0);
        $model = $this->articleFactory->create();
        if ($id) {
            $this->articleResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This article no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }
        if (empty($data['url_key']) && !empty($data['title'])) {
            $data['url_key'] = $this->generateUniqueUrlKey((string) $data['title'], $id);
        }
        $urlKey = trim((string) ($data['url_key'] ?? ''));
        if ($urlKey !== '') {
            $existingId = $this->articleResource->getIdByUrlKey($urlKey);
            if ($existingId !== null && $existingId !== $id) {
                $this->messageManager->addErrorMessage(
                    __('URL key "%1" is already in use. Please choose a different URL key.', $urlKey)
                );
                $this->dataPersistor->set('venbhas_blog', $data);
                $path = $id ? '*/*/edit' : '*/*/new';
                $params = $id ? ['article_id' => $id] : [];
                return $resultRedirect->setPath($path, $params);
            }
        }
        $articleData = $this->filterAllowedFields($data);
        if (empty($id) && isset($articleData['article_id'])) {
            unset($articleData['article_id']);
        }
        $articleData['status'] = $this->resolveStatus($articleData['status'] ?? null);
        $articleData['author'] = $this->getLoggedInAdminUserId();
        $model->setData($articleData);
        try {
            $this->articleResource->save($model);
            $articleId = (int) $model->getId();
            if ($articleId > 0) {
                // Store category in venbhas_article_category_relation (not on article table)
                if ($categoryIds === []) {
                    $categoryIds = $this->categoryIdsResolver->resolve($data);
                }
                $this->categoryRelation->saveArticleCategories($articleId, $categoryIds);
                $productIds = $this->resolveRelatedProductIds($data);
                $this->relatedProducts->saveRelatedProducts($articleId, $productIds);
            }
            $this->messageManager->addSuccessMessage(__('You saved the article.'));
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['article_id' => $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('venbhas_blog', $data);
            $path = $id ? '*/*/edit' : '*/*/new';
            $params = $id ? ['article_id' => $id] : [];
            return $resultRedirect->setPath($path, $params);
        }
    }

    /**
     * Get request data from POST or JSON body.
     *
     * @return array
     */
    private function getRequestData(): array
    {
        $request = $this->getRequest();
        $content = $request->getContent();
        $contentType = $request->getHeader('Content-Type');
        if (!empty($content) && $contentType && strpos((string) $contentType, 'application/json') !== false) {
            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $request->getPostValue() ?? [];
    }

    /**
     * Filter request data to allowed fields only.
     *
     * @param array $data
     * @return array
     */
    private function filterAllowedFields(array $data): array
    {
        $filtered = [];
        foreach (self::ALLOWED_FIELDS as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($key === 'featured_image' && is_array($value)) {
                $value = $value[0]['path'] ?? ($value['path'] ?? '');
            }
            $filtered[$key] = $value;
        }
        return $filtered;
    }

    /**
     * Generate URL key from title.
     *
     * @param string $title
     * @return string
     */
    private function generateUrlKey(string $title): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    }

    /**
     * Generate a unique URL key from title.
     *
     * @param string $title
     * @param int $excludeId
     * @return string
     */
    private function generateUniqueUrlKey(string $title, int $excludeId = 0): string
    {
        $baseUrlKey = $this->generateUrlKey($title);
        if ($baseUrlKey === '') {
            return $baseUrlKey;
        }

        $urlKey = $baseUrlKey;
        $suffix = 1;
        while (true) {
            $existingId = $this->articleResource->getIdByUrlKey($urlKey);
            if ($existingId === null || $existingId === $excludeId) {
                return $urlKey;
            }
            $urlKey = $baseUrlKey . '-' . $suffix;
            $suffix++;
        }
    }

    /**
     * Resolve article status for persistence.
     *
     * @param mixed $status
     * @return int
     */
    private function resolveStatus($status): int
    {
        if ($status === null || $status === '') {
            return ArticleStatus::STATUS_DRAFT;
        }

        return (int) $status;
    }

    /**
     * Resolve related product ids from form data.
     *
     * @param array $data
     * @return array
     */
    private function resolveRelatedProductIds(array $data): array
    {
        if (!empty($data['links'][RelatedProductsModifier::DATA_SCOPE_RELATED])
            && is_array($data['links'][RelatedProductsModifier::DATA_SCOPE_RELATED])
        ) {
            $ids = [];
            foreach ($data['links'][RelatedProductsModifier::DATA_SCOPE_RELATED] as $item) {
                if (is_array($item) && !empty($item['id'])) {
                    $ids[] = (int) $item['id'];
                }
            }
            return array_values(array_filter($ids));
        }
        if (!empty($data['related_products']) && is_array($data['related_products'])) {
            return array_values(array_filter(array_map('intval', $data['related_products'])));
        }
        if (!empty($data['related_product_skus'])) {
            $skus = array_map('trim', explode(',', (string) $data['related_product_skus']));
            return $this->getProductIdsBySkus($skus);
        }
        return [];
    }

    /**
     * Get product ids by skus.
     *
     * @param array $skus
     * @return array
     */
    private function getProductIdsBySkus(array $skus): array
    {
        return $this->relatedProducts->getProductIdsBySkus($skus);
    }

    /**
     * Get logged-in admin user id.
     *
     * @return int|null
     */
    private function getLoggedInAdminUserId(): ?int
    {
        $user = $this->authSession->getUser();
        return $user ? (int) $user->getId() : null;
    }
}
