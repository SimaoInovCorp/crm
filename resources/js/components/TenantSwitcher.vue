<script setup lang="ts">
import { ChevronsUpDown, Building2, Check } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTenantStore } from '@/stores/useTenantStore';

const store = useTenantStore();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div
                            class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                        >
                            <Building2 class="size-4" />
                        </div>
                        <div
                            class="grid flex-1 text-left text-sm leading-tight"
                        >
                            <span class="truncate font-semibold">
                                {{
                                    store.activeTenant?.name ??
                                    'Select workspace'
                                }}
                            </span>
                            <span
                                class="truncate text-xs text-muted-foreground capitalize"
                            >
                                {{ store.activeTenant?.role ?? '' }}
                            </span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                    align="start"
                    side="bottom"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        Workspaces
                    </DropdownMenuLabel>
                    <DropdownMenuGroup>
                        <DropdownMenuItem
                            v-for="tenant in store.tenants"
                            :key="tenant.slug"
                            class="cursor-pointer gap-2 p-2"
                            @click="store.switchTenant(tenant.slug)"
                        >
                            <div
                                class="flex size-6 items-center justify-center rounded-sm border"
                            >
                                <Building2 class="size-4 shrink-0" />
                            </div>
                            <span class="flex-1 truncate">{{
                                tenant.name
                            }}</span>
                            <Check
                                v-if="tenant.slug === store.activeTenantSlug"
                                class="ml-auto size-4"
                            />
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                    <DropdownMenuSeparator v-if="store.tenants.length > 0" />
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        No additional workspaces
                    </DropdownMenuLabel>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
