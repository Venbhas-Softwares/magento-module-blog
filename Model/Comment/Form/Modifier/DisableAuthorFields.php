<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Comment\Form\Modifier;

use Magento\Framework\Escaper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Disable author fields when editing comment.
 */
class DisableAuthorFields implements ModifierInterface
{
    /** @var RequestInterface */
    private $request;

    /** @var ArrayManager */
    private $arrayManager;

    /** @var Escaper */
    private $escaper;

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param ArrayManager $arrayManager
     * @param Escaper $escaper
     */
    public function __construct(RequestInterface $request, ArrayManager $arrayManager, Escaper $escaper)
    {
        $this->request = $request;
        $this->arrayManager = $arrayManager;
        $this->escaper = $escaper;
    }

    /**
     * Modify meta to disable author fields when editing.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        $commentId = (int) $this->request->getParam('comment_id');
        if ($commentId <= 0) {
            return $meta;
        }

        // When editing: linked article is read-only and listed first.
        $meta = $this->removeFieldByName($meta, 'article_id');
        if (!$this->arrayManager->findPath('article_link', $meta, null, 'children')) {
            $meta = $this->addArticleLinkField($meta);
        } else {
            $meta = $this->setFieldConfigValue($meta, 'article_link', 'visible', true);
            $meta = $this->setFieldConfigValue($meta, 'article_link', 'sortOrder', 1);
        }

        // Comment: keep single Comment field but make it disabled when editing
        foreach (['/arguments/data/config', '/data/config'] as $suffix) {
            $configPath = 'content/children/comment' . $suffix;
            if ($this->arrayManager->exists($configPath, $meta)) {
                $meta = $this->arrayManager->set($configPath . '/disabled', $meta, true);
                break;
            }
        }

        return $meta;
    }

    /**
     * Remove a form field from UI meta by name.
     *
     * @param array $meta
     * @param string $fieldName
     * @return array
     */
    private function removeFieldByName(array $meta, string $fieldName): array
    {
        $path = $this->arrayManager->findPath($fieldName, $meta, null, 'children');
        return $path ? $this->arrayManager->remove($path, $meta) : $meta;
    }

    /**
     * Set a config value on a form field regardless of meta array shape.
     */
    /**
     * @param mixed $value
     */
    private function setFieldConfigValue(array $meta, string $fieldName, string $key, $value): array
    {
        $fieldPath = $this->arrayManager->findPath($fieldName, $meta, null, 'children');
        if (!$fieldPath) {
            return $meta;
        }

        foreach (['/arguments/data/config/' . $key, '/settings/' . $key] as $suffix) {
            $configPath = $fieldPath . $suffix;
            if ($this->arrayManager->exists($configPath, $meta)) {
                return $this->arrayManager->set($configPath, $meta, $value);
            }
        }

        return $this->arrayManager->set($fieldPath . '/arguments/data/config/' . $key, $meta, $value);
    }

    private function addArticleLinkField(array $meta): array
    {
        $articleLinkField = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'field',
                        'formElement' => 'input',
                        'dataType' => 'text',
                        'label' => __('Article'),
                        'dataScope' => 'article_link',
                        'sortOrder' => 1,
                        'visible' => true,
                        'disabled' => true,
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/html',
                    ],
                ],
            ],
        ];
        $generalChildrenPath = $this->arrayManager->findPath('general', $meta, null, 'children');
        $targetPath = $generalChildrenPath
            ? $generalChildrenPath . '/children/article_link'
            : 'general/children/article_link';

        return $this->arrayManager->set($targetPath, $meta, $articleLinkField);
    }

    /**
     * Modify data (no change).
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data): array
    {
        return $data;
    }
}
