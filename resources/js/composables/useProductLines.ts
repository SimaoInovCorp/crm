import { computed, ref } from 'vue';
import type { Ref } from 'vue';

export interface ProductOption {
    id: number;
    name: string;
    price: string | null;
}

export interface ProductLine {
    product_id: string;
    quantity: number;
    unit_price: number;
}

/**
 * Manages a list of product lines (product picker rows with qty and price).
 * Used in deal create/edit forms and calendar event forms.
 *
 * @param allProducts  Reactive list of available products to pick from.
 */
export function useProductLines(allProducts: Ref<ProductOption[]>) {
    const productLines = ref<ProductLine[]>([]);

    const productLinesTotal = computed(() =>
        productLines.value.reduce((s, p) => s + p.quantity * p.unit_price, 0),
    );

    function addProductLine(): void {
        productLines.value.push({ product_id: '', quantity: 1, unit_price: 0 });
    }

    function removeProductLine(idx: number): void {
        productLines.value.splice(idx, 1);
    }

    function onProductSelect(idx: number, productId: string): void {
        productLines.value[idx].product_id = productId;
        const p = allProducts.value.find((x) => String(x.id) === productId);
        if (p?.price) {
            productLines.value[idx].unit_price = parseFloat(p.price);
        }
    }

    return {
        productLines,
        productLinesTotal,
        addProductLine,
        removeProductLine,
        onProductSelect,
    };
}
