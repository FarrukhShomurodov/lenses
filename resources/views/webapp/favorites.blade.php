@extends('webapp.layout')

@section('title', __('webapp.favorites_title'))

@section('content')
    <div class="content">
        <div class="header">
            <a href="{{ url()->previous() == url()->current() ? route('webapp') : url()->previous() }}" class="header__btn i-back"></a>
            <p class="title">{{ __('webapp.favorites_title') }}</p>
        </div>

        <div class="products-list">

            @forelse($favorites as $fav)
                <div class="product__item">
                    <div class="product">
                        <div class="product__image">
                            <a href="{{ route('webapp.product.show', $fav->product->id) }}">
                                <img
                                    src="{{ $fav->product->images->first()
                                        ? asset('storage/' . $fav->product->images->first()->url)
                                        : '/no-image.png' }}"
                                    alt="image">
                            </a>
                        </div>

                        <div class="product__info">
                            <a href="{{ route('webapp.product.show', $fav->product->id) }}">
                                <span class="product__type">{{ $fav->product->localized_name }}</span>
                                <p class="product__title">
                                    {{ \Illuminate\Support\Str::limit($fav->product->localized_description, 50, '...') }}
                                </p>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div>
                    <p>{{ __('webapp.favorites_empty') }}</p>
                </div>
            @endforelse

        </div>
    </div>
@endsection


@section('nav')
    @include('webapp.partials.bottom-nav', ['navActive' => 'favs'])
@endsection

@section('scripts')
    <script>
        const topBadge = document.getElementById("cart-badge");
        const bottomBadge = document.getElementById("bottom-cart-badge");

        function updateBadge(count) {
            if (topBadge) {
                topBadge.style.display = count > 0 ? "block" : "none";
                topBadge.innerText = count;
            }

            if (bottomBadge) {
                bottomBadge.style.display = count > 0 ? "block" : "none";
                bottomBadge.innerText = count;
            }
        }

        function loadCartCount() {
            if (!userId) return;

            fetch(`/api/webapp/cart/count?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => updateBadge(data.count));
        }

        loadCartCount();
    </script>
@endsection
