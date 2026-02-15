<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Category;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\Category;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;
use Venbhas\Article\Model\ResourceModel\Category\RelatedProducts;
use Magento\Store\Model\StoreManagerInterface;

class View extends Template
{
    /** @var ArticleCollectionFactory */
    private $articleCollectionFactory;


    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var Registry */
    private $registry;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        Context $context,
        ArticleCollectionFactory $articleCollectionFactory,
        RelatedProducts $relatedProducts,
        Registry $registry,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->articleCollectionFactory = $articleCollectionFactory;
        $this->relatedProducts = $relatedProducts;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function getCategory(): ?Category
    {
        return $this->_data['category'] ?? $this->registry->registry('current_article_category');
    }

    public function getRelatedProductIds(): array
    {
        $category = $this->getCategory();
        return $category ? $this->relatedProducts->getRelatedProductIds((int) $category->getId()) : [];
    }

    public function getArticles()
    {
        $category = $this->getCategory();
        if (!$category) {
            return [];
        }
        $collection = $this->articleCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->join(
            ['rel' => 'venbhas_article_category_relation'],
            'main_table.article_id = rel.article_id AND rel.category_id = ' . (int) $category->getId(),
            []
        );
        $collection->setOrder('updated_at', 'desc');
        return $collection;
    }
    public function getTimeAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff . ' seconds';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' days';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . ' months';
        } else {
            return floor($diff / 31536000) . ' years';
        }
    }
    public function getImageUrl($image)
    {
        
        if (!$image) {
            return false;
        }
            
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . ltrim($image, '/');
    }

    public function getArticleUrl(string $urlKey): string
    {
        return $this->getUrl('article/' . $urlKey);
    }
}
