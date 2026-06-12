<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Shared toolbar wiring for article listing blocks.
 */
trait ToolbarAwareTrait
{
    /**
     * Render the top list toolbar HTML.
     *
     * @return string
     */
    public function getToolbarHtml(): string
    {
        $toolbar = $this->getArticleToolbarBlock();
        if (!$toolbar instanceof Toolbar) {
            return '';
        }

        return (string) $toolbar->setIsBottom(false)->toHtml();
    }

    /**
     * Render the bottom list toolbar HTML.
     *
     * @return string
     */
    public function getBottomToolbarHtml(): string
    {
        $toolbar = $this->getArticleToolbarBlock();
        if (!$toolbar instanceof Toolbar) {
            return '';
        }

        return (string) $toolbar->setIsBottom(true)->toHtml();
    }

    /**
     * Configure toolbar before block HTML is rendered.
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $collection = $this->getToolbarCollection();
        if ($collection instanceof AbstractCollection && $collection->getSize()) {
            $this->configureArticleToolbar($collection);
        }

        return parent::_beforeToHtml();
    }

    /**
     * Apply paging and sort settings to the toolbar block.
     *
     * @param AbstractCollection $collection
     * @return void
     */
    protected function configureArticleToolbar(AbstractCollection $collection): void
    {
        $toolbar = $this->getArticleToolbarBlock();
        if (!$toolbar instanceof Toolbar) {
            return;
        }

        $limit = $this->getPageLimit();
        $page = max(1, (int) $this->getRequest()->getParam('p', 1));

        if ($collection->isLoaded()) {
            $collection->clear();
        }

        $collection->setPageSize($limit)->setCurPage($page);

        $toolbar->setCollection($collection);
        $toolbar->setAvailableOrders($this->getToolbarSortOptions());
        $toolbar->setDefaultOrder($this->getCurrentSortOrder());
        $this->setChild('toolbar', $toolbar);
    }

    /**
     * Resolve the active page size from the request.
     *
     * @return int
     */
    protected function getPageLimit(): int
    {
        $storeId = $this->getStoreId();
        $limits = $this->config->getAvailablePageLimits($storeId);
        $defaultLimit = $this->config->getArticlesPerPage($storeId);
        $limit = (int) $this->getRequest()->getParam('limit', $defaultLimit);

        if (!isset($limits[$limit])) {
            $limit = $defaultLimit;
        }

        return $limit > 0 ? $limit : $defaultLimit;
    }

    /**
     * Get the current store id.
     *
     * @return int
     */
    protected function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * Get sort options for the toolbar.
     *
     * @return array
     */
    protected function getToolbarSortOptions(): array
    {
        return $this->config->getSortOptionsForFrontend();
    }

    /**
     * Apply the configured sort order to a collection.
     *
     * @param AbstractCollection $collection
     * @return void
     */
    protected function applyToolbarSort(AbstractCollection $collection): void
    {
        $sort = $this->config->getSortOrderFieldAndDirection($this->getCurrentSortOrder());
        $collection->setOrder($sort['field'], $sort['direction']);
    }

    /**
     * Resolve the toolbar block from layout.
     *
     * @return Toolbar|null
     */
    protected function getArticleToolbarBlock(): ?Toolbar
    {
        $blockName = (string) ($this->getData('toolbar_block_name') ?: 'article_list_toolbar');
        $toolbar = $this->getChildBlock($blockName);
        if ($toolbar instanceof Toolbar) {
            return $toolbar;
        }

        $toolbar = $this->getLayout()->getBlock($blockName);
        return $toolbar instanceof Toolbar ? $toolbar : null;
    }

    /**
     * Return the collection used by the toolbar.
     *
     * @return AbstractCollection|array|null
     */
    abstract protected function getToolbarCollection();
}
