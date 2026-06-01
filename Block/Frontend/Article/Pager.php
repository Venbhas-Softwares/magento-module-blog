<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager as ThemePager;
use Venbhas\Blog\Model\Config;

/**
 * Blog list pager with clean frontend URLs and reliable page count.
 */
class Pager extends ThemePager
{
    /** @var string */
    protected $_template = 'Venbhas_Blog::article/list/toolbar/pager.phtml';

    /** @var Config */
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @param array $params
     * @return string
     */
    public function getPagerUrl($params = [])
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        $listRoute = trim($this->config->getArticleListRoute($storeId), '/');
        $categoryRoute = trim($this->config->getCategoryListRoute($storeId), '/');
        $path = trim((string) $this->getRequest()->getPathInfo(), '/');

        if ($path === '' || $path === $listRoute) {
            $directPath = $listRoute;
        } elseif ($path === $categoryRoute) {
            $directPath = $categoryRoute;
        } elseif (str_starts_with($path, $listRoute . '/category/')) {
            $directPath = $path;
        } else {
            return parent::getPagerUrl($params);
        }

        return $this->getUrl('', [
            '_direct' => $directPath,
            '_query' => $params,
            '_use_rewrite' => true,
            '_fragment' => $this->getFragment(),
        ]);
    }

    /**
     * @return int
     */
    public function getLastPageNum()
    {
        $limit = (int) $this->getLimit();
        if ($limit <= 0) {
            return 1;
        }

        $collection = $this->getCollection();
        if (!$collection instanceof Collection) {
            return 1;
        }

        return (int) max(1, (int) ceil((int) $collection->getSize() / $limit));
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $collection = $this->getCollection();
        if (!$collection instanceof Collection || !$collection->getSize()) {
            return '';
        }

        $limit = (int) $this->getLimit();
        if ($limit <= 0) {
            return '';
        }

        if ((int) $collection->getSize() <= $limit) {
            return '';
        }

        return parent::_toHtml();
    }
}
