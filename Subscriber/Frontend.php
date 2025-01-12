<?php
namespace VOPDebitConnect\Subscriber;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
require_once(__DIR__.'/../Components/BoniGateway/class.EAP-BoniGateway.php');

class Frontend implements  SubscriberInterface
{
    public static function getSubscribedEvents() {

        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => array('onPostDispatch',300000),
        ];
    }

    public function getBoniGatewaySettings(\Enlight_Controller_ActionEventArgs $arguments){
        try
        {
            $settings = array();
            $shop = Shopware()->Shop()->getMain() !== null ? Shopware()->Shop()->getMain() : Shopware()->Shop();
            $selectedShop = $shop->getId();

            $entrys =  Shopware()->Db()->fetchall("SELECT art,datavalue from dc_gatewaymeta where nType = 0 and shopID = ".$selectedShop);
            foreach($entrys as $entry){
                $settings[$entry['art']] = is_object(json_decode($entry['datavalue'])) ? json_decode($entry['datavalue'],true) : $entry['datavalue'];
            }

            return $settings;
        }catch(Exception $e){

            return null;
        }
    }



    // REDIRECT TO PAYMENT WALL
    public function redirectToPayentWall(\Enlight_Controller_ActionEventArgs $arguments,$EAPBoniGateway = null)
    {
        if($EAPBoniGateway!=null) $this->writeBoniGatewaySession($EAPBoniGateway);

        $request = $arguments->getSubject()->Request();
        $response = $arguments->getSubject()->Response();
        $controller = $request->getControllerName();
        $action = $arguments->getRequest()->getActionName();
        $target = $request->getParam('sTarget');
        $targetAction = $request->getParam('sTargetAction');
        $view = $arguments->getSubject()->View();
        $userId = Shopware()->Session()->sUserId;
        $arguments->getSubject()->redirect(
            array(
                'controller'    => 'checkout',
                'action'        => 'shippingPayment'
            ));

    }

    public function redirectToConfirmPage(\Enlight_Controller_ActionEventArgs $arguments,$EAPBoniGateway = null)
    {
        if($EAPBoniGateway!=null) $this->writeBoniGatewaySession($EAPBoniGateway);
        $request = $arguments->getSubject()->Request();
        $response = $arguments->getSubject()->Response();
        $controller = $request->getControllerName();
        $action = $arguments->getRequest()->getActionName();
        $target = $request->getParam('sTarget');
        $targetAction = $request->getParam('sTargetAction');
        $view = $arguments->getSubject()->View();
        $userId = Shopware()->Session()->sUserId;
        $arguments->getSubject()->redirect(array(
            'controller'    => 'checkout',
            'action'        => 'confirm'
        ));

    }
    /**
     * Handles payment logos and payment title in Frontend
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatch(\Enlight_Controller_ActionEventArgs $arguments) {

        $request = $arguments->getSubject()->Request();
        $response = $arguments->getSubject()->Response();
        $controller = $request->getControllerName();
        $action = $arguments->getRequest()->getActionName();
        $target = $request->getParam('sTarget');
        $targetAction = $request->getParam('sTargetAction');
        $view = $arguments->getSubject()->View();
        $userId = Shopware()->Session()->sUserId;

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }

        $gatewaySettings = $this->getBoniGatewaySettings($arguments);

        if($gatewaySettings == null || (strlen($gatewaySettings['username'])<1 && strlen($gatewaySettings['passwd'])<1)){
            return;
            // CANT LOAD SETTINGS, OR AUTHDATA NOT SET =>  RETURN
        }

        //$getPaymentWithoutId = $this->getPaymentWithoutId();

        //Shopware()->Session()->boniGateway = null;


        // ****** CREATING INSTANCE DONT MODIFY HERE ******
        //-------------------------------------------------
        $EAPBoniGateway = new \EAPBoniGateway();
        // GET INSTANCE FROM STORED SESSION
        if(is_object(unserialize(Shopware()->Session()->boniGateway))) $EAPBoniGateway = unserialize(Shopware()->Session()->boniGateway);
        Shopware()->Session()->boniGatewayHandle = $EAPBoniGateway->requestParams["currenthandle"];
        $lastHandle = $EAPBoniGateway->requestParams["currenthandle"];
        $lastBasket = $EAPBoniGateway->requestParams["Warenkorb"];
        //

        $EAPBoniGateway->getCurrentPluginConfig($gatewaySettings,$view,$arguments);



        if($EAPBoniGateway->schufaBoni== null){
            $EAPBoniGateway->schufaBoni = new \EAP_Bonitaetspruefung($EAPBoniGateway->functions) ;
        }
        if($EAPBoniGateway->schufaIdent == null){
            $EAPBoniGateway->schufaIdent = new \EAP_IdentCheck($EAPBoniGateway->functions);
        }


        $EAPBoniGateway->getCurrentRequestParams();
        $currentHandle = $EAPBoniGateway->requestParams["currenthandle"];
        $newBasket = $EAPBoniGateway->requestParams["Warenkorb"];
        // CONFIRMATION , ENABLE ID CHECK ?
        // IF HANDLE HAS CHANGE, CUSTOMER MUST REBEGIN FROM PAYMENTWALL...
        if($lastHandle != $currentHandle){
            // RESET IF HANDLE HAS CHANGED
            // IF OTHER CUSTOMER ADRESS, RESET ALL
            $EAPBoniGateway->schufaIdent->requested = null;
            $EAPBoniGateway->schufaBoni->requested = null;

        }else if($newBasket!=$lastBasket){
            $EAPBoniGateway->current_card = $lastBasket;
            $EAPBoniGateway->changed_card++;
            // IF BASKET HAS CHANGED, JUST GET NEW SCOREINFORMATION
            $EAPBoniGateway->schufaBoni->requested = null;

        }// ENDIF LASTHANDLE != CURRENTHANDLE

        //-------------------------------------------------
        //****** CREATING INSTANCE DONT MODIFY HERE ******

        //if($action=="saveShippingPayment")die();
        if($controller == "checkout" && $action=="cart"){
            if($EAPBoniGateway->identCheckAlwaysOrAttribute()){
                // PRINT NOTIFY AGECHECK TO CART
                $EAPBoniGateway->setNoticeAgeCheck(false);
            }
        }else if ($controller=="checkout" && $action=="finish"){
            $sess =  Shopware()->Session();
            $sOrderVariables = $sess['sOrderVariables']->getArrayCopy();

            $sOrderNumber = $sOrderVariables["sOrderNumber"];
            if(strlen($sOrderNumber)>0){
                $EAPBoniGateway->changed_card = 0;
                $EAPBoniGateway->functions->writeBackPayment($EAPBoniGateway->requestParams,$EAPBoniGateway->schufaBoni->current_id,$EAPBoniGateway->requestParams["Zahlungsart"]->cName,$EAPBoniGateway->settingsArray,$sOrderNumber);
            }

            $EAPBoniGateway->schufaBoni->responseData = null;

        }else if($controller == "checkout" && $action=="shippingPayment"){

            //GET REQUEST PARAMS, CURRENT HANDLE
            $EAPBoniGateway->getCurrentRequestParams();

            if($EAPBoniGateway->schufaBoni->checkEnabled($oPlugin,$EAPBoniGateway->shopware->view,$EAPBoniGateway->settingsArray))
            {

                $EAPBoniGateway->schufaBoni->requestParams = $EAPBoniGateway->requestParams;
                $EAPBoniGateway->setPaymentWallFancyBox($EAPBoniGateway->schufaBoni,$EAPBoniGateway->getCurrentHandle(false));
            }
            // DISABLE SHIPPING METHODS
            if($EAPBoniGateway->identCheckAlwaysOrAttribute() && $EAPBoniGateway->disableShippingMethods()>0){
                $EAPBoniGateway->setNoticeAgeCheck(false);
            }

        }else if($controller == "checkout" && $action == "confirm"){


            $settingsIdentCheck = $EAPBoniGateway->settingsArray['jtl_eap_identcheck_use'];

            if($EAPBoniGateway->schufaBoni->checkEnabled($oPlugin,$EAPBoniGateway->smarty,$EAPBoniGateway->settingsArray))
            {


                $EAPBoniGateway->schufaBoni->requestParams = $EAPBoniGateway->requestParams;
                if($EAPBoniGateway->functions->istGesperrt($EAPBoniGateway->requestParams["Zahlungsart"]->kZahlungsart,$EAPBoniGateway->settingsArray['boniPayments'])
                    && $EAPBoniGateway->requestParams['art'] == "B2C"  &&
                    ($EAPBoniGateway->requestParams['Rechnungsadresse']->dGeburtstag == "00.00.0000" || $EAPBoniGateway->requestParams['Rechnungsadresse']->dGeburtstag == date("d.m.Y"))){
                    // DOB NOT SET, REDIRECT PAYMENTWALL
                    if($EAPBoniGateway->settingsArray['b2c_birthday']>0) {
                        $this->redirectToPayentWall($arguments, $EAPBoniGateway);
                        return;
                    }
                }

                $EAPBoniGateway->schufaBoni->doRequest($currentHandle);
                if($EAPBoniGateway->schufaBoni->requested && $EAPBoniGateway->schufaBoni->responseData->secure_payment
                    && $EAPBoniGateway->functions->istGesperrt($EAPBoniGateway->requestParams["Zahlungsart"]->kZahlungsart,$EAPBoniGateway->settingsArray['boniPayments']))
                {

                    $this->redirectToPayentWall($arguments,$EAPBoniGateway);
                    return;
                }
            }



            if($EAPBoniGateway->identCheckAlwaysOrAttribute() && $EAPBoniGateway->schufaIdent->checkEnabled($oPlugin,$EAPBoniGateway->smarty,$EAPBoniGateway->settingsArray,$EAPBoniGateway->requestParams)){

                if(in_array($EAPBoniGateway->versandArt	,$EAPBoniGateway->settingsArray['identShipping'])){
                    // WENN STANDARDVERSANRT EINE GESPERRTE IST ZURÜCKLEITEN ZUR PAYMENTWALL..
                    $this->redirectToPayentWall($arguments,$EAPBoniGateway);
                    return;
                }

                if($EAPBoniGateway->functions->getParsedDate($EAPBoniGateway->requestParams['Rechnungsadresse']->dGeburtstag) != "00.00.0000" && $request->getParam("cmd") == "requestIdentCheck")
                {

                    $EAPBoniGateway->schufaIdent->requestParams = $EAPBoniGateway->requestParams;
                    $EAPBoniGateway->schufaIdent->doRequest($EAPBoniGateway->requestParams);
                    $this->redirectToConfirmPage($arguments,$EAPBoniGateway);
                    return;
                }

            }//ENDIF CHECKENABLED SCHUFAIDENT
            //
            // FINALY WRITE DOWN SESSION
            // SERIALIZE CLASSES TO SESSION WITHOUT PDO INSTANCE
            $EAPBoniGateway->RemovePaymentWallSetIdentCheckWall();
            $this->writeBoniGatewaySession($EAPBoniGateway);
        }

        $this->writeBoniGatewaySession($EAPBoniGateway);
        $this->addTemplateDir($view);
    }

    // WRITEBACK SERIALIZED CLASS TO SHOPWARE SESSION AND REMOVE PDO INSTANCES
    public function writeBoniGatewaySession($EAPBoniGateway){
        $EAPBoniGateway->shopware = null;
        $EAPBoniGateway->smarty = null;
        $EAPBoniGateway->schufaBoni->smarty = null;
        $EAPBoniGateway->schufaIdent->smarty = null;
        Shopware()->Session()->boniGateway = serialize($EAPBoniGateway);
    }


    /**
     * @param $view
     */
    private function addTemplateDir($view) {
        //
        // Templates ueberschreiben
        //
        $view->addTemplateDir(__DIR__ . '/../Views/');
    }

}