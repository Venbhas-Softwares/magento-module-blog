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
use Venbhas\Blog\Model\Config\Source\MetaRobots;
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

    /** @var MetaRobots */
    private $metaRobots;

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
     * @param MetaRobots $metaRobots
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
        MetaRobots $metaRobots,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->pool = $pool;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->metaRobots = $metaRobots;
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
                    'parent_id' => $this->request->has('parent')
                        ? (int) $this->request->getParam('parent')
                        : 0,
                    'name' => '',
                    'url_key' => '',
                    'status' => 1,
                    'short_description' => '',
                    'description' => '',
                    'meta_title' => '',
                    'meta_keywords' => '',
                    'meta_description' => '',
                    'meta_robots' => null,
                    'use_config_meta_robots' => true,
                    'featured_image' => '',
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
                $metaRobots = $this->metaRobots->normalizeValue($data['meta_robots'] ?? null);
                $data['use_config_meta_robots'] = $metaRobots === null ? true : false;
                $data['meta_robots'] = $metaRobots;
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

        if (isset($meta['seo']['children']['meta_robots_group']['children']['use_config_meta_robots'])) {
            $useConfigMetaRobots = &$meta['seo']['children']['meta_robots_group']['children']['use_config_meta_robots'];
            $useConfigMetaRobots['arguments']['data']['config']['default'] = true;
        }

        return $meta;
    }
}
