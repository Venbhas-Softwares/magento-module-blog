<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Comment;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Venbhas\Article\Model\Comment as CommentModel;
use Venbhas\Article\Model\Comment\Form\Modifier\DisableAuthorFields;
use Venbhas\Article\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;

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

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CommentCollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        DisableAuthorFields $disableAuthorFieldsModifier,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->disableAuthorFieldsModifier = $disableAuthorFieldsModifier;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta(): array
    {
        return $this->disableAuthorFieldsModifier->modifyMeta(parent::getMeta());
    }

    public function getData(): array
    {
        if ($this->loadedData !== [] && $this->loadedData !== null) {
            return $this->loadedData;
        }

        $id = $this->request->getParam($this->getRequestFieldName());
        $persistorData = $this->dataPersistor->get('venbhas_article_comment');

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
                $this->dataPersistor->clear('venbhas_article_comment');
            }
            $this->loadedData[''] = $defaults;
            $this->loadedData[0] = $defaults;
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter($this->getPrimaryFieldName(), (int) $id);
        foreach ($this->collection->getItems() as $comment) {
            $row = $comment->getData();
            $this->loadedData[$comment->getId()] = $row;
        }

        return $this->loadedData;
    }
}
