<?php

namespace App\Livewire\Storefront;

use App\Models\User;
use App\Support\Checkout\CheckoutManager;
use App\Support\Checkout\ShippingRateResolver;
use App\Support\Security\RateLimitGuard;
use App\Support\Security\SecurityRateLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CheckoutPage extends Component
{
    /**
     * @var array{customer_name: string, email: string, phone: string, line_one: string, line_two: string, area: string, city: string, region: string, postal_code: string, country_code: string, customer_note: string}
     */
    public array $form = [
        'customer_name' => '',
        'email' => '',
        'phone' => '',
        'line_one' => '',
        'line_two' => '',
        'area' => '',
        'city' => '',
        'region' => '',
        'postal_code' => '',
        'country_code' => 'BD',
        'customer_note' => '',
    ];

    public ?int $shippingMethodId = null;

    public string $paymentMethod = 'cash_on_delivery';

    public string $couponCode = '';

    public ?string $couponMessage = null;

    public function mount(ShippingRateResolver $shippingRateResolver): void
    {
        $user = auth()->user();

        if ($user instanceof User) {
            $this->form['customer_name'] = $user->name;
            $this->form['email'] = $user->email;
        }

        $firstMethod = $shippingRateResolver->availableMethods($this->form['country_code'])->first();
        $this->shippingMethodId = $firstMethod?->id;
    }

    public function updatedFormCountryCode(): void
    {
        $this->shippingMethodId = null;
    }

    public function updatedFormRegion(): void
    {
        $this->shippingMethodId = null;
    }

    public function applyCoupon(): void
    {
        $this->resetErrorBag('couponCode');
        $this->couponMessage = null;
        $rateLimitKey = SecurityRateLimits::couponKey($this->user(), $this->form['email']);

        try {
            app(RateLimitGuard::class)->ensureAllowed(
                $rateLimitKey,
                SecurityRateLimits::CouponMaxAttempts,
                SecurityRateLimits::CouponDecaySeconds,
                'couponCode',
                'Too many coupon attempts. Try again in :seconds seconds.',
            );

            $result = app(CheckoutManager::class)->applyCouponCode($this->couponCode, $this->user(), $this->form['email']);
            app(RateLimitGuard::class)->reset($rateLimitKey);
            $this->couponCode = $result['coupon']->code;
            $this->couponMessage = $result['message'];
        } catch (ValidationException $exception) {
            $this->addError('couponCode', $this->validationMessage($exception, 'couponCode'));
        }
    }

    public function removeCoupon(): void
    {
        app(CheckoutManager::class)->clearCoupon();
        $this->couponCode = '';
        $this->couponMessage = __('Coupon removed.');
    }

    public function placeOrder(): void
    {
        $this->validate();

        try {
            $order = app(CheckoutManager::class)->placeOrder($this->checkoutPayload(), $this->user());

            session()->put('checkout.last_order_id', $order->id);
            session()->flash('status', __('Order placed successfully.'));

            $this->redirectRoute('checkout.thank-you', ['order' => $order->order_number]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? __('Checkout could not be completed.'));
            }
        }
    }

    public function render(CheckoutManager $checkoutManager, ShippingRateResolver $shippingRateResolver): View
    {
        try {
            $preview = $checkoutManager->currentPreview(
                $this->shippingMethodId,
                $this->form['country_code'],
                $this->form['region'],
                $this->user(),
                $this->form['email'],
            );
        } catch (ValidationException $exception) {
            $this->addError('cart', $this->validationMessage($exception, 'cart'));
            $checkoutManager->clearCoupon();

            try {
                $preview = $checkoutManager->currentPreview(null, $this->form['country_code'], $this->form['region']);
            } catch (ValidationException) {
                $preview = $checkoutManager->emptyPreview();
            }
        }

        $shippingRates = $shippingRateResolver->availableRates(
            $this->form['country_code'],
            $this->form['region'],
            $preview['subtotal'],
            $preview['coupon_result']['free_shipping'],
        );

        return view('livewire.storefront.checkout-page', [
            'preview' => $preview,
            'shippingRates' => $shippingRates,
            'paymentMethods' => $checkoutManager->supportedPaymentMethods(),
        ])->layout('components.layouts.storefront', [
            'title' => __('Checkout'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.customer_name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:40'],
            'form.line_one' => ['required', 'string', 'max:255'],
            'form.line_two' => ['nullable', 'string', 'max:255'],
            'form.area' => ['nullable', 'string', 'max:255'],
            'form.city' => ['required', 'string', 'max:120'],
            'form.region' => ['nullable', 'string', 'max:120'],
            'form.postal_code' => ['nullable', 'string', 'max:30'],
            'form.country_code' => ['required', 'string', 'size:2'],
            'form.customer_note' => ['nullable', 'string', 'max:1000'],
            'shippingMethodId' => ['required', 'integer', 'exists:shipping_methods,id'],
            'paymentMethod' => ['required', 'string', Rule::in(array_keys(app(CheckoutManager::class)->supportedPaymentMethods()))],
        ];
    }

    protected function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array{customer_name: string, email: string, phone: string|null, line_one: string, line_two: string|null, area: string|null, city: string, region: string|null, postal_code: string|null, country_code: string, shipping_method_id: int, payment_method: string, customer_note: string|null}
     */
    protected function checkoutPayload(): array
    {
        if ($this->shippingMethodId === null) {
            throw ValidationException::withMessages([
                'shippingMethodId' => __('Choose an available shipping method.'),
            ]);
        }

        return [
            'customer_name' => $this->form['customer_name'],
            'email' => $this->form['email'],
            'phone' => $this->nullableFormValue('phone'),
            'line_one' => $this->form['line_one'],
            'line_two' => $this->nullableFormValue('line_two'),
            'area' => $this->nullableFormValue('area'),
            'city' => $this->form['city'],
            'region' => $this->nullableFormValue('region'),
            'postal_code' => $this->nullableFormValue('postal_code'),
            'country_code' => strtoupper($this->form['country_code']),
            'shipping_method_id' => $this->shippingMethodId,
            'payment_method' => $this->paymentMethod,
            'customer_note' => $this->nullableFormValue('customer_note'),
        ];
    }

    protected function nullableFormValue(string $key): ?string
    {
        $value = $this->form[$key] ?? '';

        return trim($value) === '' ? null : $value;
    }

    protected function validationMessage(ValidationException $exception, string $fallbackField): string
    {
        return $exception->validator->errors()->first($fallbackField)
            ?: $exception->validator->errors()->first()
            ?: __('Checkout could not be completed.');
    }
}
