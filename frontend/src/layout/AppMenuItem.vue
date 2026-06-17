<script setup>
import { useLayout } from '@/layout/composables/layout';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const { layoutState, isDesktop } = useLayout();
const route = useRoute();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    root: {
        type: Boolean,
        default: true
    },
    parentPath: {
        type: String,
        default: ''
    },
    itemKey: {
        type: String,
        default: ''
    }
});

const itemId = computed(() => {
    return props.item.path || props.item.to || props.itemKey || props.item.label;
});

const fullPath = computed(() => {
    return props.parentPath ? `${props.parentPath}/${itemId.value}` : itemId.value;
});

const hasChildren = computed(() => {
    return !!(props.item.items && props.item.items.length);
});

// Check recursively if this item or any of its children matches current route
const hasActiveRoute = (item) => {
    if (item.to && route.path === item.to) {
        return true;
    }

    if (item.items) {
        return item.items.some((child) => hasActiveRoute(child));
    }

    return false;
};

const routeActive = computed(() => hasActiveRoute(props.item));

// manual expand/collapse state
const expanded = ref(false);

// keep submenu synced with current route
watch(
    () => route.path,
    () => {
        if (hasChildren.value) {
            expanded.value = routeActive.value;
        }
    },
    { immediate: true }
);

const isActive = computed(() => {
    return routeActive.value || expanded.value;
});

const itemClick = (event, item) => {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    if (item.command) {
        item.command({ originalEvent: event, item });
    }

    if (item.items) {
        event.preventDefault();

        // If a child route is active, keep it open
        if (routeActive.value) {
            expanded.value = true;
        } else {
            expanded.value = !expanded.value;
        }

        layoutState.menuHoverActive = true;
        return;
    }

    layoutState.overlayMenuActive = false;
    layoutState.mobileMenuActive = false;
    layoutState.menuHoverActive = false;
};

const onMouseEnter = () => {
    if (isDesktop() && props.root && props.item.items && layoutState.menuHoverActive) {
        expanded.value = true;
    }
};
</script>

<template>
    <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActive }">
        <div v-if="root && item.visible !== false" class="layout-menuitem-root-text">
            {{ item.label }}
        </div>

        <a v-if="(!item.to || item.items) && item.visible !== false" :href="item.url || '#'" @click="itemClick($event, item)" :class="item.class" :target="item.target" tabindex="0" @mouseenter="onMouseEnter">
            <i :class="item.icon" class="layout-menuitem-icon" />
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items" />
        </a>

        <router-link v-if="item.to && !item.items && item.visible !== false" @click="itemClick($event, item)" exactActiveClass="active-route" :class="item.class" tabindex="0" :to="item.to" @mouseenter="onMouseEnter">
            <i :class="item.icon" class="layout-menuitem-icon" />
            <span class="layout-menuitem-text">{{ item.label }}</span>
        </router-link>

        <Transition v-if="item.items && item.visible !== false" name="layout-submenu">
            <ul v-show="root ? true : isActive" class="layout-submenu">
                <app-menu-item v-for="(child, index) in item.items" :key="child.label + '_' + (child.to || child.path || index)" :item="child" :root="false" :parentPath="fullPath" :itemKey="String(index)" />
            </ul>
        </Transition>
    </li>
</template>
