<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Comment\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    /** @var Context */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getCommentId(): ?int
    {
        return (int) $this->context->getRequest()->getParam('comment_id') ?: null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
