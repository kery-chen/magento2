<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NewRelicReporting\Model\Observer;

use Magento\NewRelicReporting\Model\Config;

/**
 * Class ReportConcurrentUsers
 */
class ReportConcurrentUsers
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\NewRelicReporting\Model\UsersFactory
     */
    protected $usersFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * Constructor
     *
     * @param Config $config
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\NewRelicReporting\Model\UsersFactory $usersFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        Config $config,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\NewRelicReporting\Model\UsersFactory $usersFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->usersFactory = $usersFactory;
        $this->jsonEncoder = $jsonEncoder;
        $this->dateTime = $dateTime;
    }

    /**
     * Reports concurrent users to the database reporting_users table
     *
     * @return \Magento\NewRelicReporting\Model\Observer\ReportConcurrentUsers
     */
    public function execute()
    {
        if ($this->config->isNewRelicEnabled()) {
            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());

                $jsonData = [
                    'id' => $customer->getId(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'store' => $this->storeManager->getStore()->getName(),
                    'website' => $this->storeManager->getWebsite()->getName(),
                ];

                $modelData = [
                    'type' => 'user_action',
                    'action' => $this->jsonEncoder->encode($jsonData),
                    'updated_at' => $this->dateTime->formatDate(true),
                ];

                /** @var \Magento\NewRelicReporting\Model\Users $usersModel */
                $usersModel = $this->usersFactory->create();
                $usersModel->setData($modelData);
                $usersModel->save();
            }
        }

        return $this;
    }
}