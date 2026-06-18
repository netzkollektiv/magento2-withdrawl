<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Success extends Template
{
    private $orderRepository;
    private $customerSession;
    private $order;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
    }

    public function getOrder()
    {
        if ($this->order === null) {
            // Bound to the session set on submission, not to a URL parameter.
            $orderId = (int) $this->customerSession->getWithdrawalSuccessOrderId();
            if ($orderId) {
                try {
                    $this->order = $this->orderRepository->get($orderId);
                } catch (\Exception $e) {
                    $this->order = false;
                }
            }
        }
        return $this->order ?: null;
    }

    public function getOrderHistoryUrl(): string
    {
        return $this->getUrl('sales/order/history');
    }

    public function getHomeUrl(): string
    {
        return $this->getUrl('/');
    }
}
