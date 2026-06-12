<?php
declare(strict_types=1);

namespace Venbhas\Blog\Ui\DataProvider\Article\Form\Modifier;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Venbhas\Blog\Ui\Component\Article\Form\Categories\Options;

/**
 * Injects category tree options into the article categories ui-select field.
 */
class Categories implements ModifierInterface
{
    /** @var ArrayManager */
    private $arrayManager;

    /** @var Options */
    private $categoriesOptions;

    /**
     * @param ArrayManager $arrayManager
     * @param Options $categoriesOptions
     */
    public function __construct(
        ArrayManager $arrayManager,
        Options $categoriesOptions
    ) {
        $this->arrayManager = $arrayManager;
        $this->categoriesOptions = $categoriesOptions;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        $fieldPath = $this->arrayManager->findPath('category_ids', $meta, null, 'children');

        if (!$fieldPath) {
            return $meta;
        }

        return $this->arrayManager->merge(
            $fieldPath . '/arguments/data/config',
            $meta,
            [
                'options' => $this->categoriesOptions->toOptionArray(),
                'multiple' => true,
                'lastSelectable' => false,
                'showCheckbox' => true,
                'selectType' => 'tree',
                'dataType' => 'array',
            ]
        );
    }
}
