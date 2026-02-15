<?php 
namespace Venbhas\Article\Block\Frontend;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;
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


    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        Registry $registry,
        CategoryRelatedProducts $relatedCategoryProducts,
        ArticleRelatedProducts $relatedArticleProducts,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->registry = $registry;
        $this->relatedCategoryProducts = $relatedCategoryProducts;
        $this->relatedArticleProducts = $relatedArticleProducts;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function getRelatedProductCollection()
    {
        $ids = [];
        $articleId = $this->getCurrentArticle()->getId();
        if($articleId) {
            $ids = $this->getArticleRelatedProductIds($articleId);
        } else {
            $categoryId = $this->getCurrentCategory()->getcategoryId();
            $ids = $this->getCategoryRelatedProductIds($categoryId);
        }

        if (empty($ids)) {
            return [];
        
        }
        

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
            //->addIdFilter($ids);
            //->addAttributeToFilter('status', 1);
            //->addAttributeToFilter('visibility', ['in' => [2,3,4]])
            //->setStoreId($this->storeManager->getStore()->getId());
            $collection->getSelect()->limit(10);
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
