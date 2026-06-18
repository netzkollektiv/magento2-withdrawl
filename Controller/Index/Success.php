<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Success implements HttpGetActionInterface
{
    private $pageFactory;
    private $redirectFactory;
    private $customerSession;

    public function __construct(
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        CustomerSession $customerSession
    ) {
        $this->pageFactory = $pageFactory;
        $this->redirectFactory = $redirectFactory;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        // The order id is read from the session (set on successful submission),
        // never from the URL, so the page cannot be enumerated.
        $orderId = (int) $this->customerSession->getWithdrawalSuccessOrderId();
        if (!$orderId) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('/');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Withdrawal Submitted Successfully'));
        return $page;
    }
}
