<?php

namespace Imagina\Envioclick\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Custom shipping model
 */
class Customshipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'envioclick';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;

     /**
     * @var \Imagina\Envioclick\Logger\Logger
     */
    protected $loggerC;

    protected $baseUrlApi;


    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     * @param \Imagina\Envioclick\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = [],
        \Imagina\Envioclick\Logger\Logger $loggerC
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->loggerC = $loggerC;
        $this->baseUrlApi = "https://api.envioclickpro.com.co/api/v1/quotation";
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Get Config data
        $originCode = $this->getConfigData('originCode');
        $originAddresse = $this->getConfigData('originAddresse');
        $apiKey = $this->getConfigData('apiKey');

        if($apiKey=="null" || empty($apiKey) || empty($originCode)){
            return false;
        }
            
        // Get Infor Destino
        $inforDest = $this->getDestInfo($request);

        if(empty($inforDest['postcode'])){
            return false;
        }
            
        // Get All Infor Car
        $inforCart = $this->getAllInforCart($request->getAllItems());

        $totalWeight =  round($request->getPackageWeight(),2);
        if($this->getWeightUnit()=="lbs")
            $totalWeight = round($totalWeight * 0.45,2);
            

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        
        $package = array();
        $package["description"] = "Request to quotation";
        $package["contentValue"] = $inforCart['price'];
        $package["weight"] = $totalWeight;
        $package["length"] = $inforCart['length'];
        $package["height"] = $inforCart['height'];
        $package["width"] = $inforCart['width'];

        $formData = array();
        $formData["package"] = $package;
        $formData["originCode"] = $originCode;
        if(!empty($originAddresse))
            $formData["originAddresse"] = $originAddresse;

        $formData["destinationCode"] = $inforDest['postcode'];
        if(!empty($inforDest['street']))
            $formData["destinationAddress"] = $inforDest['street'];

        try {

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $this->baseUrlApi,[
                'headers' => ['AuthorizationKey' => $apiKey],
                'form_params' => $formData
            ]);
           
            if($response){

                // Check response
                //$resBody = $response->getBody();$data = (string) $resBody;$this->loggerC->info($data);
              
                $this->loggerC->info("StatusCode: ".$response->getStatusCode());
                $res= json_decode($response->getBody());

                if($res->status_codes[0]==200){
                    $rates = $res->data->rates;
                    foreach($rates as $rate){

                         /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
                        $rateMag = $this->rateMethodFactory->create();

                        $rateMag->setCarrier(($this->_code));
                        $rateMag->setCarrierTitle($this->getConfigData('title'));

                        //$rateMag->setCarrier($rate->carrier);
                        //$rateMag->setCarrierTitle($rate->carrier);
                        
                        $methodTitle = $rate->carrier." - ".$rate->product;

                        $rateMag->setMethod($methodTitle);
                        $rateMag->setMethodTitle($methodTitle);
            
                        $rateMag->setCost($rate->publicPrice);
                        $rateMag->setPrice($rate->publicPrice);
            
                        $result->append($rateMag);

                    }
                }//If
            }// If Response

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            

            $this->loggerC->critical($e->getMessage());

            if($this->getConfigData('showErrorsFrontend')){
              
                /*
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($e->getMessage());
                */

                $responseBody = $e->getResponse()->getBody(true);
                $re = json_decode($responseBody);

                $msjError= "";
                foreach($re->status_messages->error as $key => $error){
                    $msjError = $msjError." - ".$error[0];
                }
            
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(__("Error EnvioClick:".$msjError));

                return $error;
            }

        }
       
        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @return string
     */
    public function getWeightUnit()
    {
        return $this->_scopeConfig->getValue(
            'general/locale/weight_unit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return array
     */
    public function getDestInfo($request){

        $info = array(
            'city' => $request->getDestCity(), 
            'company' => $request->getDestCompany(), 
            'country_id' => $request->getDestCountryId(), 
            'firstname' => $request->getDestFirstname(), 
            'lastname' => $request->getDestLastname(), 
            'postcode' => $request->getDestPostcode(), 
            'region' => $request->getDestRegion(), 
            'region_code' => $request->getDestRegionCode(), 
            'region_id' => $request->getDestRegionId(), 
            'street' => $request->getDestStreet(), 
            'telphone' => $request->getDestTelphone()
        );

        return $info;
    }

    /**
     * @return array
     */
    public function getAllInforCart($items){
        
        $tWidth = 0;$tHeight = 0;$tLength = 0;
        $tVolumen = 0;
        $tPrice = 0;

        foreach($items as $_item)
        {
        
            $product = $_item->getProduct();

            $tWidth += $product->getTsDimensionsWidth() * $_item->getQty();
            $tHeight += $product->getTsDimensionsHeight() * $_item->getQty();
            $tLength += $product->getTsDimensionsLength() * $_item->getQty();

            if($_item->getParentItem())
                $_item = $_item->getParentItem();

            $tVolumen += (int) $product->getResource()
                    ->getAttributeRawValue($product->getId(),'volumen',$product->getStoreId()) * $_item->getQty();
          
            if($product->getCost())
                $tPrice += $product->getCost() * $_item->getQty();
            else
                $tPrice += $_item->getPrice() * $_item->getQty();

            //$productsNames.= $_item['name'].', ';

        }

        //$productName = rtrim(trim($productName),",");
        
        // Convert to cm
        if($this->getWeightUnit()=="lbs"){
            $tWidth = $tWidth * 2.54;
            $tHeight = $tHeight * 2.54;
            $tLength = $tLength * 2.54;
        }

        $allDimensions = array(
            "width"     => round($tWidth,2), 
            "height"    => round($tHeight,2), 
            "length"    => round($tLength,2),
            "volumen"   => round($tVolumen,2),
            "price"     => round($tPrice,2)
        );

        return $allDimensions;

    }

}
