<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Category;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Venbhas\Article\Model\ResourceModel\Category\RelatedProducts;

class DataProvider extends AbstractDataProvider
{
    /** @var array */
    protected $loadedData = [];

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var RequestInterface */
    private $request;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CategoryCollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        RelatedProducts $relatedProducts,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->relatedProducts = $relatedProducts;
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
        $persistorData = $this->dataPersistor->get('venbhas_article_category');

        if (!$id) {
            $defaults = !empty($persistorData)
                ? $persistorData
                : [
                    'category_id' => null,
                    'name' => '',
                    'url_key' => '',
                    'status' => 1,
                    'short_description' => '',
                    'description' => '',
                    'meta_title' => '',
                    'meta_keywords' => '',
                    'meta_description' => '',
                    'meta_robots' => '',
                    'featured_image' => '',
                    'related_products' => [],
                    'related_articles' => [],
                ];
            if (!empty($persistorData)) {
                $this->dataPersistor->clear('venbhas_article_category');
            }
            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
        $items = $this->collection->getItems();

        foreach ($items as $category) {
            $data = $category->getData();
            // related_articles is stored as comma-separated ids; multiselect options use string values
            $relatedArticlesRaw = trim((string) ($data['related_articles'] ?? ''));
            $data['related_articles'] = $relatedArticlesRaw !== ''
                ? array_values(array_map('strval', array_filter(array_map('intval', explode(',', $relatedArticlesRaw)))))
                : [];
            $ids = $this->relatedProducts->getRelatedProductIds((int) $category->getId());
            $data['related_products'] = $ids;
            $featuredImage = $data['featured_image'] ?? $data['featured image'] ?? '';
            if ($featuredImage) {
                $data['featured_image'] = [['name' => basename($featuredImage), 'path' => $featuredImage, 'url' => $this->getMediaUrl($featuredImage)]];
            }
            $this->loadedData[$category->getId()] = $data;
        }

        return $this->loadedData;
    }
}
