<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Venbhas\Blog\Model\Config;

/**
 * Article list toolbar (sorting, pagination, limiter) matching catalog product list toolbar.
 */
class Toolbar extends Template
{
    private const ORDER_PARAM = 'order';
    private const LIMIT_PARAM = 'limit';
    private const PAGE_PARAM = 'p';

    /** @var AbstractCollection|null */
    private $collection;

    /** @var array|null */
    private $availableOrders;

    /** @var string|null */
    private $defaultOrder;

    /** @var Config */
    private $config;

    /** @var FormKey */
    private $formKey;

    /**
     * @param Context $context
     * @param Config $config
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        FormKey $formKey,
        array $data = []
    ) {
        $this->config = $config;
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractCollection $collection
     * @return $this
     */
    public function setCollection(AbstractCollection $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return AbstractCollection|null
     */
    public function getCollection(): ?AbstractCollection
    {
        return $this->collection;
    }

    /**
     * @param array $orders
     * @return $this
     */
    public function setAvailableOrders(array $orders): self
    {
        $this->availableOrders = $orders;
        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableOrders(): array
    {
        return $this->availableOrders ?? $this->config->getSortOptionsForFrontend();
    }

    /**
     * @param string $order
     * @return $this
     */
    public function setDefaultOrder(string $order): self
    {
        $this->defaultOrder = $order;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentOrder(): string
    {
        $requestOrder = (string) $this->getRequest()->getParam(self::ORDER_PARAM, '');
        $valid = array_keys($this->getAvailableOrders());
        if ($requestOrder !== '' && in_array($requestOrder, $valid, true)) {
            return $requestOrder;
        }

        if ($this->defaultOrder !== null) {
            return $this->defaultOrder;
        }

        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->getDefaultSortOrder($storeId);
    }

    /**
     * @param string $order
     * @return bool
     */
    public function isOrderCurrent(string $order): bool
    {
        return $order === $this->getCurrentOrder();
    }

    /**
     * @return array
     */
    public function getAvailableLimit(): array
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->getAvailablePageLimits($storeId);
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        $limits = $this->getAvailableLimit();
        $defaultLimit = $this->getDefaultPerPageValue();
        $limit = (int) $this->getRequest()->getParam(self::LIMIT_PARAM, $defaultLimit);

        if (!isset($limits[$limit])) {
            $limit = $defaultLimit;
        }

        return $limit > 0 ? $limit : $defaultLimit;
    }

    /**
     * @param int|string $limit
     * @return bool
     */
    public function isLimitCurrent($limit): bool
    {
        return (int) $limit === $this->getLimit();
    }

    /**
     * @return int
     */
    public function getDefaultPerPageValue(): int
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->getArticlesPerPage($storeId);
    }

    /**
     * @return int
     */
    public function getFirstNum(): int
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return 0;
        }

        return (int) ($collection->getPageSize() * ($collection->getCurPage() - 1) + 1);
    }

    /**
     * @return int
     */
    public function getLastNum(): int
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return 0;
        }

        return (int) ($collection->getPageSize() * ($collection->getCurPage() - 1) + $collection->count());
    }

    /**
     * @return int
     */
    public function getTotalNum(): int
    {
        $collection = $this->getCollection();
        return $collection ? (int) $collection->getSize() : 0;
    }

    /**
     * @return int
     */
    public function getLastPageNum(): int
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return 1;
        }

        $limit = $this->getLimit();
        if ($limit <= 0) {
            return 1;
        }

        return (int) max(1, (int) ceil($this->getTotalNum() / $limit));
    }

    /**
     * @return bool
     */
    public function isExpanded(): bool
    {
        return true;
    }

    /**
     * @param bool $isBottom
     * @return $this
     */
    public function setIsBottom(bool $isBottom): self
    {
        $this->setData('is_bottom', $isBottom);
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsBottom(): bool
    {
        return (bool) $this->getData('is_bottom');
    }

    /**
     * @param array $params
     * @return string
     */
    public function getPagerUrl(array $params = []): string
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        $listRoute = trim($this->config->getArticleListRoute($storeId), '/');
        $categoryRoute = trim($this->config->getCategoryListRoute($storeId), '/');
        $path = trim((string) $this->getRequest()->getPathInfo(), '/');
        $pathParts = $path !== '' ? explode('/', $path) : [];
        $first = $pathParts[0] ?? '';

        if ($first === $categoryRoute) {
            return $this->getUrl('', [
                '_direct' => $categoryRoute,
                '_query' => $params,
                '_use_rewrite' => true,
            ]);
        }

        if ($first === $listRoute || str_starts_with($path, $listRoute . '/category/')) {
            return $this->getUrl('', [
                '_direct' => $path !== '' ? $path : $listRoute,
                '_query' => $params,
                '_use_rewrite' => true,
            ]);
        }

        return $this->getUrl('*/*/*', [
            '_current' => true,
            '_escape' => false,
            '_use_rewrite' => true,
            '_query' => $params,
        ]);
    }

    /**
     * Render pagination HTML (same approach as catalog product list toolbar).
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        $collection = $this->getCollection();
        if (!$collection instanceof AbstractCollection) {
            return '';
        }

        $limit = $this->getLimit();
        $page = max(1, (int) $this->getRequest()->getParam(self::PAGE_PARAM, 1));

        if ($collection->isLoaded()) {
            $collection->clear();
        }

        $collection->setPageSize($limit)->setCurPage($page);

        $total = (int) $collection->getSize();
        if ($total <= $limit) {
            return '';
        }

        $pagerBlock = $this->getPagerBlock();
        if (!$pagerBlock instanceof Pager) {
            return '';
        }

        $pagerBlock->setAvailableLimit($this->getAvailableLimit());
        $pagerBlock->setUseContainer(false)
            ->setShowPerPage(false)
            ->setShowAmounts(false)
            ->setFrameLength(
                (int) $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    ScopeInterface::SCOPE_STORE
                )
            )
            ->setJump(
                (int) $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    ScopeInterface::SCOPE_STORE
                )
            )
            ->setLimit($limit)
            ->setCollection($collection);

        return (string) $pagerBlock->toHtml();
    }

    /**
     * @return Pager|null
     */
    protected function getPagerBlock(): ?Pager
    {
        $pagerName = (string) ($this->getData('pager_block_name') ?: 'article_list_toolbar_pager');
        $pagerBlock = $this->getChildBlock($pagerName);

        if (!$pagerBlock instanceof Pager) {
            $pagerBlock = $this->getLayout()->getBlock($pagerName);
        }

        if (!$pagerBlock instanceof Pager) {
            $pagerBlock = $this->getLayout()->createBlock(
                Pager::class,
                $this->getNameInLayout() . '.' . $pagerName
            );
        }

        return $pagerBlock instanceof Pager ? $pagerBlock : null;
    }

    /**
     * @param array $customOptions
     * @return string
     */
    public function getWidgetOptionsJson(array $customOptions = []): string
    {
        $options = [
            'mode' => 'product_list_mode',
            'direction' => 'product_list_dir',
            'order' => self::ORDER_PARAM,
            'limit' => self::LIMIT_PARAM,
            'page' => self::PAGE_PARAM,
            'modeDefault' => 'grid',
            'directionDefault' => 'desc',
            'orderDefault' => $this->getCurrentOrder(),
            'limitDefault' => (string) $this->getDefaultPerPageValue(),
            'url' => $this->getPagerUrl(),
            'formKey' => $this->formKey->getFormKey(),
            'post' => false,
        ];
        $options = array_replace_recursive($options, $customOptions);

        return (string) json_encode(['productListToolbarForm' => $options]);
    }
}
