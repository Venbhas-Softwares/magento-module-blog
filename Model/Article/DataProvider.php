<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Article;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Venbhas\Blog\Model\ResourceModel\Article\CategoryRelation;
use Venbhas\Blog\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;

/**
 * Article form data provider.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array */
    protected $loadedData = [];

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var PoolInterface */
    private $pool;

    /** @var CategoryRelation */
    private $categoryRelation;

    /** @var RequestInterface */
    private $request;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ArticleCollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param PoolInterface $pool
     * @param CategoryRelation $categoryRelation
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ArticleCollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        PoolInterface $pool,
        CategoryRelation $categoryRelation,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->pool = $pool;
        $this->categoryRelation = $categoryRelation;
        $this->request = $request;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get media URL for path.
     *
     * @param string $path
     * @return string
     */
    private function getMediaUrl(string $path): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->loadedData !== [] && $this->loadedData !== null) {
            return $this->loadedData;
        }

        $id = $this->request->getParam($this->getRequestFieldName());
        $persistorData = $this->dataPersistor->get('venbhas_blog');

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
                    'use_config_meta_robots' => 1,
                    'category_id' => '',
                ];
            if (!empty($persistorData)) {
                $this->dataPersistor->clear('venbhas_blog');
            }
            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
        } else {
            // Edit: load single record
            $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
            $items = $this->collection->getItems();

            foreach ($items as $article) {
                $data = $article->getData();
                $categoryId = $this->categoryRelation->getCategoryIdByArticleId((int) $article->getId());
                $data['category_id'] = $categoryId !== null ? (string) $categoryId : '';
                $metaRobots = trim((string) ($data['meta_robots'] ?? ''));
                $data['use_config_meta_robots'] = $metaRobots === '' ? 1 : 0;
                $featuredImage = $data['featured_image'] ?? '';
                if ($featuredImage) {
                    $fileName = preg_replace('#^.*[/\\\\]#', '', $featuredImage);
                    $data['featured_image'] = [
                        [
                            'name' => $fileName,
                            'path' => $featuredImage,
                            'url' => $this->getMediaUrl($featuredImage),
                        ],
                    ];
                }
                $this->loadedData[$article->getId()] = $data;
            }
        }

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->loadedData = $modifier->modifyData($this->loadedData);
        }

        return $this->loadedData;
    }

    /**
     * @inheritdoc
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
