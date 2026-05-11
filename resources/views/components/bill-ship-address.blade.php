@props(['billingAddress' => '', 'shippingAddress' => ''])

<div class="col-md-4">
    <x-label for="billing_address" name="{{ __('app.bill_to') }}" />
    <textarea
        name="billing_address"
        id="billing_address"
        class="form-control"
        rows="2"
        placeholder="Enter Billing Address">{{ old('billing_address', $billingAddress) }}</textarea>
</div>

<div class="col-md-4">
    <x-label for="shipping_address" name="{{ __('app.ship_to') }}" />
    <textarea
        name="shipping_address"
        id="shipping_address"
        class="form-control"
        rows="2"
        placeholder="Enter Shipping Address">{{ old('shipping_address', $shippingAddress) }}</textarea>
</div>
