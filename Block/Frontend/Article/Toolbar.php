<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Venbhas\Blog\Block\Frontend\ModuleEnabledTrait;
use Venbhas\Blog\Model\Config;

/**
 * Article list toolbar (sorting, pagination, limiter) matching catalog product list toolbar.
 */
class Toolbar extends Template
{
    use ModuleEnabledTrait;
    private const ORDER_PARAM = 'order';
    private const LIMIT_PARAM = 'limit';
    private const PAGE_PARAM = 'p';
    private const TPL_AMOUNT = 'Venbhas_Blog::article/list/toolbar/amount.phtml';
    private const TPL_SORTER = 'Venbhas_Blog::article/list/toolbar/sorter.phtml';
    private const TPL_LIMITER = 'Venbhas_Blog::article/list/toolbar/limiter.phtml';

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
     * Initialize toolbar block dependencies.
     *
     * @param Context $context Template context
     * @param Config $config Blog configuration
     * @param FormKey $formKey Form key provider
     * @param array $data Block data
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
     * Assign the article collection to the toolbar.
     *
     * @param AbstractCollection $collection
     * @return $this
     */
    public function setCollection(AbstractCollection $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Get the toolbar article collection.
     *
     * @return AbstractCollection|null
     */
    public function getCollection(): ?AbstractCollection
    {
        return $this->collection;
    }

    /**
     * Set allowed sort order options.
     *
     * @param array $orders
     * @return $this
     */
    public function setAvailableOrders(array $orders): self
    {
        $this->availableOrders = $orders;
        return $this;
    }

    /**
     * Get allowed sort order options.
     *
     * @return array
     */
    public function getAvailableOrders(): array
    {
        return $this->availableOrders ?? $this->config->getSortOptionsForFrontend();
    }

    /**
     * Set the default sort order code.
     *
     * @param string $order
     * @return $this
     */
    public function setDefaultOrder(string $order): self
    {
        $this->defaultOrder = $order;
        return $this;
    }

    /**
     * Get the active sort order code.
     *
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
     * Whether the given sort order is active.
     *
     * @param string $order
     * @return bool
     */
    public function isOrderCurrent(string $order): bool
    {
        return $order === $this->getCurrentOrder();
    }

    /**
     * Get per-page limit options.
     *
     * @return array
     */
    public function getAvailableLimit(): array
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->getAvailablePageLimits($storeId);
    }

    /**
     * Get the active page size limit.
     *
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
     * Whether the given page size is active.
     *
     * @param int|string $limit
     * @return bool
     */
    public function isLimitCurrent($limit): bool
    {
        return (int) $limit === $this->getLimit();
    }

    /**
     * Get the configured default articles per page.
     *
     * @return int
     */
    public function getDefaultPerPageValue(): int
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->getArticlesPerPage($storeId);
    }

    /**
     * Get the first item number on the current page.
     *
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
     * Get the last item number on the current page.
     *
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
     * Get the total number of articles in the collection.
     *
     * @return int
     */
    public function getTotalNum(): int
    {
        $collection = $this->getCollection();
        return $collection ? (int) $collection->getSize() : 0;
    }

    /**
     * Get the total number of pages.
     *
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
     * Whether the toolbar shows expanded controls.
     *
     * @return bool
     */
    public function isExpanded(): bool
    {
        return true;
    }

    /**
     * Mark toolbar placement as top or bottom.
     *
     * @param bool $isBottom
     * @return $this
     */
    public function setIsBottom(bool $isBottom): self
    {
        $this->setData('is_bottom', $isBottom);
        return $this;
    }

    /**
     * Whether the toolbar is rendered at the list bottom.
     *
     * @return bool
     */
    public function getIsBottom(): bool
    {
        return (bool) $this->getData('is_bottom');
    }

    /**
     * Build a list URL preserving blog route rewrites.
     *
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
     * Resolve the pager child block instance.
     *
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
     * Build JSON options for the list toolbar widget.
     *
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

    /**
     * JSON config for productListToolbarForm widget (for data-mage-init).
     *
     * @return string
     */
    public function getToolbarFormWidgetJson(): string
    {
        $decoded = json_decode($this->getWidgetOptionsJson(), true);
        if (!is_array($decoded) || !isset($decoded['productListToolbarForm'])) {
            return '{}';
        }

        return (string) json_encode($decoded['productListToolbarForm']);
    }

    /**
     * Full data-mage-init attribute value for the list toolbar widget.
     *
     * @return string
     */
    public function getToolbarMageInitAttribute(): string
    {
        return '{"productListToolbarForm":' . $this->getToolbarFormWidgetJson() . '}';
    }

    /**
     * Render the toolbar amount partial.
     *
     * @return string
     */
    public function getAmountHtml(): string
    {
        return $this->renderToolbarPartial(self::TPL_AMOUNT);
    }

    /**
     * Render the toolbar sorter partial.
     *
     * @return string
     */
    public function getSorterHtml(): string
    {
        return $this->renderToolbarPartial(self::TPL_SORTER);
    }

    /**
     * Render the toolbar limiter partial.
     *
     * @return string
     */
    public function getLimiterHtml(): string
    {
        return $this->renderToolbarPartial(self::TPL_LIMITER);
    }

    /**
     * Fetch a toolbar sub-template as HTML.
     *
     * @param string $template Module template id
     * @return string
     */
    private function renderToolbarPartial(string $template): string
    {
        return (string) $this->fetchView($this->getTemplateFile($template));
    }
}
