<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Category;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Category form data provider.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array */
    protected $loadedData = [];

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var PoolInterface */
    private $pool;

    /** @var RequestInterface */
    private $request;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CategoryCollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param PoolInterface $pool
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CategoryCollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        PoolInterface $pool,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->pool = $pool;
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
        $persistorData = $this->dataPersistor->get('venbhas_blog_category');

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
                    'use_config_meta_robots' => 1,
                    'featured_image' => '',
                    'related_articles' => [],
                ];
            if (!empty($persistorData)) {
                $this->dataPersistor->clear('venbhas_blog_category');
            }
            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
        } else {
            $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
            $items = $this->collection->getItems();

            foreach ($items as $category) {
                $data = $category->getData();
                $metaRobots = trim((string) ($data['meta_robots'] ?? ''));
                $data['use_config_meta_robots'] = $metaRobots === '' ? 1 : 0;
                // related_articles is stored as comma-separated ids; multiselect options use string values
                $relatedArticlesRaw = trim((string) ($data['related_articles'] ?? ''));
                $data['related_articles'] = $relatedArticlesRaw !== ''
                    ? array_values(
                        array_map(
                            'strval',
                            array_filter(array_map('intval', explode(',', $relatedArticlesRaw)))
                        )
                    )
                    : [];
                $featuredImage = $data['featured_image'] ?? $data['featured image'] ?? '';
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
                $this->loadedData[$category->getId()] = $data;
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
