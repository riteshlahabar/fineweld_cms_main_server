@props(['shippingAddress' => ''])

<div class="col-md-4">
    <x-label for="shipping_address" name="Shipping Address" />

    <textarea
        name="shipping_address"
        id="shipping_address"
        class="form-control"
        rows="2"
        placeholder="Enter Shipping Address">{{ old('shipping_address', $shippingAddress) }}</textarea>
</div>