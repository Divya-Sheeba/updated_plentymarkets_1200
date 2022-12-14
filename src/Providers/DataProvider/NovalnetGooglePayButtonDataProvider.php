<?php
/**
 * This file is used for displaying the Google Pay button
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet
 * @license      https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
namespace Novalnet\Providers\DataProvider;

use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Novalnet\Services\SettingsService;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Helper\Services\WebstoreHelper;

/**
 * Class NovalnetGooglePayButtonDataProvider
 *
 * @package Novalnet\Providers\DataProvider
 */
class NovalnetGooglePayButtonDataProvider
{
    /**
     * Display the Google Pay button
     *
     * @param Twig $twig
     * @param BasketRepositoryContract $basketRepository
     * @param CountryRepositoryContract $countryRepository
     * @param Arguments $arg
     *
     * @return string
     */
    public function call(Twig $twig,
                         BasketRepositoryContract $basketRepository,
                         CountryRepositoryContract $countryRepository,
                         WebstoreHelper $webstoreHelper,
                         $arg)
    {
        $basket             = $basketRepository->load();
        $paymentHelper      = pluginApp(PaymentHelper::class);
        $sessionStorage     = pluginApp(FrontendSessionStorageFactoryContract::class);
        $paymentService     = pluginApp(PaymentService::class);
        $settingsService    = pluginApp(SettingsService::class);

        $orderAmount = 0;
        if(!empty($basket->basketAmount)) {
            // Get the order total basket amount
            $orderAmount = $paymentHelper->convertAmountToSmallerUnit($basket->basketAmount);
        }
        // Get the Payment MOP Id
        $paymentMethodDetails = $paymentHelper->getPaymentMethodByKey('NOVALNET_GOOGLEPAY');
        // Get the order language
        $orderLang = strtoupper($sessionStorage->getLocaleSettings()->language);
        // Get the countryCode
        $billingAddress = $paymentHelper->getCustomerAddress((int) $basket->customerInvoiceAddressId);
        // Get the seller name from the shop configuaration
        $sellerName = $settingsService->getPaymentSettingsValue('business_name', 'novalnet_googlepay');
        $name = $webstoreHelper->getCurrentWebstoreConfiguration()->name;
        $paymentService->logger('store', $name);
        // Required details for the Google Pay button
        $googlePayData = [
                            'clientKey'           => trim($settingsService->getPaymentSettingsValue('novalnet_client_key')),
                            'merchantId'          => $settingsService->getPaymentSettingsValue('payment_active', 'novalnet_googlepay'),
                            'sellerName'          => !empty($sellerName) ? $sellerName : $webstoreHelper->getCurrentWebstoreConfiguration()->name,
                            'enforce'             => $settingsService->getPaymentSettingsValue('enforce', 'novalnet_googlepay'),
                            'buttonType'          => $settingsService->getPaymentSettingsValue('button_type', 'novalnet_googlepay'),
                            'buttonTheme'         => $settingsService->getPaymentSettingsValue('button_theme', 'novalnet_googlepay'),
                            'buttonHeight'        => $settingsService->getPaymentSettingsValue('button_height', 'novalnet_googlepay'),
                            'testMode'            => ($settingsService->getPaymentSettingsValue('test_mode', 'novalnet_googlepay') == true) ? 'SANDBOX' : 'PRODUCTION'
                         ];
        // Render the Google Pay button
       return $twig->render('Novalnet::PaymentForm.NovalnetGooglePayButton',
                                    [
                                        'paymentMethodId' => $paymentMethodDetails[0],
                                        'nnPaymentProcessUrl' => $paymentService->getProcessPaymentUrl(),
                                        'orderLang'           => $orderLang,
                                        'orderCurrency'       => $basket->currency,
                                        'countryCode'         => $countryRepository->findIsoCode($billingAddress->countryId, 'iso_code_2'),
                                        'orderAmount'         => $orderAmount,
                                        'googlePayData' => $googlePayData
                                    ]);
    }
}
