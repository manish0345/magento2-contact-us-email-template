<?php
namespace SimplifiedMagento\ContactUs\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class Submit extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    protected $_resultRedirectFactory;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Escaper $escaper
    ) {
        parent::__construct($context);
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_escaper = $escaper;
        $this->_resultRedirectFactory=$context->getResultRedirectFactory();
    }

    /**
     * Post user question
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $resultRedirect = $this->_resultRedirectFactory->create();
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('*/*/');
            return;
        }
        try {

            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $is_enabled = $this->scopeConfig->getValue('Contactsection/ContactGroup/IsEnabled', $storeScope);
            $url = $this->storeManager->getStore()->getBaseUrl();
            $enabled_link = '';
            if($is_enabled == '1') {
                $enabled_link = $url;
            }
            $post['site_link'] = $enabled_link;

            $emailTo = $this->scopeConfig->getValue('Contactsection/ContactGroup/ToField', $storeScope);
            $emailTo = ($emailTo != '') ? explode(",", $emailTo) : '';
            $emailCc = $this->scopeConfig->getValue('Contactsection/ContactGroup/CcField', $storeScope);
            $emailCc = ($emailCc != '') ? explode(",", $emailCc) : '';
            $emailBcc = $this->scopeConfig->getValue('Contactsection/ContactGroup/BccField', $storeScope);
            $emailBcc = ($emailBcc != '') ? explode(",", $emailBcc) : '';

            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($post);
            $error = false;

            $sender = [
                'name' => $this->_escaper->escapeHtml($post['firstname'].' '.$post['lastname']),
                'email' => $this->_escaper->escapeHtml($post['email']),
            ];

            $transport = $this->_transportBuilder->setTemplateIdentifier('send_email_custom_template') // this code we have mentioned in the email_templates.xml
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, // this is using frontend area to get the template file
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
                ->setTemplateVars(['data' => $postObject])
                ->setFrom($sender)
                ->addTo($emailTo)
                //->addCc($emailCc)
                //->addBcc($emailBcc)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->messageManager->addSuccess(__('Your data sent successfully.'));
            return $resultRedirect->setPath('*/*/');
        }catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->messageManager->addError(__('There is some error, Try again.'.$e->getMessage()));
            return $resultRedirect->setPath('*/*/');
        }
    }


}

?>