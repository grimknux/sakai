import { computed, reactive } from 'vue';

const STORAGE_KEY = 'app_layout_config_v1';

const DEFAULTS = {
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: false,
    menuMode: 'static'
};

const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: false,
    menuMode: 'static'
});
const THEME_KEY = 'app_theme';

const layoutState = reactive({
    staticMenuInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    sidebarExpanded: false,
    menuHoverActive: false,
    activeMenuItem: null,
    activePath: null
});

function applyTheme(isDark) {
    // Use explicit toggle with 2nd param (no surprises)
    document.documentElement.classList.toggle('app-dark', isDark);

    // Optional: if you want a light class too
    // document.documentElement.classList.toggle('app-light', !isDark);
}

function loadTheme() {
    const saved = localStorage.getItem(THEME_KEY); // 'dark' | 'light' | null
    const isDark = saved === 'dark';

    layoutConfig.darkTheme = isDark;
    applyTheme(isDark);
}

function saveTheme(isDark) {
    localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
}

export function useLayout() {
    loadTheme();

    const executeDarkModeToggle = () => {
        const next = !layoutConfig.darkTheme;

        layoutConfig.darkTheme = next;
        applyTheme(next);
        saveTheme(next);
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();
            return;
        }

        document.startViewTransition(() => executeDarkModeToggle());
    };

    const toggleMenu = () => {
        if (isDesktop()) {
            if (layoutConfig.menuMode === 'static') {
                layoutState.staticMenuInactive = !layoutState.staticMenuInactive;
            }

            if (layoutConfig.menuMode === 'overlay') {
                layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
            }
        } else {
            layoutState.mobileMenuActive = !layoutState.mobileMenuActive;
        }
    };

    const toggleConfigSidebar = () => {
        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const hideMobileMenu = () => {
        layoutState.mobileMenuActive = false;
    };

    const changeMenuMode = (event) => {
        layoutConfig.menuMode = event.value;
        layoutState.staticMenuInactive = false;
        layoutState.mobileMenuActive = false;
        layoutState.sidebarExpanded = false;
        layoutState.menuHoverActive = false;
        layoutState.anchored = false;
    };

    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const isDesktop = () => window.innerWidth > 991;

    const hasOpenOverlay = computed(() => layoutState.overlayMenuActive);

    return {
        layoutConfig,
        layoutState,
        isDarkTheme,
        toggleDarkMode,
        toggleConfigSidebar,
        toggleMenu,
        hideMobileMenu,
        changeMenuMode,
        isDesktop,
        hasOpenOverlay
    };
}
