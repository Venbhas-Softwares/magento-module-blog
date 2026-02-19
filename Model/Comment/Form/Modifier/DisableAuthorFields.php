<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Comment\Form\Modifier;

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

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param ArrayManager $arrayManager
     */
    public function __construct(RequestInterface $request, ArrayManager $arrayManager)
    {
        $this->request = $request;
        $this->arrayManager = $arrayManager;
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

        // Remove editable Article, User Name, User Email from form when editing (marked fields in screenshot)
        $meta = $this->arrayManager->remove('general/children/article_id', $meta);
        $meta = $this->arrayManager->remove('general/children/user_name', $meta);
        $meta = $this->arrayManager->remove('general/children/user_email', $meta);

        // Hidden field to preserve article_id on submit
        $hiddenArticleId = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'field',
                        'formElement' => 'input',
                        'dataType' => 'text',
                        'dataScope' => 'article_id',
                        'visible' => false,
                        'sortOrder' => 19,
                    ],
                ],
            ],
        ];
        $meta = $this->arrayManager->set('general/children/article_id_hidden', $meta, $hiddenArticleId);

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
