<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Article\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Generic button for article edit form.
 */
class GenericButton
{
    /** @var Context */
    protected $context;

    /**
     * Constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Get article id from request.
     *
     * @return int|null
     */
    public function getArticleId(): ?int
    {
        return (int) $this->context->getRequest()->getParam('article_id') ?: null;
    }

    /**
     * Get URL for route.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
