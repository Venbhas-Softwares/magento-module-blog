<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Article;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\Article;
use Venbhas\Article\Model\ResourceModel\Article\RelatedProducts;
use Magento\Store\Model\StoreManagerInterface;

class View extends Template
{
    /** @var Registry */
    private $registry;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        Context $context,
        Registry $registry,
        RelatedProducts $relatedProducts,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->relatedProducts = $relatedProducts;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function getArticle(): ?Article
    {
        return $this->registry->registry('current_article');
    }

    public function getRelatedProductIds(): array
    {
        $article = $this->getArticle();
        return $article ? $this->relatedProducts->getRelatedProductIds((int) $article->getId()) : [];
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

}
