<?php
declare(strict_types=1);

namespace Venbhas\Article\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryActions extends Column
{
    const URL_PATH_EDIT = 'venbhas_article/category/edit';
    const URL_PATH_DELETE = 'venbhas_article/category/delete';

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
                if (isset($item['category_id'])) {
                    $name = $this->escaper->escapeHtml($item['name'] ?? '');
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['category_id' => $item['category_id']]),
                            'label' => __('Edit'),
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['category_id' => $item['category_id']]),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete %1', $name),
                                'message' => __('Are you sure you want to delete this category?'),
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
