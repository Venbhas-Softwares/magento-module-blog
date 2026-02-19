<?php
namespace Venbhas\Article\Block\Frontend;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Category\RelatedProducts as CategoryRelatedProducts;
use Venbhas\Article\Model\ResourceModel\Article\RelatedProducts as ArticleRelatedProducts;

class RelatedProducts extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /** @var CollectionFactory */
    protected $productCollectionFactory;

    /** @var CategoryRelatedProducts */
    protected $relatedCategoryProducts;

    /** @var ArticleRelatedProducts */
    protected $relatedArticleProducts;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var Registry */
    protected $registry;

    /** @var Image */
    protected $imageHelper;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var Config */
    private $config;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepository $productRepository
     * @param Image $imageHelper
     * @param Registry $registry
     * @param CategoryRelatedProducts $relatedCategoryProducts
     * @param ArticleRelatedProducts $relatedArticleProducts
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        Registry $registry,
        CategoryRelatedProducts $relatedCategoryProducts,
        ArticleRelatedProducts $relatedArticleProducts,
        StoreManagerInterface $storeManager,
        Config $config,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->registry = $registry;
        $this->relatedCategoryProducts = $relatedCategoryProducts;
        $this->relatedArticleProducts = $relatedArticleProducts;
        $this->storeManager = $storeManager;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Resolve related product IDs for current article or category.
     *
     * @return int[]
     */
    private function getRelatedProductIds(): array
    {
        $ids = [];
        $article = $this->getCurrentArticle();
        $category = $this->getCurrentCategory();
        if ($article && $article->getId()) {
            $ids = $this->getArticleRelatedProductIds((int) $article->getId());
        } elseif ($category && $category->getId()) {
            $ids = $this->getCategoryRelatedProductIds((int) $category->getId());
        } else {
            $categoryId = (int) $this->getRequest()->getParam('category_id');
            $articleId = (int) $this->getRequest()->getParam('article_id');
            if ($articleId) {
                $ids = $this->getArticleRelatedProductIds($articleId);
            } elseif ($categoryId) {
                $ids = $this->getCategoryRelatedProductIds($categoryId);
            }
        }
        return array_values(array_filter(array_map('intval', (array) $ids)));
    }

    /**
     * Load all related products (no limit). Config "Related Products Limit" = how many visible at once in the slider.
     *
     * @return Product[]
     */
    public function getRelatedProducts(): array
    {
        $ids = $this->getRelatedProductIds();
        if (empty($ids)) {
            return [];
        }
        $storeId = (int) $this->storeManager->getStore()->getId();
        $products = [];
        foreach ($ids as $id) {
            try {
                $product = $this->productRepository->getById($id, false, $storeId);
                if ($product && (int) $product->getStatus() === ProductStatus::STATUS_ENABLED) {
                    $products[] = $product;
                }
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        return $products;
    }

    /**
     * Number of related products visible at once.
     *
     * @return int
     */
    public function getVisibleCount(): int
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $n = $this->config->getRelatedProductsLimit($storeId);
        return $n > 0 ? $n : 4;
    }

    /**
     * Deprecated: use getRelatedProducts() instead. Returns empty collection for backwards compatibility.
     *
     * @deprecated Use getRelatedProducts() instead.
     * @see getRelatedProducts()
     */
    public function getRelatedProductCollection()
    {
        $ids = $this->getRelatedProductIds();
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($this->storeManager->getStore()->getId())
            ->addAttributeToSelect('*')
            ->addIdFilter(empty($ids) ? [0] : $ids);
        return $collection;
    }

    /**
     * Get product image URL by product ID.
     *
     * @param int $productId
     * @return string
     */
    public function getProductImageUrl($productId)
    {
        $imageUrl = '';
        try {
            $_product = $this->productRepository->getById($productId);
            $imageUrl = $this->imageHelper
                ->init($_product, 'category_page_list')
                ->getUrl();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return '';
        }

        return $imageUrl;
    }

    /**
     * Get product price HTML for list display.
     *
     * @param Product $product
     * @return string
     */
    public function getProductPrice($product)
    {
        $priceRender = $this->getLayout()->getBlock('product.price.render.default')
            ->setData('is_product_list', true);

        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                $product,
                [
                    'include_container' => true,
                    'display_minimal_price' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                    'list_category_page' => true
                ]
            );
        }

        return $price;
    }

    /**
     * Get current article from registry.
     *
     * @return \Venbhas\Article\Model\Article|null
     */
    public function getCurrentArticle()
    {
        return $this->registry->registry('current_article');
    }

    /**
     * Get current category from registry.
     *
     * @return \Venbhas\Article\Model\Category|null
     */
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_article_category');
    }

    /**
     * Get related product IDs for an article.
     *
     * @param int $articleId
     * @return array
     */
    public function getArticleRelatedProductIds($articleId): array
    {
        return $articleId ? $this->relatedArticleProducts->getRelatedProductIds($articleId) : [];
    }

    /**
     * Get related product IDs for a category.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryRelatedProductIds($categoryId): array
    {
        return $categoryId ? $this->relatedCategoryProducts->getRelatedProductIds($categoryId) : [];
    }
}
