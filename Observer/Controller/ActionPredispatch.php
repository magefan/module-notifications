<?php
/**
 * Copyright © 2017 Magefan (support@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\Notifications\Observer\Controller;

class ActionPredispatch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Check every 10 min
     */
    const TIMEOUT = 600;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $backendSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $date;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $reviewCollectionFactory;

    /**
     * Initialization
     * @param \Magento\Framework\App\Cache\TypeListInterface               $cacheTypeList
     * @param \Magento\Framework\Message\ManagerInterface                  $messageManager
     * @param \Magento\Backend\Model\Auth\Session                          $backendSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                  $date,
     * @param \Magento\Framework\UrlInterface                              $url
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Auth\Session $backendSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\UrlInterface $url,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->messageManager = $messageManager;
        $this->backendSession = $backendSession;
        $this->date = $date;
        $this->url = $url;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if (!$this->backendSession->isLoggedIn()) {
            return; // Isn't logged in
        }

        if ($observer->getRequest()->isXmlHttpRequest()) {
            return; // It's ajax request
        }

        if ($observer->getRequest()->getMethod() == 'POST') {
            return; // It's post request
        }

        $this->checkCacheTypes();
        $this->checkReviews();

    }

    /**
     * Check if cache types are enabled
     * @return void
     */
    protected function checkCacheTypes()
    {
        $disabled = [];
        foreach ($this->cacheTypeList->getTypes() as $cacheType) {
            if (!$cacheType->getStatus()) {
                 $disabled[] = $cacheType->getCacheType();
            }
        }

        if (count($disabled)) {
            $this->messageManager->addNotice(
                __('The following Cache Type(s) are disabled: %1. <a href="%2">Manage Cache</a>.',
                    implode(', ', $disabled),
                    $this->url->getUrl('adminhtml/cache')
                )
            );
        }
    }

    /**
     * Check if any pending review exists
     * @return void
     */
    protected function checkReviews()
    {
        $pendignReview = $this->reviewCollectionFactory->create()
            ->addFieldToFilter('status_id', \Magento\Review\Model\Review::STATUS_PENDING)
            ->setPageSize(1)
            ->getFirstItem();

        if ($pendignReview->getId()) {
            $this->messageManager->addNotice(
                __('Some customer reviews are pending for approval. <a href="%1">Manage Reviews</a>.',
                    $this->url->getUrl('review/product/index')
                )
            );
        }
    }
}
