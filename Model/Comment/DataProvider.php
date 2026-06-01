<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Comment;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Venbhas\Blog\Model\Comment as CommentModel;
use Venbhas\Blog\Model\Comment\Form\Modifier\DisableAuthorFields;
use Venbhas\Blog\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;

/**
 * Comment form data provider.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array */
    protected $loadedData = [];

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var RequestInterface */
    private $request;

    /** @var DisableAuthorFields */
    private $disableAuthorFieldsModifier;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var Escaper */
    private $escaper;

    private const ARTICLE_EDIT_URL = 'blog/article/edit';

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CommentCollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param DisableAuthorFields $disableAuthorFieldsModifier
     * @param ResourceConnection $resourceConnection
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CommentCollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        DisableAuthorFields $disableAuthorFieldsModifier,
        ResourceConnection $resourceConnection,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->disableAuthorFieldsModifier = $disableAuthorFieldsModifier;
        $this->resourceConnection = $resourceConnection;
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get meta with author fields disabled when editing.
     *
     * @return array
     */
    public function getMeta(): array
    {
        return $this->disableAuthorFieldsModifier->modifyMeta(parent::getMeta());
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->loadedData !== [] && $this->loadedData !== null) {
            return $this->loadedData;
        }

        $id = $this->request->getParam($this->getRequestFieldName());
        $persistorData = $this->dataPersistor->get('venbhas_blog_comment');

        if (!$id) {
            $defaults = !empty($persistorData)
                ? $persistorData
                : [
                    'comment_id' => null,
                    'article_id' => '',
                    'user_name' => '',
                    'user_email' => '',
                    'comment' => '',
                    'reply' => '',
                    'status' => CommentModel::STATUS_PENDING,
                ];
            if (!empty($persistorData)) {
                $this->dataPersistor->clear('venbhas_blog_comment');
            }
            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
        foreach ($this->collection->getItems() as $comment) {
            $row = $comment->getData();
            $row['article_link'] = $this->buildArticleLinkHtml((int) ($row['article_id'] ?? 0));
            $this->loadedData[$comment->getId()] = $row;
        }

        return $this->loadedData;
    }

    private function buildArticleLinkHtml(int $articleId): string
    {
        if ($articleId <= 0) {
            return '';
        }

        $title = $this->fetchArticleTitle($articleId);
        $label = $this->escaper->escapeHtml($title !== '' ? $title : (string) $articleId);
        $url = $this->urlBuilder->getUrl(self::ARTICLE_EDIT_URL, ['article_id' => $articleId]);

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            $this->escaper->escapeUrl($url),
            $label
        );
    }

    private function fetchArticleTitle(int $articleId): string
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('venbhas_article');
            $select = $connection->select()
                ->from($table, ['title'])
                ->where('article_id = ?', $articleId)
                ->limit(1);
            $title = $connection->fetchOne($select);
            return is_string($title) ? $title : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
