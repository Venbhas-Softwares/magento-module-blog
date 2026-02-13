<?php
declare(strict_types=1);

namespace Venbhas\Article\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ArticleActions extends Column
{
    const URL_PATH_EDIT = 'venbhas_article/article/edit';
    const URL_PATH_DELETE = 'venbhas_article/article/delete';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var Escaper */
    private $escaper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['article_id'])) {
                    $title = $this->escaper->escapeHtml($item['title'] ?? '');
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['article_id' => $item['article_id']]),
                            'label' => __('Edit'),
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['article_id' => $item['article_id']]),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete %1', $title),
                                'message' => __('Are you sure you want to delete this article?'),
                            ],
                            'post' => true,
                        ],
                    ];
                }
            }
        }
        return $dataSource;
    }
}
