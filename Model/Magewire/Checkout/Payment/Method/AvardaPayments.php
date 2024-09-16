<?php

namespace Avarda\HyvaAvardaPaymentCompatible\Model\Magewire\Checkout\Payment\Method;

use Avarda\HyvaAvardaPaymentCompatible\ViewModel\HyvaCheckoutAvardaPayments;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\EvaluationResult;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;
use Rakit\Validation\Validator;

class AvardaPayments extends Form implements EvaluationInterface
{
    public ?string $socialSecurityNumber = null;

    protected $loader = [
        'socialSecurityNumber' => 'Saving SSN',
    ];

    protected $rules = [
        'socialSecurityNumber' => 'required',
    ];

    protected $messages = [
        'socialSecurityNumber:required' => 'The SSN is a required field.',
    ];

    protected SessionCheckout $sessionCheckout;
    protected CartRepositoryInterface $quoteRepository;
    protected HyvaCheckoutAvardaPayments $hyvaCheckoutAvardaPayments;

    public function __construct(
        Validator $validator,
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        HyvaCheckoutAvardaPayments $hyvaCheckoutAvardaPayments
    ) {
        parent::__construct($validator);

        $this->sessionCheckout = $sessionCheckout;
        $this->quoteRepository = $quoteRepository;
        $this->hyvaCheckoutAvardaPayments = $hyvaCheckoutAvardaPayments;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function mount(): void
    {
        $additionalData = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation('avarda_payments_ssn');
        $this->socialSecurityNumber = $additionalData ?? null;
    }

    /**
     * Listen for the Ssn to be updated.
     */
    public function updatedSocialSecurityNumber(string $value): ?string
    {
        $value = empty($value) ? null : $value;

        try {
            $quote = $this->sessionCheckout->getQuote();
            $quote->getPayment()->setAdditionalInformation('avarda_payments_ssn', $value);

            $this->quoteRepository->save($quote);
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }

        return $value;
    }

    /**
     * Validate that ssn is set
     *
     * @param EvaluationResultFactory $resultFactory
     * @return EvaluationResult
     */
    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResult
    {
        if ($this->socialSecurityNumber === null) {
            return $resultFactory->createErrorMessageEvent()
                ->withCustomEvent('payment:method:error')
                ->withMessage('Social security number is a required field.');
        }

        return $resultFactory->createSuccess();
    }

    public function getTermsHtml($method): string
    {
        return $this->hyvaCheckoutAvardaPayments->getTermsHtml($method);
    }
}
