<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductService
{
    public function create(array $validated): Model|Product
    {
        return DB::transaction(function () use ($validated) {
            $product = Product::query()->create($validated);

            if (! empty($validated['photos'])) {
                $this->syncPhotos($product, $validated['photos']);
            }

            if (array_key_exists('attributes', $validated)) {
                $this->syncAttributes($product, (array) $validated['attributes']);
            }

            if (! Stock::query()->where('product_id', $product->id)->exists()) {
                $product->stock()->create([
                    'quantity' => 0,
                ]);
            }

            return $product->refresh();
        });
    }

    public function update(Product $product, array $validated): Product
    {
        return DB::transaction(function () use ($product, $validated) {
            $product->update($validated);

            if (! empty($validated['photos'])) {
                // replace existing photos: delete old files + records first
                $this->deleteOldPhotos($product);
                $this->syncPhotos($product, $validated['photos']);
            }

            if (array_key_exists('attributes', $validated)) {
                $this->syncAttributes($product, (array) $validated['attributes']);
            }

            return $product->refresh();
        });
    }

    public function delete(Product $product): JsonResponse
    {
        try {
            DB::transaction(function () use ($product) {
                $this->deleteOldPhotos($product);
                $product->delete();
            });

            return response()->json(['message' => 'Продукт успешно удалён'], 204);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Ошибка при удалении продукта',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function syncPhotos(Product $product, array $photos): void
    {
        foreach ($photos as $photo) {
            $path = $photo->store('products_photos', 'public');
            $product->images()->create(['url' => $path]);
        }
    }

    protected function deleteOldPhotos(Product $product): void
    {
        foreach ($product->images as $image) {
            if (Storage::disk('public')->exists($image->url)) {
                Storage::disk('public')->delete($image->url);
            }
            $image->delete();
        }
    }

    protected function syncAttributes(Product $product, array $attributes): void
    {
        ProductAttribute::query()->where('product_id', $product->id)->delete();

        $allowedAttributeIds = Attribute::query()
            ->where('category_id', $product->category_id)
            ->whereIn('id', array_keys($attributes))
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (empty($allowedAttributeIds)) {
            return;
        }

        $rows = [];

        foreach ($attributes as $attributeId => $values) {
            if (empty($values) || ! is_array($values)) {
                continue;
            }

            if (! in_array((int) $attributeId, $allowedAttributeIds, true)) {
                continue;
            }

            $validValues = AttributeValue::query()
                ->where('attribute_id', $attributeId)
                ->whereIn('value', array_map('strval', $values))
                ->pluck('value')
                ->all();

            foreach ($validValues as $value) {
                $rows[] = [
                    'product_id' => $product->id,
                    'attribute_id' => $attributeId,
                    'value' => (string) $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($rows)) {
            ProductAttribute::query()->insert($rows);
        }
    }
}
