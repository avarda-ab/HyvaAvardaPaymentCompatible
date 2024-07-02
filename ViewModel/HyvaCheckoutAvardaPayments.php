<?php

namespace Avarda\HyvaAvardaPaymentCompatible\ViewModel;

use Avarda\Payments\Model\GetAprWidgetHtml;
use Avarda\Payments\Model\Ui\Invoice\ConfigProvider as InvoiceConfigProvider;
use Avarda\Payments\Model\Ui\DirectInvoice\ConfigProvider as DirectInvoiceConfigProvider;
use Avarda\Payments\Model\Ui\Loan\ConfigProvider as LoanConfigProvider;
use Avarda\Payments\Model\Ui\PartPayment\ConfigProvider as PartPaymentConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class HyvaCheckoutAvardaPayments implements ArgumentInterface
{
    protected ConfigProviderInterface $invoice;
    protected ConfigProviderInterface $directInvoice;
    protected ConfigProviderInterface $loan;
    protected ConfigProviderInterface $partPayment;
    protected GetAprWidgetHtml $getAprWidgetHtml;
    protected SecureHtmlRenderer $secureHtmlRenderer;
    protected Escaper $escaper;

    public function __construct(
        ConfigProviderInterface $avardaInvoiceProvider,
        ConfigProviderInterface $avardaDirectInvoiceProvider,
        ConfigProviderInterface $avardaLoanProvider,
        ConfigProviderInterface $avardaPartPaymentProvider,
        GetAprWidgetHtml $getAprWidgetHtml,
        SecureHtmlRenderer $secureHtmlRenderer,
        Escaper $escaper
    ) {
        $this->invoice = $avardaInvoiceProvider;
        $this->directInvoice = $avardaDirectInvoiceProvider;
        $this->loan = $avardaLoanProvider;
        $this->partPayment = $avardaPartPaymentProvider;
        $this->getAprWidgetHtml = $getAprWidgetHtml;
        $this->secureHtmlRenderer = $secureHtmlRenderer;
        $this->escaper = $escaper;
    }

    public function getTermsHtml($method): string
    {
        $instructor = $this->getInstructor($method);
        if (!$instructor) {
            return '';
        }
        $config = $this->$instructor->getConfig()['payment']['instructions'][$method] ?? [];
        if ($config['apr_widget']['enabled']) {
            $html = $this->getAprWidgetHtml->execute($method);
            $html .= $this->secureHtmlRenderer->renderTag(
                'script',
                ['type' => 'text/javascript'],
                'let s = document.createElement("script");' .
                 's.src = "' . $config['apr_widget']['url'] . '";' .
                 's.type = "text/javascript";' .
                 's.dataset.paymentId = "' . $this->escaper->escapeJs($config['apr_widget']['paymentId']) . '";' .
                 's.dataset.widgetJwt = "' . $this->escaper->escapeJs($config['apr_widget']['widgetJwt']) . '";' .
                 's.dataset.customStyles = "' . $this->escaper->escapeJs($config['apr_widget']['styles']) . '";' .
                 'document.head.appendChild(s);',
                false
            );
        } else {
            $html = '<span class="instructions">' . $config['instructions'] . '</span><br><br>';
            $html .= '<span class="text-xs">' .
                    $config['terms_text'] .
                    ' <a href="' . $config['terms_link'] . '" target="_blank" rel="noopener">(' . __('Terms') . ')</a>' .
                '</span>';
        }
        return $html;
    }

    public function getInstructor($method): string
    {
        return match ($method) {
            InvoiceConfigProvider::CODE => 'invoice',
            DirectInvoiceConfigProvider::CODE => 'directInvoice',
            LoanConfigProvider::CODE => 'loan',
            PartPaymentConfigProvider::CODE => 'partPayment',
            default => '',
        };
    }
}
