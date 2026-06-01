<?php

namespace Venbhas\Blog\Block\Frontend\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Blog\Model\Category;
use Venbhas\Blog\Model\Config;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Block for category list (sidebar).
 */
class ListCategory extends Template
{
    /** @var CollectionFactory */
    protected $categoryCollectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Registry */
    private $registry;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var array<int, int>|null */
    private $articleCounts;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $categoryCollectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        Registry $registry,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data);
    }

    /**
     * Get all active categories
     */
    public function getCategoryCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->setOrder('name', 'ASC');

        return $collection;
    }

    /**
     * Category view URL (path from store config: Article List URL Key + /category/ + url_key).
     *
     * @param \Venbhas\Blog\Model\Category $category
     * @return string
     */
    public function getCategoryUrl($category): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/category/' . $category->getUrlKey()]);
    }

    /**
     * Check whether the given category is the current page category.
     *
     * @param Category $category
     * @return bool
     */
    public function isCategoryActive(Category $category): bool
    {
        $currentCategory = $this->registry->registry('current_article_category');
        return $currentCategory && (int) $currentCategory->getId() === (int) $category->getId();
    }

    /**
     * Get active article count for a category.
     *
     * @param Category $category
     * @return int
     */
    public function getArticleCount(Category $category): int
    {
        if ($this->articleCounts === null) {
            $this->articleCounts = $this->loadArticleCounts();
        }

        return (int) ($this->articleCounts[(int) $category->getId()] ?? 0);
    }

    /**
     * Load article counts grouped by category id.
     *
     * @return array<int, int>
     */
    private function loadArticleCounts(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $relationTable = $this->resourceConnection->getTableName('venbhas_article_category_relation');
        $articleTable = $this->resourceConnection->getTableName('venbhas_article');

        $select = $connection->select()
            ->from(['rel' => $relationTable], ['category_id'])
            ->joinInner(
                ['article' => $articleTable],
                'article.article_id = rel.article_id AND article.status = 1',
                ['count' => new Expression('COUNT(*)')]
            )
            ->group('rel.category_id');

        $counts = [];
        foreach ($connection->fetchAll($select) as $row) {
            $counts[(int) $row['category_id']] = (int) $row['count'];
        }

        return $counts;
    }
}
