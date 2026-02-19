<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Article;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\Article;
use Venbhas\Article\Model\ResourceModel\Article\RelatedProducts;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\NoSuchEntityException;

class View extends Template
{
    /** @var Registry */
    private $registry;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CollectionFactory */
    private $productCollectionFactory;

    /** @var ProductRepository */
    private $productRepository;

    /** @var Image */
    private $imageHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param RelatedProducts $relatedProducts
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepository $productRepository
     * @param Image $imageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        RelatedProducts $relatedProducts,
        StoreManagerInterface $storeManager,
        CollectionFactory $productCollectionFactory,
        ProductRepository $productRepository,
        Image $imageHelper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->relatedProducts = $relatedProducts;
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get current article from registry.
     *
     * @return Article|null
     */
    public function getArticle(): ?Article
    {
        return $this->registry->registry('current_article');
    }

    /**
     * Get human-readable time ago string from datetime.
     *
     * @param string $datetime
     * @return string
     */
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

    /**
     * Get media URL for image path.
     *
     * @param string|null $image
     * @return string|false
     */
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
