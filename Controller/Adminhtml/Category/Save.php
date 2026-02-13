<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Article\Model\CategoryFactory;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;
use Venbhas\Article\Model\ResourceModel\Category\RelatedProducts;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Venbhas_Article::category_save';

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
            $data['url_key'] = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $data['name']), '-'));
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
            $this->dataPersistor->set('venbhas_article_category', $data);
            return $resultRedirect->setPath($id ? '*/*/edit' : '*/*/new', $id ? ['category_id' => $id] : []);
        }
    }

    private function getRequestData(): array
    {
        $request = $this->getRequest();
        $content = $request->getContent();
        if (!empty($content) && $request->getHeader('Content-Type') && strpos((string) $request->getHeader('Content-Type'), 'application/json') !== false) {
            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $request->getPostValue() ?? [];
    }

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

    private function resolveRelatedArticleIds(array $data): array
    {
        if (!empty($data['related_articles']) && is_array($data['related_articles'])) {
            return array_values(array_filter(array_map('intval', $data['related_articles'])));
        }
        return [];
    }

    private function resolveRelatedProductIds(array $data): array
    {
        if (!empty($data['related_products']) && is_array($data['related_products'])) {
            return array_values(array_filter(array_map('intval', $data['related_products'])));
        }
        if (!empty($data['related_product_skus'])) {
            $skus = array_map('trim', explode(',', (string) $data['related_product_skus']));
            return $this->relatedProducts->getProductIdsBySkus($skus);
        }
        return [];
    }
}
