<?php

declare(strict_types=1);

namespace Perspective\Ukrposhta\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

/**
 * Ukrposhta Express shipping model
 *
 */
class UkrposhtaExpress extends AbstractCarrier implements CarrierInterface
{
    private const CARRIER_CODE = 'ukrposhta';
    private const METHOD_CODE  = 'express';
    public const DELIVERY_METHOD = self::CARRIER_CODE . '_' . self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var ResultFactory
     */
    private $rateResultFactory;


    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param MethodFactory $rateMethodFactory
     * @param ResultFactory $rateResultFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        MethodFactory $rateMethodFactory,
        ResultFactory $rateResultFactory,
        array $data = [])
    {
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateResultFactory = $rateResultFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritDoc
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return null;
        }

        $shippingPrice = (int)$this->getConfigData('price');

        $result = $this->rateResultFactory->create();

        $method = $this->createResultMethod($shippingPrice);
        $result->append($method);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @inheritDoc
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Get tracking information
     *
     * @param string $tracking
     * @return array
     */
    public function getTrackingInfo($tracking)
    {
        return [
            'title' => $this->getConfigData('title'),
            'number' => $tracking,
        ];
    }

    /**
     * @inheritDoc
     */
    public function isCityRequired()
    {
        return true;
    }

    /**
     * Create rate object based on shipping price.
     *
     * @param float $shippingPrice
     * @return Method
     */
    private function createResultMethod(float $shippingPrice): Method
    {
        $store = $this->getStore();

        if ($store instanceof StoreInterface) {
            $store = $store->getId();
        }

        $method = $this->rateMethodFactory->create(
            [
                'data' => [
                    'carrier' => self::CARRIER_CODE,
                    'carrier_title' => $this->getConfigData('title'),
                    'method' => self::METHOD_CODE,
                    'method_title' => $this->getConfigData('name'),
                    'cost' => $shippingPrice
                ]
            ]
        );

        $method->setPrice($shippingPrice);

        return $method;
    }
}
