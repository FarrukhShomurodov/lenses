@extends('webapp.layout')

@section('title', __('webapp.cart_title'))

@section('head')
@endsection

@section('content')
    <div id="alert-box" class="alert-box"></div>

    <div class="order-modal" id="orderModal" style="display:none">
        <div class="order-modal__body">
            <button type="button" class="order-modal__close" id="closeOrderModal">×</button>

            <h3>{{ __('webapp.delivery_type') }}</h3>
            <select id="delivery_type">
                <option value="pickup">{{ __('webapp.pickup') }}</option>
                <option value="delivery">{{ __('webapp.delivery') }}</option>
            </select>

            <div id="delivery-fields" style="display:none">
                <input type="text" id="delivery_address" placeholder="{{ __('webapp.delivery_address') }}">
                <input type="text" id="delivery_phone" placeholder="{{ __('webapp.delivery_phone') }}">
            </div>

            <button id="confirm-order">{{ __('webapp.confirm') }}</button>
        </div>
    </div>

    <div class="content is-cart">
        <div class="header">
            <a href="{{ url()->previous() == url()->current() ? route('webapp') : url()->previous() }}" class="header__btn i-back"></a>
            <p class="title">{{ __('webapp.cart_title') }}</p>
        </div>

        <div class="products">
            @foreach ($cart->items as $item)
                @php
                    $itemPricing = $pricing['items'][$item->id] ?? null;
                    $unitPrice = $itemPricing['final_unit_price'] ?? $item->product->price;
                    $badge = null;
                    if (($itemPricing['product_discount'] ?? 0) > 0) {
                        $badge = '-' . $itemPricing['product_discount'] . '%';
                    } elseif (($itemPricing['applied_type'] ?? null) === 'promo_percent') {
                        $badge = '-' . ($pricing['promotion_percent'] ?? 0) . '%';
                    } elseif (($itemPricing['applied_type'] ?? null) === 'promo_one_plus_two') {
                        $badge = '1+2';
                    }
                @endphp
                <div class="product-card" data-id="{{ $item->id }}">

                    <a href="{{ route('webapp.product.show', $item->product->id) }}" class="product-card__image">
                        <img src="{{ $item->product->images->first() ? asset('storage/' . $item->product->images->first()->url) : '/no-image.png' }}"
                            alt="image">
                    </a>

                    <div class="product-card__data">
                        <span class="product-card__type">
                            {{ $item->product->category->localized_name ?? '' }}
                        </span>

                        <a href="{{ route('webapp.product.show', $item->product->id) }}" class="product-card__title"
                            style="text-decoration: none;">
                            {{ $item->product->localized_name }}
                        </a>

                        @if ($badge)
                            <div class="product-card__badge">{{ $badge }}</div>
                        @endif

                        <div class="product-card__meta">
                            <p class="product-card__price">
                                <span class="product-card__qty">{{ $item->quantity }}</span>
                                × <span
                                    class="product-card__unit-price">{{ number_format($unitPrice, 0, '.', ' ') }}</span>
                            </p>

                            <div class="product-card__btns">
                                <button type="button" class="product-card__plus" data-id="{{ $item->id }}"></button>
                                <button type="button" class="product-card__minus" data-id="{{ $item->id }}"></button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="product-card__del" data-id="{{ $item->id }}"></button>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('nav')
    <div class="payment">
        <div class="payment__row payment__row--muted">
            <p class="payment__label">Общая стоимость:</p>
            <p class="payment__label subtotal-price">
                {{ number_format($pricing['subtotal'], 0, '.', ' ') }}
            </p>
        </div>

        <div class="payment__row payment__row--muted payment__row--discount">
            <p class="payment__label">Скидка:</p>
            <p class="payment__label discount-price">
                {{ number_format($pricing['discount_total'], 0, '.', ' ') }}
            </p>
        </div>

        <div class="payment__row payment__row--total">
            <p class="payment__label">К оплате:</p>
            <p class="payment__label total-price">
                {{ number_format($pricing['total'], 0, '.', ' ') }}
            </p>
        </div>

        <p class="payment__label">{{ __('webapp.payment_methods') }}:</p>
        <div class="payment__vars">
            <div class="payment__var">
                <label class="custom-checkbox">
                    <input type="radio" name="payment" checked>
                    <span></span>
                    <p>PayMe</p>
                </label>
            </div>

            <div class="payment__var">
                <label class="custom-checkbox">
                    <input type="radio" name="payment">
                    <span></span>
                    <p>{{ __('webapp.cash') }}</p>
                </label>
            </div>
        </div>

        <button class="payment__btn" id="make-order" {{ $cart->items->count() === 0 ? 'disabled' : '' }}>
            {{ $cart->items->count() === 0 ? __('webapp.cart_empty') : __('webapp.pay') }}
        </button>
    </div>

    @include('webapp.partials.bottom-nav', ['navActive' => 'cart'])
@endsection

<script src="https://code.jquery.com/jquery-3.7.1.slim.js"
    integrity="sha256-UgvvN8vBkgO0luPSUl2s8TIlOSYRoGFAX4jlCIm9Adc=" crossorigin="anonymous"></script>
@section('scripts')
    <script>
        const csrf = "{{ csrf_token() }}";
        const payText = "{{ __('webapp.pay') }}";
        const emptyText = "{{ __('webapp.cart_empty') }}";

        function showError(message) {
            const box = document.getElementById('alert-box');
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");
            setTimeout(() => box.classList.remove('show'), 2000);
        }

        function parseAmount(value) {
            if (value === null || value === undefined) return 0;
            if (typeof value === 'number') return value;
            const cleaned = String(value).replace(/\s+/g, '').replace(',', '.');
            const num = Number(cleaned);
            return Number.isFinite(num) ? num : 0;
        }

        function formatAmount(value) {
            const num = parseAmount(value);
            // Округляем и разбиваем на разряды обычными пробелами
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        function updateOrderButtonText(count) {
            const payBtn = document.getElementById('make-order');
            if (!payBtn) return;

            if (count === 0) {
                payBtn.disabled = true;
                payBtn.innerText = emptyText;
            } else {
                payBtn.disabled = false;
                // Проверяем выбранный метод оплаты
                const selectedPayment = document.querySelector("input[name=payment]:checked");
                const label = selectedPayment.closest('.custom-checkbox').querySelector('p').innerText;

                payBtn.innerText = label === "{{ __('webapp.cash') }}" ?
                    "{{ __('webapp.order') }}" :
                    "{{ __('webapp.pay') }}";
            }
        }

        function applyTotals(data) {
            const total = parseAmount(data.total);
            const subtotal = parseAmount(data.subtotal ?? 0);
            const discount = parseAmount(data.discount_total ?? 0);

            // Обновляем текст напрямую, без лишних манипуляций с DOM
            const totalEl = document.querySelector('.total-price');
            if (totalEl) totalEl.textContent = formatAmount(total);

            document.querySelectorAll('.subtotal-price').forEach(el => {
                el.textContent = formatAmount(subtotal);
            });

            document.querySelectorAll('.discount-price').forEach(el => {
                el.textContent = formatAmount(discount);
            });

            // Обновление состояния кнопки (из предыдущего совета)
            const count = Number(data.count);
            if (!isNaN(count)) {
                const payBtn = document.getElementById('make-order');
                if (payBtn) {
                    if (count === 0) {
                        payBtn.disabled = true;
                        payBtn.innerText = emptyText;
                    } else {
                        payBtn.disabled = false;
                        // Проверяем актуальный текст в зависимости от выбранной оплаты
                        const selectedPayment = document.querySelector("input[name=payment]:checked");
                        const label = selectedPayment.closest('.custom-checkbox').querySelector('p').innerText;
                        payBtn.innerText = label === "{{ __('webapp.cash') }}" ? "{{ __('webapp.order') }}" :
                            "{{ __('webapp.pay') }}";
                    }
                }
            }
        }

        function updateQty(itemId, delta) {
            fetch("/api/webapp/cart/update", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        delta
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showError(data.message ?? "{{ __('webapp.add_error') }}");
                        return;
                    }

                    const productCard = document.querySelector(`.product-card[data-id="${itemId}"]`);
                    const qtyEl = productCard.querySelector('.product-card__qty');
                    const unitPriceEl = productCard.querySelector('.product-card__unit-price');

                    if (data.quantity === 0) productCard.remove();
                    else qtyEl.innerText = data.quantity;

                    if (unitPriceEl && data.item_unit_price !== null) {
                        unitPriceEl.innerText = formatAmount(data.item_unit_price);
                    }

                    applyTotals(data);
                });
        }

        document.querySelectorAll(".product-card__del").forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.blur();
                const itemId = this.dataset.id;

                fetch("/api/webapp/cart/remove", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrf
                        },
                        body: JSON.stringify({
                            item_id: itemId
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error || data.success === false) {
                            showError(data.error ?? data.message ?? "{{ __('webapp.add_error') }}");
                            return;
                        }

                        document.querySelector(`.product-card[data-id="${itemId}"]`).remove();
                        applyTotals(data);
                    });
            });
        });

        document.querySelectorAll(".product-card__plus").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                btn.blur();
                updateQty(btn.dataset.id, +1);
            });
        });

        document.querySelectorAll(".product-card__minus").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                btn.blur();
                updateQty(btn.dataset.id, -1);
            });
        });

        document.getElementById("make-order").addEventListener("click", function() {
            if (this.disabled) return;
            document.getElementById("orderModal").style.display = "block";
        });

        document.getElementById("closeOrderModal").addEventListener("click", function() {
            document.getElementById("orderModal").style.display = "none";
        });

        document.getElementById("delivery_type").addEventListener("change", function() {
            document.getElementById("delivery-fields").style.display =
                this.value === "delivery" ? "block" : "none";
        });

        document.getElementById("confirm-order").addEventListener("click", function() {

            const delivery_type = document.getElementById("delivery_type").value;
            const address = document.getElementById("delivery_address").value;
            const phone = document.getElementById("delivery_phone").value;

            if (delivery_type === 'delivery' && (!address || !phone)) {
                showError("{{ __('webapp.fill_delivery') }}");
                return;
            }

            const selectedPayment = document.querySelector("input[name=payment]:checked");
            const label = selectedPayment.closest('.custom-checkbox')
                .querySelector('p').innerText;

            const payment_type = label === "PayMe" ? "payme" : "cash";

            fetch("/api/webapp/order/create", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf
                    },
                    body: JSON.stringify({
                        chat_id: userId,
                        payment_type,
                        delivery_type,
                        delivery_address: address,
                        delivery_phone: phone,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) tg.close();

                    // if (data.success) {
                    // let formattedAmount = Math.round(data.order_total_price);
                    //let callback = `{{ env('APP_URL') }}/telegram/webapp`;

                    //     let paycomForm = `
                //         <form id="form-payme" method="POST" action="https://checkout.paycom.uz">
                //             <input type="hidden" name="merchant" value="6925c6584d88ce7417bab6d0">
                //             <input type="hidden" name="account[order_id]" value="${data.order_id}">
                //             <input type="hidden" name="amount" value="${formattedAmount * 100}">
                //             <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
                //             <input type="hidden" name="callback" value="${callback}">
                //             <input type="submit" value="">
                //         </form>
                //     `;

                    //     $('body').append(paycomForm);
                    //     $('#form-payme').submit();
                    // } else showError(data.message);

                });
        });

        document.querySelectorAll('input[name="payment"]').forEach(r => {
            r.addEventListener("change", function() {
                // Просто вызываем обновление текста на основе текущего состояния
                const currentCount = document.querySelectorAll('.product-card').length;
                updateOrderButtonText(currentCount);
            });
        });
    </script>
@endsection
