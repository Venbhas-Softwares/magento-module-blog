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
    protected $productCollectionFactory;
    protected $relatedCategoryProducts;
    protected $relatedArticleProducts;
    protected $storeManager;
    protected $registry;
    protected $imageHelper;
    protected $productRepository;

    /** @var Config */
    private $config;

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
     * Load related products via repository (bypasses collection store/website filters).
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
        $limit = $this->config->getRelatedProductsLimit($storeId);
        $ids = array_slice($ids, 0, $limit);
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
     * @deprecated Use getRelatedProducts(). Returns empty collection for backwards compatibility.
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
    
    public function getCurrentArticle()
    {
        return $this->registry->registry('current_article');
    }

    public function getCurrentCategory()
    {
        return $this->registry->registry('current_article_category');
    }

    public function getArticleRelatedProductIds($articleId): array
    {
        return $articleId ? $this->relatedArticleProducts->getRelatedProductIds( $articleId) : [];
    }
    public function getCategoryRelatedProductIds($categoryId): array
    {
        return $categoryId ? $this->relatedCategoryProducts->getRelatedProductIds($categoryId) : [];
    }


}
