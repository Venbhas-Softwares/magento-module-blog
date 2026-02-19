<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Article;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Article::article_save';

    /** @var UploaderFactory */
    private $uploaderFactory;

    /** @var Filesystem */
    private $filesystem;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Execute action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = ['error' => true, 'message' => __('File cannot be uploaded.')];

    // This must match the dataScope in your UI component
        $fileId = $this->getRequest()->getParam('param_name', 'featured_image');

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg','jpeg','gif','png','webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);

            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $path = $mediaDir->getAbsolutePath('venbhas/article');

            $uploadResult = $uploader->save($path);

            if (!empty($uploadResult['file'])) {
                $relativePath = 'venbhas/category' . $uploadResult['file']; // include dispersion path
                $baseMediaUrl = $this->_url->getBaseUrl(
                    ['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]
                );
                $result = [
                    'name' => $uploadResult['name'],
                    'url' => $baseMediaUrl . $relativePath,
                ];
            }

        } catch (LocalizedException $e) {
            $result['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $result['message'] = $e->getMessage();
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
