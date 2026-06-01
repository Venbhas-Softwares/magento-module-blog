<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;
use Venbhas\Blog\Model\ResourceModel\Category\RelatedProducts;
use Venbhas\Blog\Ui\DataProvider\Form\Modifier\RelatedProducts as RelatedProductsModifier;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category_save';

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CategoryResource */
    private $categoryResource;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var string[] Allowed category table columns for setData (related_articles set from form array) */
    private const ALLOWED_FIELDS = [
        'category_id', 'name', 'url_key', 'status', 'description', 'short_description',
        'featured_image', 'meta_title', 'meta_keywords', 'meta_description', 'meta_robots',
    ];

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param RelatedProducts $relatedProducts
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        CategoryFactory $categoryFactory,
        CategoryResource $categoryResource,
        RelatedProducts $relatedProducts,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
        $this->relatedProducts = $relatedProducts;
        $this->dataPersistor = $dataPersistor;
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
        if (!empty($data['data']) && is_array($data['data'])) {
            $data = array_merge($data, $data['data']);
            unset($data['data']);
        }

        if (!empty($data['use_config_meta_robots'])) {
            $data['meta_robots'] = null;
        }
        $id = (int) ($data['category_id'] ?? 0);
        $model = $this->categoryFactory->create();
        if ($id) {
            $this->categoryResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This category no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }
        if (empty($data['url_key']) && !empty($data['name'])) {
            $data['url_key'] = $this->generateUniqueUrlKey((string) $data['name'], $id);
        }
        $urlKey = trim((string) ($data['url_key'] ?? ''));
        if ($urlKey !== '') {
            $existingId = $this->categoryResource->getIdByUrlKey($urlKey);
            if ($existingId !== null && $existingId !== $id) {
                $this->messageManager->addErrorMessage(
                    __('URL key "%1" is already in use. Please choose a different URL key.', $urlKey)
                );
                $this->dataPersistor->set('venbhas_blog_category', $data);
                $path = $id ? '*/*/edit' : '*/*/new';
                $params = $id ? ['category_id' => $id] : [];
                return $resultRedirect->setPath($path, $params);
            }
        }
        $categoryData = $this->filterAllowedFields($data);
        if (empty($id) && isset($categoryData['category_id'])) {
            unset($categoryData['category_id']);
        }
        // related_articles: form sends array; DB column is text (comma-separated ids)
        $articleIds = $this->resolveRelatedArticleIds($data);
        $categoryData['related_articles'] = $articleIds !== [] ? implode(',', $articleIds) : null;
        $model->setData($categoryData);
        try {
            $this->categoryResource->save($model);
            $categoryId = (int) $model->getId();
            if ($categoryId > 0) {
                $productIds = $this->resolveRelatedProductIds($data);
                $this->relatedProducts->saveRelatedProducts($categoryId, $productIds);
            }
            $this->messageManager->addSuccessMessage(__('You saved the category.'));
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['category_id' => $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('venbhas_blog_category', $data);
            $path = $id ? '*/*/edit' : '*/*/new';
            $params = $id ? ['category_id' => $id] : [];
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
        $isJson = $contentType && strpos((string) $contentType, 'application/json') !== false;
        if (!empty($content) && $isJson) {
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
     * Resolve related article ids from form data.
     *
     * @param array $data
     * @return array
     */
    private function resolveRelatedArticleIds(array $data): array
    {
        if (!empty($data['related_articles']) && is_array($data['related_articles'])) {
            return array_values(array_filter(array_map('intval', $data['related_articles'])));
        }
        return [];
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
            return $this->relatedProducts->getProductIdsBySkus($skus);
        }
        return [];
    }

    /**
     * Generate URL key from name.
     *
     * @param string $name
     * @return string
     */
    private function generateUrlKey(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    }

    /**
     * Generate a unique URL key from name.
     *
     * @param string $name
     * @param int $excludeId
     * @return string
     */
    private function generateUniqueUrlKey(string $name, int $excludeId = 0): string
    {
        $baseUrlKey = $this->generateUrlKey($name);
        if ($baseUrlKey === '') {
            return $baseUrlKey;
        }

        $urlKey = $baseUrlKey;
        $suffix = 1;
        while (true) {
            $existingId = $this->categoryResource->getIdByUrlKey($urlKey);
            if ($existingId === null || $existingId === $excludeId) {
                return $urlKey;
            }
            $urlKey = $baseUrlKey . '-' . $suffix;
            $suffix++;
        }
    }
}
