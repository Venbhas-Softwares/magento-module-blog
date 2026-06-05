<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Blog\Block\Frontend\ModuleEnabledTrait;
use Venbhas\Blog\Model\Article;
use Venbhas\Blog\Model\Article\Source\Status as ArticleStatus;
use Venbhas\Blog\Model\Config;
use Venbhas\Blog\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;
use Venbhas\Blog\Model\ResourceModel\Article\RelatedProducts as ArticleRelatedProducts;

/**
 * Related articles block for the product detail page.
 */
class RelatedArticles extends AbstractProduct
{
    use ModuleEnabledTrait;

    /** @var ArticleRelatedProducts */
    private $articleRelatedProducts;

    /** @var ArticleCollectionFactory */
    private $articleCollectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param Context $context
     * @param ArticleRelatedProducts $articleRelatedProducts
     * @param ArticleCollectionFactory $articleCollectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        ArticleRelatedProducts $articleRelatedProducts,
        ArticleCollectionFactory $articleCollectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->articleRelatedProducts = $articleRelatedProducts;
        $this->articleCollectionFactory = $articleCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Load published articles linked to the current product.
     *
     * @return Article[]
     */
    public function getRelatedArticles(): array
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return [];
        }

        $articleIds = $this->articleRelatedProducts->getRelatedArticleIdsByProductId((int) $product->getId());
        if ($articleIds === []) {
            return [];
        }

        $collection = $this->articleCollectionFactory->create();
        $collection->addFieldToFilter('article_id', ['in' => $articleIds]);
        $collection->addFieldToFilter('status', ArticleStatus::STATUS_PUBLISHED);

        $articlesById = [];
        foreach ($collection as $article) {
            $articlesById[(int) $article->getId()] = $article;
        }

        $articles = [];
        foreach ($articleIds as $articleId) {
            if (isset($articlesById[$articleId])) {
                $articles[] = $articlesById[$articleId];
            }
        }

        return $articles;
    }

    /**
     * Number of related articles visible at once in the slider.
     *
     * @return int
     */
    public function getVisibleCount(): int
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $count = $this->config->getRelatedProductsLimit($storeId);

        return $count > 0 ? $count : 4;
    }

    /**
     * Article detail URL.
     *
     * @param string $urlKey
     * @return string
     */
    public function getArticleUrl(string $urlKey): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');

        return $this->getUrl('', ['_direct' => $basePath . '/' . $urlKey]);
    }

    /**
     * Get media URL for article featured image.
     *
     * @param string|null $image
     * @return string
     */
    public function getImageUrl(?string $image): string
    {
        if (!$image) {
            return '';
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($image, '/');
    }
}
