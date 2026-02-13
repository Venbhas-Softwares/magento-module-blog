<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Article;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Venbhas\Article\Model\ResourceModel\Article\CategoryRelation;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;
use Venbhas\Article\Model\ResourceModel\Article\RelatedProducts;

class DataProvider extends AbstractDataProvider
{
    /** @var array */
    protected $loadedData = [];

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var CategoryRelation */
    private $categoryRelation;

    /** @var RequestInterface */
    private $request;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ArticleCollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        RelatedProducts $relatedProducts,
        CategoryRelation $categoryRelation,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->relatedProducts = $relatedProducts;
        $this->categoryRelation = $categoryRelation;
        $this->request = $request;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    private function getMediaUrl(string $path): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getData(): array
    {
        if ($this->loadedData !== [] && $this->loadedData !== null) {
            return $this->loadedData;
        }

        $id = $this->request->getParam($this->getRequestFieldName());
        $persistorData = $this->dataPersistor->get('venbhas_article');

        // New record (no id): return defaults so form renders - use both '' and 0 for key compatibility
        if (!$id) {
            $defaults = !empty($persistorData)
                ? $persistorData
                : [
                    'article_id' => null,
                    'title' => '',
                    'url_key' => '',
                    'status' => 1,
                    'short_description' => '',
                    'description' => '',
                    'featured_image' => '',
                    'meta_title' => '',
                    'meta_keywords' => '',
                    'meta_description' => '',
                    'meta_robots' => '',
                    'category_id' => '',
                    'related_products' => [],
                    'related_product_skus' => '',
                ];
            if (!empty($persistorData)) {
                $this->dataPersistor->clear('venbhas_article');
            }
            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
            return $this->loadedData;
        }

        // Edit: load single record
        $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
        $items = $this->collection->getItems();

        foreach ($items as $article) {
            $data = $article->getData();
            $categoryId = $this->categoryRelation->getCategoryIdByArticleId((int) $article->getId());
            $data['category_id'] = $categoryId !== null ? (string) $categoryId : '';
            $ids = $this->relatedProducts->getRelatedProductIds((int) $article->getId());
            $data['related_products'] = $ids;
            $featuredImage = $data['featured_image'] ?? '';
            if ($featuredImage) {
                $data['featured_image'] = [['name' => basename($featuredImage), 'path' => $featuredImage, 'url' => $this->getMediaUrl($featuredImage)]];
            }
            $this->loadedData[$article->getId()] = $data;
        }

        return $this->loadedData;
    }
}
