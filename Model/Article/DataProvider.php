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
use Venbhas\Blog\Model\Article\Source\Status as ArticleStatus;
use Venbhas\Blog\Model\Config\Source\MetaRobots;
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

    /** @var MetaRobots */
    private $metaRobots;

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
     * @param MetaRobots $metaRobots
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
        MetaRobots $metaRobots,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->pool = $pool;
        $this->categoryRelation = $categoryRelation;
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

        $id = (int) $this->request->getParam($this->getRequestFieldName());
        $persistorData = $this->dataPersistor->get('venbhas_blog');
        if ($id <= 0) {
            $defaults = $this->getNewArticleDefaults();

            if (!empty($persistorData)) {
                $defaults = array_merge($defaults, $this->normalizeCategoryIds($persistorData));
                $this->dataPersistor->clear('venbhas_blog');
            }
            $defaults['status'] = (string) ArticleStatus::STATUS_DRAFT;

            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
        } else {
            $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
            $items = $this->collection->getItems();

            foreach ($items as $article) {
                $data = $article->getData();
                $data['status'] = (string) (int) ($data['status'] ?? ArticleStatus::STATUS_DRAFT);
                $data['category_ids'] = array_map(
                    'strval',
                    $this->categoryRelation->getCategoryIdsByArticleId((int) $article->getId())
                );
                $metaRobots = $this->metaRobots->normalizeValue($data['meta_robots'] ?? null);
                $data['use_config_meta_robots'] = $metaRobots === null ? true : false;
                $data['meta_robots'] = $metaRobots;
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

        if (isset($meta['seo']['children']['meta_robots_group']['children']['use_config_meta_robots'])) {
            $useConfigMetaRobots = &$meta['seo']['children']['meta_robots_group']['children']['use_config_meta_robots'];
            $useConfigMetaRobots['arguments']['data']['config']['default'] = true;
        }

        return $meta;
    }

    /**
     * Default field values for a new article form.
     *
     * @return array
     */
    private function getNewArticleDefaults(): array
    {
        return [
            'article_id' => null,
            'title' => '',
            'url_key' => '',
            'status' => (string) ArticleStatus::STATUS_DRAFT,
            'short_description' => '',
            'description' => '',
            'featured_image' => '',
            'meta_title' => '',
            'meta_keywords' => '',
            'meta_description' => '',
            'meta_robots' => null,
            'use_config_meta_robots' => true,
            'category_ids' => [],
        ];
    }

    /**
     * Normalize legacy category_id form data to category_ids array.
     *
     * @param array $data
     * @return array
     */
    private function normalizeCategoryIds(array $data): array
    {
        if (!isset($data['category_ids']) && array_key_exists('category_id', $data)) {
            $categoryId = (int) ($data['category_id'] ?? 0);
            $data['category_ids'] = $categoryId > 0 ? [$categoryId] : [];
        }

        if (isset($data['category_ids']) && !is_array($data['category_ids'])) {
            $categoryIds = array_map('intval', explode(',', (string) $data['category_ids']));
            $data['category_ids'] = array_map(
                'strval',
                array_values(array_filter($categoryIds))
            );
        } elseif (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $data['category_ids'] = array_map(
                'strval',
                array_values(array_filter(array_map('intval', $data['category_ids'])))
            );
        }

        return $data;
    }
}
