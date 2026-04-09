<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{

    /**
     * Check if the product belongs to the same tenant as the user.
     *
     * @param  User    $user    The authenticated user.
     * @param  Product $product The product instance.
     * @return bool  True if the product belongs to the current tenant, false otherwise.
     */
    private function sameTenant(User $user, Product $product): bool
    {
        $tenant = app('current.tenant');
        return $tenant && $product->tenant_id === $tenant->id;
    }


    /**
     * Determine whether the user can view any products.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  Always true (all users can view products).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }


    /**
     * Determine whether the user can view a specific product.
     *
     * @param  User    $user    The authenticated user.
     * @param  Product $product The product instance.
     * @return bool  True if the product belongs to the current tenant, aborts(404) if not.
     */
    public function view(User $user, Product $product): bool
    {
        if (!$this->sameTenant($user, $product)) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can create a product.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  Always true (all users can create products).
     */
    public function create(User $user): bool
    {
        return true;
    }


    /**
     * Determine whether the user can update a product.
     *
     * @param  User    $user    The authenticated user.
     * @param  Product $product The product instance.
     * @return bool  True if the product belongs to the current tenant, aborts(404) if not.
     */
    public function update(User $user, Product $product): bool
    {
        if (!$this->sameTenant($user, $product)) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can delete a product.
     *
     * @param  User    $user    The authenticated user.
     * @param  Product $product The product instance.
     * @return bool  True if the product belongs to the current tenant, aborts(404) if not.
     */
    public function delete(User $user, Product $product): bool
    {
        if (!$this->sameTenant($user, $product)) {
            abort(404);
        }
        return true;
    }
}
