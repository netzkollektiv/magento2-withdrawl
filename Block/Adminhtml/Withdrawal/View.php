<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Adminhtml\Withdrawal;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class View extends Template
{
    private $withdrawalRepository;
    private $orderRepository;
    private $timezone;

    /** @var \Zwernemann\Withdrawal\Model\Withdrawal|null */
    private $withdrawal = null;

    public function __construct(
        Context $context,
        WithdrawalRepository $withdrawalRepository,
        OrderRepositoryInterface $orderRepository,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->withdrawalRepository = $withdrawalRepository;
        $this->orderRepository = $orderRepository;
        $this->timezone = $timezone;
    }

    public function getWithdrawal()
    {
        if ($this->withdrawal === null) {
            $id = (int) $this->getRequest()->getParam('id');
            try {
                $this->withdrawal = $this->withdrawalRepository->getById($id);
            } catch (\Exception $e) {
                $this->withdrawal = false;
            }
        }
        return $this->withdrawal ?: null;
    }

    public function getWithdrawalItems(): array
    {
        $withdrawal = $this->getWithdrawal();
        if (!$withdrawal) {
            return [];
        }
        return $this->withdrawalRepository->getItemsByWithdrawalId((int) $withdrawal->getId());
    }

    public function getOrder()
    {
        $withdrawal = $this->getWithdrawal();
        if (!$withdrawal) {
            return null;
        }
        try {
            return $this->orderRepository->get((int) $withdrawal->getData('order_id'));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getOrderViewUrl(): string
    {
        $withdrawal = $this->getWithdrawal();
        if (!$withdrawal) {
            return '';
        }
        return $this->getUrl('sales/order/view', ['order_id' => $withdrawal->getData('order_id')]);
    }

    /**
     * Returns the shipments of the related order including links to the shipment
     * view and the packing slip PDF (used by warehouse staff to verify returns).
     *
     * @return array<int, array{increment_id: string, view_url: string, packing_slip_url: string}>
     */
    public function getShipments(): array
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }

        $shipments = [];
        foreach ($order->getShipmentsCollection() as $shipment) {
            $shipmentId = (int) $shipment->getId();
            $shipments[] = [
                'increment_id'     => (string) $shipment->getIncrementId(),
                'view_url'         => $this->getUrl(
                    'adminhtml/order_shipment/view',
                    ['shipment_id' => $shipmentId]
                ),
                'packing_slip_url' => $this->getUrl(
                    'adminhtml/order_shipment/printAction',
                    ['shipment_id' => $shipmentId]
                ),
            ];
        }

        return $shipments;
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('withdrawal/index/index');
    }

    public function formatWithdrawalDate(string $date): string
    {
        try {
            // Stored values are UTC; convert to the configured (admin) timezone so the
            // detail view matches the grid, which uses Magento's date UI component.
            return $this->timezone->date(new \DateTime($date))->format('d.m.Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }

    public function getStatusLabel(string $status): string
    {
        $labels = [
            'pending'   => __('Pending'),
            'confirmed' => __('Confirmed'),
            'rejected'  => __('Rejected'),
        ];
        return (string) ($labels[$status] ?? $status);
    }
}
